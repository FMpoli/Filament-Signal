<?php

namespace Base33\FilamentSignal\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use ReflectionClass;

class SignalPayloadFieldAnalyzer
{
    protected SignalModelRegistry $modelRegistry;

    public function __construct(SignalModelRegistry $modelRegistry)
    {
        $this->modelRegistry = $modelRegistry;
    }

    /**
     * Analizza un evento e restituisce i campi disponibili basati su HasSignal
     *
     * @return array{
     *     fields: array<string, array{label: string, type: string, group: string}>,
     *     relations: array<string, array{id_field: string, relation_field: string, model_class: string|null, label: string, expand: array<string>}>
     * }
     */
    public function analyzeEvent(string $eventClass): array
    {
        if (! class_exists($eventClass)) {
            return ['fields' => [], 'relations' => []];
        }

        try {
            $reflection = new ReflectionClass($eventClass);
            $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);

            $fields = [];
            $relations = [];

            foreach ($properties as $property) {
                $name = $property->getName();
                $type = $property->getType();

                // Se è un modello, controlla se ha HasSignal
                if ($type && $type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                    $typeClass = $type->getName();

                    if (is_subclass_of($typeClass, Model::class)) {
                        $modelFields = $this->modelRegistry->getFields($typeClass);

                        if ($modelFields) {
                            // Usa i campi definiti da HasSignal
                            $this->processModelFields($name, $typeClass, $modelFields, $fields, $relations);
                        } else {
                            // Fallback: usa tutti i campi del modello (compatibilità)
                            $this->processModelFieldsFallback($name, $typeClass, $fields, $relations);
                        }
                    } else {
                        // Non è un modello, è un campo semplice dell'evento (es: currentStatus, previousStatus)
                        // Questi campi appartengono all'evento stesso, non al modello
                        $fieldType = $this->getFieldType($type, $name);
                        $label = $this->getTranslatedEventFieldLabel($name, $eventClass);

                        $fields[$name] = [
                            'label' => $label,
                            'type' => $fieldType,
                            'group' => 'Event fields',
                        ];
                    }
                } else {
                    // Campo primitivo dell'evento (es: string, int, ecc.)
                    $fieldType = $this->getFieldType($type, $name);
                    $label = $this->getTranslatedEventFieldLabel($name, $eventClass);

                    $fields[$name] = [
                        'label' => $label,
                        'type' => $fieldType,
                        'group' => 'Event fields',
                    ];
                }
            }

            return [
                'fields' => $fields,
                'relations' => $relations,
            ];
        } catch (\Throwable $e) {
            return ['fields' => [], 'relations' => []];
        }
    }

    /**
     * Processa i campi di un modello usando HasSignal
     */
    protected function processModelFields(
        string $propertyName,
        string $modelClass,
        array $modelFields,
        array &$fields,
        array &$relations
    ): void {
        $essential = Arr::get($modelFields, 'essential', []);
        $modelRelations = Arr::get($modelFields, 'relations', []);

        $modelLabel = $this->getModelLabel($modelClass);

        // Aggiungi i campi essenziali
        foreach ($essential as $field => $label) {
            $fieldKey = is_int($field) ? $label : $field;
            $fieldLabel = is_int($field) ? $this->getTranslatedFieldLabel($fieldKey, $modelClass) : $this->getTranslatedFieldLabel($label, $modelClass, $label);

            $fullFieldKey = "{$propertyName}.{$fieldKey}";
            $translatedPropertyName = $this->getTranslatedPropertyName($propertyName, $modelClass);
            $fullLabel = "{$translatedPropertyName} → {$fieldLabel}";

            $fields[$fullFieldKey] = [
                'label' => $fullLabel,
                'type' => 'string', // Tipo generico, può essere migliorato
                'group' => "{$modelLabel} fields",
            ];
        }

        // Aggiungi le relazioni
        foreach ($modelRelations as $relationName => $relationConfig) {
            $idField = "{$propertyName}.{$relationName}_id";
            $relationField = "{$propertyName}.{$relationName}";

            // Ottieni la classe del modello correlato usando reflection sul metodo di relazione
            $relatedModelClass = $this->getRelatedModelClassFromRelation($modelClass, $relationName);

            if ($relatedModelClass) {
                $expand = Arr::get($relationConfig, 'expand', []);
                
                $relations[$idField] = [
                    'id_field' => $idField,
                    'relation_field' => $relationField,
                    'model_class' => $relatedModelClass,
                    'label' => $this->getRelationLabel("{$propertyName}.{$relationName}", $relatedModelClass),
                    'expand' => $expand, // Relazioni annidate da espandere
                ];
            }
        }
    }

    /**
     * Fallback: processa tutti i campi del modello (compatibilità)
     */
    protected function processModelFieldsFallback(
        string $propertyName,
        string $modelClass,
        array &$fields,
        array &$relations
    ): void {
        $modelLabel = $this->getModelLabel($modelClass);
        $modelFields = $this->getModelFields($modelClass);

        foreach ($modelFields as $modelField => $modelFieldType) {
            $nestedFieldKey = "{$propertyName}.{$modelField}";
            $nestedLabel = $this->getFieldLabel($modelField, $propertyName);

            $fields[$nestedFieldKey] = [
                'label' => $nestedLabel,
                'type' => $modelFieldType,
                'group' => "{$modelLabel} fields",
            ];

            // Se il campo è un ID, aggiungi come relazione
            if (str_ends_with($modelField, '_id')) {
                $nestedIdField = "{$propertyName}.{$modelField}";
                $nestedRelationField = str_replace('_id', '', $modelField);
                $nestedModelClass = $this->guessModelClass($modelField, $modelClass);

                if ($nestedModelClass) {
                    $relations[$nestedIdField] = [
                        'id_field' => $nestedIdField,
                        'relation_field' => "{$propertyName}.{$nestedRelationField}",
                        'model_class' => $nestedModelClass,
                        'label' => $this->getRelationLabel("{$propertyName}.{$nestedRelationField}", $nestedModelClass),
                        'expand' => [],
                    ];
                }
            }
        }
    }

    /**
     * Determina il tipo di campo
     */
    protected function getFieldType(?\ReflectionType $type, string $name): string
    {
        if (! $type) {
            return 'mixed';
        }

        if ($type instanceof \ReflectionNamedType) {
            if ($type->isBuiltin()) {
                return $type->getName();
            }

            $typeName = $type->getName();

            if (is_subclass_of($typeName, Model::class)) {
                return 'Model';
            }

            if (is_subclass_of($typeName, \BackedEnum::class)) {
                return 'Enum';
            }

            if (is_subclass_of($typeName, \DateTimeInterface::class)) {
                return 'DateTime';
            }

            return 'object';
        }

        return 'mixed';
    }

    /**
     * Ottiene i campi di un modello (fallback)
     */
    protected function getModelFields(string $modelClass): array
    {
        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return [];
        }

        try {
            $model = new $modelClass;
            $fillable = $model->getFillable();
            $casts = $model->getCasts();

            $fields = [];

            foreach ($fillable as $field) {
                $fields[$field] = $casts[$field] ?? 'string';
            }

            $fields['id'] = 'integer';
            $fields['created_at'] = 'datetime';
            $fields['updated_at'] = 'datetime';

            return $fields;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Ottiene la classe del modello correlato usando reflection sul metodo di relazione
     * Questo è completamente agnostico e funziona con qualsiasi modello Laravel
     */
    protected function getRelatedModelClassFromRelation(string $modelClass, string $relationName): ?string
    {
        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        try {
            $model = new $modelClass;
            
            // Verifica se il metodo di relazione esiste
            if (! method_exists($model, $relationName)) {
                return null;
            }

            // Chiama il metodo di relazione per ottenere l'oggetto relazione
            $relation = $model->{$relationName}();
            
            // Se è una relazione Eloquent, ottieni il modello correlato
            if (method_exists($relation, 'getRelated')) {
                $relatedModel = $relation->getRelated();
                return get_class($relatedModel);
            }

            // Fallback: usa reflection per ottenere il tipo di ritorno del metodo
            $reflection = new ReflectionClass($modelClass);
            if ($reflection->hasMethod($relationName)) {
                $method = $reflection->getMethod($relationName);
                $returnType = $method->getReturnType();
                
                if ($returnType instanceof \ReflectionNamedType) {
                    $returnTypeClass = $returnType->getName();
                    // Se è una classe di relazione Eloquent, prova a ottenere il modello correlato
                    if (class_exists($returnTypeClass)) {
                        // Prova a istanziare la relazione e ottenere il modello correlato
                        try {
                            $relationInstance = $model->{$relationName}();
                            if (method_exists($relationInstance, 'getRelated')) {
                                return get_class($relationInstance->getRelated());
                            }
                        } catch (\Throwable $e) {
                            // Ignora errori durante l'istanziazione
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Se fallisce, usa il metodo di fallback
            return $this->guessModelClassFallback($relationName, $modelClass);
        }

        return null;
    }

    /**
     * Fallback: indovina la classe del modello da un campo ID o nome relazione
     * Usato solo quando non è possibile ottenere il tipo dalla relazione
     */
    protected function guessModelClassFallback(string $idFieldOrRelationName, string $parentModelClass): ?string
    {
        $relationName = str_replace('_id', '', $idFieldOrRelationName);
        $parentNamespace = substr($parentModelClass, 0, strrpos($parentModelClass, '\\'));

        // Prova a trovare il modello nello stesso namespace
        $possibleNames = [
            ucfirst($relationName), // Es: Unit
            ucfirst(str_replace('_', '', ucwords($relationName, '_'))), // Es: EquipmentUnit da equipment_unit
        ];

        foreach ($possibleNames as $name) {
            $possibleClass = $parentNamespace . '\\' . $name;
            if (class_exists($possibleClass) && is_subclass_of($possibleClass, Model::class)) {
                return $possibleClass;
            }
        }

        // Prova anche nel namespace Models
        $modelsNamespace = $parentNamespace . '\\Models';
        foreach ($possibleNames as $name) {
            $possibleClass = $modelsNamespace . '\\' . $name;
            if (class_exists($possibleClass) && is_subclass_of($possibleClass, Model::class)) {
                return $possibleClass;
            }
        }

        // Ultimo fallback: solo User (modello standard Laravel)
        if (in_array(strtolower($relationName), ['user', 'borrower', 'loaner', 'author', 'creator', 'owner'])) {
            if (class_exists(\App\Models\User::class)) {
                return \App\Models\User::class;
            }
        }

        return null;
    }

    /**
     * @deprecated Usa getRelatedModelClassFromRelation invece
     * Mantenuto per compatibilità con il codice esistente
     */
    protected function guessModelClass(string $idFieldOrRelationName, string $parentModelClass): ?string
    {
        $relationName = str_replace('_id', '', $idFieldOrRelationName);
        return $this->getRelatedModelClassFromRelation($parentModelClass, $relationName)
            ?? $this->guessModelClassFallback($idFieldOrRelationName, $parentModelClass);
    }

    /**
     * Ottiene un'etichetta leggibile per una relazione
     */
    protected function getRelationLabel(string $relationName, ?string $modelClass): string
    {
        if ($modelClass) {
            $parts = explode('\\', $modelClass);
            $className = end($parts);
            $cleanRelationName = str_replace('_', ' ', $relationName);
            $cleanRelationName = str_replace('.', ' → ', $cleanRelationName);

            return ucwords($cleanRelationName) . " ({$className})";
        }

        return ucfirst(str_replace('_', ' ', $relationName));
    }

    /**
     * Ottiene un'etichetta leggibile per un campo
     */
    protected function getFieldLabel(string $fieldName, ?string $prefix = null): string
    {
        $label = $fieldName;

        if ($prefix) {
            $label = "{$prefix} → {$fieldName}";
        }

        $label = str_replace('_', ' ', $label);
        $label = preg_replace('/([a-z])([A-Z])/', '$1 $2', $label);

        return ucwords($label);
    }

    /**
     * Ottiene un'etichetta leggibile per un modello
     */
    protected function getModelLabel(string $modelClass): string
    {
        $parts = explode('\\', $modelClass);
        $className = end($parts);

        $className = str_replace('Equipment', '', $className);
        $className = preg_replace('/([a-z])([A-Z])/', '$1 $2', $className);

        return ucwords(strtolower($className));
    }

    /**
     * Ottiene un'etichetta tradotta per un campo
     * Metodo pubblico per essere usato nelle closure
     */
    public function getTranslatedFieldLabel(string $fieldKey, string $modelClass, ?string $fallback = null): string
    {
        // Usa il fieldKey direttamente come chiave di traduzione (generico)
        $translationKey = $fieldKey;

        // Prova a trovare la traduzione nel namespace del modello
        $modelNamespace = substr($modelClass, 0, strrpos($modelClass, '\\'));
        $packageName = $this->getPackageNameFromNamespace($modelNamespace);

        if ($packageName) {
            // Prova vari formati di chiave di traduzione
            // Il formato corretto per Filament è: filament-{package}::{namespace}.{key}
            $keys = [
                "filament-{$packageName}::{$packageName}.fields.{$translationKey}",
                "{$packageName}::{$packageName}.fields.{$translationKey}",
                "filament-{$packageName}::fields.{$translationKey}",
                "{$packageName}::fields.{$translationKey}",
            ];

            foreach ($keys as $key) {
                $translated = trans($key);
                if ($translated !== $key) {
                    return $translated;
                }
            }
        }

        // Fallback: usa il label fornito o genera uno automatico
        return $fallback ?? $this->getFieldLabel($fieldKey);
    }

    /**
     * Ottiene un'etichetta tradotta per un campo dell'evento
     */
    protected function getTranslatedEventFieldLabel(string $fieldKey, string $eventClass): string
    {
        // Mappa i nomi dei campi dell'evento alle traduzioni
        if ($fieldKey === 'currentStatus') {
            return 'Stato corrente';
        }
        if ($fieldKey === 'previousStatus') {
            return 'Stato precedente';
        }

        // Prova a trovare la traduzione nel namespace dell'evento
        $eventNamespace = substr($eventClass, 0, strrpos($eventClass, '\\'));
        $packageName = $this->getPackageNameFromNamespace($eventNamespace);

        if ($packageName) {
            $keys = [
                "filament-{$packageName}::{$packageName}.fields.{$fieldKey}",
                "filament-{$packageName}::{$packageName}.fields.{$fieldKey}",
            ];

            foreach ($keys as $key) {
                $translated = trans($key);
                if ($translated !== $key) {
                    return $translated;
                }
            }
        }

        // Fallback: genera label automatico
        return $this->getFieldLabel($fieldKey);
    }

    /**
     * Estrae il nome del package dal namespace
     */
    protected function getPackageNameFromNamespace(string $namespace): ?string
    {
        // Es: Vendor\FilamentPlugin -> plugin
        // Es: App\Models -> null (non è un package)

        if (str_contains($namespace, 'Filament')) {
            $parts = explode('\\', $namespace);
            foreach ($parts as $part) {
                if (str_starts_with($part, 'Filament') && $part !== 'Filament') {
                    // Es: FilamentLabOps -> labops
                    $name = str_replace('Filament', '', $part);

                    return strtolower($name);
                }
            }

            // Se non trovato, prova a cercare il nome del package direttamente
            // Estrai il nome del package dal namespace
            foreach ($parts as $part) {
                if (str_starts_with($part, 'Filament') && $part !== 'Filament') {
                    $name = str_replace('Filament', '', $part);
                    return strtolower($name);
                }
            }
        }

        return null;
    }

    /**
     * Ottiene il nome tradotto di una proprietà (es: loan → Prestito)
     */
    protected function getTranslatedPropertyName(string $propertyName, string $modelClass): string
    {
        // Prova a trovare la traduzione nel namespace del modello
        $modelNamespace = substr($modelClass, 0, strrpos($modelClass, '\\'));
        $packageName = $this->getPackageNameFromNamespace($modelNamespace);

        if ($packageName) {
            // Prova a trovare la traduzione nelle risorse
            $keys = [
                "filament-{$packageName}::{$packageName}.resources.{$propertyName}.label",
                "{$packageName}::{$packageName}.resources.{$propertyName}.label",
                "filament-{$packageName}::{$packageName}.fields.{$propertyName}",
                "filament-{$packageName}::fields.{$propertyName}",
                "{$packageName}::fields.{$propertyName}",
            ];

            foreach ($keys as $key) {
                $translated = trans($key);
                if ($translated !== $key) {
                    return $translated;
                }
            }
        }

        // Fallback: usa il nome della proprietà capitalizzato
        return ucfirst($propertyName);
    }
}
