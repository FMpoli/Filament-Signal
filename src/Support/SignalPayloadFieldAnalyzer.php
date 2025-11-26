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

            // Indovina la classe del modello dalla relazione
            $relatedModelClass = $this->guessModelClass($relationName, $modelClass);

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
     * Indovina la classe del modello da un campo ID o nome relazione
     */
    protected function guessModelClass(string $idFieldOrRelationName, string $parentModelClass): ?string
    {
        $relationName = str_replace('_id', '', $idFieldOrRelationName);
        $parentNamespace = substr($parentModelClass, 0, strrpos($parentModelClass, '\\'));

        // Prova a trovare il modello nello stesso namespace
        $possibleNames = [
            'Equipment' . ucfirst($relationName), // Es: EquipmentUnit
            ucfirst($relationName), // Es: Unit
            ucfirst(str_replace('_', '', ucwords($relationName, '_'))), // Es: EquipmentUnit da equipment_unit
        ];

        foreach ($possibleNames as $name) {
            $possibleClass = $parentNamespace . '\\' . $name;
            if (class_exists($possibleClass) && is_subclass_of($possibleClass, Model::class)) {
                return $possibleClass;
            }
        }

        // Mappa comune per relazioni note
        $commonModels = [
            'unit' => \Detit\FilamentLabOps\Models\EquipmentUnit::class,
            'borrower' => \App\Models\User::class,
            'loaner' => \App\Models\User::class,
            'user' => \App\Models\User::class,
            'model' => \Detit\FilamentLabOps\Models\EquipmentModel::class,
            'brand' => \Detit\FilamentLabOps\Models\EquipmentBrand::class,
            'type' => \Detit\FilamentLabOps\Models\EquipmentType::class,
            'location' => \Detit\FilamentLabOps\Models\EquipmentLocation::class,
            // Fallback per nomi completi
            'EquipmentUnit' => \Detit\FilamentLabOps\Models\EquipmentUnit::class,
            'EquipmentModel' => \Detit\FilamentLabOps\Models\EquipmentModel::class,
            'EquipmentBrand' => \Detit\FilamentLabOps\Models\EquipmentBrand::class,
            'EquipmentType' => \Detit\FilamentLabOps\Models\EquipmentType::class,
            'User' => \App\Models\User::class,
        ];

        // Cerca per nome esatto (case-insensitive)
        $relationNameLower = strtolower($relationName);
        if (isset($commonModels[$relationNameLower])) {
            $class = $commonModels[$relationNameLower];
            if (class_exists($class)) {
                return $class;
            }
        }

        // Cerca per match parziale
        foreach ($commonModels as $key => $class) {
            if (stripos($relationName, strtolower($key)) !== false && class_exists($class)) {
                return $class;
            }
        }

        return null;
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
        // Mappa i nomi dei campi alle chiavi di traduzione
        $translationMap = [
            'loaned_at' => 'loan_start_at',
            'due_at' => 'loan_due_at',
            'returned_at' => 'loan_returned_at',
            'included_accessories' => 'loan_accessories',
            'notes' => 'loan_notes',
            'status' => 'status',
            'id' => 'id',
        ];

        $translationKey = $translationMap[$fieldKey] ?? $fieldKey;

        // Prova a trovare la traduzione nel namespace del modello
        $modelNamespace = substr($modelClass, 0, strrpos($modelClass, '\\'));
        $packageName = $this->getPackageNameFromNamespace($modelNamespace);

        if ($packageName) {
            // Prova vari formati di chiave di traduzione
            // Il formato corretto per Filament è: filament-{package}::{namespace}.{key}
            $keys = [
                "filament-{$packageName}::labops.fields.{$translationKey}",
                "filament-{$packageName}::{$packageName}.fields.{$translationKey}",
                "{$packageName}::labops.fields.{$translationKey}",
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
                "filament-{$packageName}::labops.fields.{$fieldKey}",
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
        // Es: Detit\FilamentLabOps -> labops
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
            // Es: Detit\FilamentLabOps -> cerca "labops"
            foreach ($parts as $part) {
                if (stripos($part, 'labops') !== false) {
                    return 'labops';
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
                "filament-{$packageName}::labops.resources.{$propertyName}.label",
                "filament-{$packageName}::{$packageName}.resources.{$propertyName}.label",
                "{$packageName}::labops.resources.{$propertyName}.label",
                "{$packageName}::{$packageName}.resources.{$propertyName}.label",
                "filament-{$packageName}::labops.fields.{$propertyName}",
                "filament-{$packageName}::{$packageName}.fields.{$propertyName}",
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
