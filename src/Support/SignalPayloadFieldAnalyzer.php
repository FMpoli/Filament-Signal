<?php

namespace Base33\FilamentSignal\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;

class SignalPayloadFieldAnalyzer
{
    protected SignalModelRegistry $modelRegistry;

    protected ReverseRelationRegistry $reverseRegistry;

    public function __construct(SignalModelRegistry $modelRegistry, ReverseRelationRegistry $reverseRegistry)
    {
        $this->modelRegistry = $modelRegistry;
        $this->reverseRegistry = $reverseRegistry;
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
        if (Str::startsWith($eventClass, 'eloquent.')) {
            return $this->analyzeEloquentEvent($eventClass);
        }

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
     * @param  array<int, array{descriptor: string, alias?: string|null, fields?: array, expand?: array}>  $reverseRelations
     */
    protected function processReverseRelations(
        string $propertyName,
        string $modelClass,
        array $reverseRelations,
        array &$relations
    ): void {
        foreach ($reverseRelations as $reverseConfig) {
            $descriptorKey = $reverseConfig['descriptor'] ?? null;
            if (! $descriptorKey) {
                continue;
            }

            $descriptor = $this->reverseRegistry->find($descriptorKey);

            if (! $descriptor) {
                continue;
            }

            $alias = $reverseConfig['alias'] ?? $this->defaultReverseAlias($descriptor);
            $sourceModel = $descriptor['source_model'] ?? null;

            // Verifica se il modello target (quello che ha la relazione inversa) proviene da Model Integration
            $isTargetFromIntegration = $this->isModelFromIntegration($modelClass);

            // Se il modello target proviene da Model Integration, usa SOLO i campi configurati
            if ($isTargetFromIntegration) {
                // Usa solo i campi esplicitamente configurati dall'utente
                $fieldOptions = $this->formatFieldOptions(
                    $reverseConfig['fields'] ?? [],
                    $sourceModel
                );

                // Non aggiungere campi extra, usa solo quelli configurati
            } else {
                // Per modelli con HasSignal, usa la logica completa con fallback
                // Prima prova con i campi selezionati nella Model Integration (se esistono)
                $fieldOptions = $this->formatFieldOptions(
                    $reverseConfig['fields'] ?? [],
                    $sourceModel
                );

                // Se non ci sono campi selezionati, usa i campi essenziali del modello sorgente
                if (empty($fieldOptions) && $sourceModel) {
                    $sourceModelFields = $this->modelRegistry->getFields($sourceModel);
                    if ($sourceModelFields && isset($sourceModelFields['essential'])) {
                        $essentialFields = $sourceModelFields['essential'];
                        // Estrai i nomi dei campi (gestisci sia array associativi che numerici)
                        $fieldNames = [];
                        foreach ($essentialFields as $key => $value) {
                            if (is_int($key)) {
                                // Array numerico: il valore è il nome del campo
                                $fieldNames[] = $value;
                            } else {
                                // Array associativo: la chiave è il nome del campo
                                $fieldNames[] = $key;
                            }
                        }
                        $fieldOptions = $this->formatFieldOptions($fieldNames, $sourceModel);
                    }
                }

                // Se ancora vuoto, usa un fallback con campi comuni
                if (empty($fieldOptions)) {
                    $fieldOptions = [
                        'id' => 'ID',
                        'name' => 'Name',
                        'created_at' => 'Created At',
                        'updated_at' => 'Updated At',
                    ];
                }

                // Aggiungi i campi delle relazioni annidate (ricorsivamente) solo per modelli con HasSignal
                if ($sourceModel) {
                    $sourceModelFields = $this->modelRegistry->getFields($sourceModel);
                    if ($sourceModelFields && isset($sourceModelFields['relations'])) {
                        $this->collectNestedRelationFieldsForReverse(
                            $sourceModelFields['relations'],
                            $sourceModel,
                            $alias,
                            $fieldOptions,
                            ''
                        );
                    }
                }
            }

            // Se il modello target proviene da Model Integration e non ci sono campi configurati, salta questa relazione
            if ($isTargetFromIntegration && empty($fieldOptions)) {
                continue;
            }

            $idField = "{$propertyName}.{$alias}";
            $formKey = $this->makeRelationFormKey('reverse::' . $descriptorKey, true);

            $relations[$idField] = [
                'id_field' => $idField,
                'relation_field' => "{$propertyName}.{$alias}",
                'model_class' => $descriptor['source_model'] ?? null,
                'label' => $descriptor['label'] ?? $alias,
                'expand' => $reverseConfig['expand'] ?? [],
                'field_options' => $fieldOptions,
                'mode' => 'reverse',
                'alias' => $alias,
                'form_key' => $formKey,
                'relation_name' => $descriptor['relation_name'] ?? $alias,
                'parent_property' => $propertyName,
                'reverse_descriptor' => $descriptorKey,
                'foreign_key' => $descriptor['foreign_key'] ?? null,
                'relation_type' => $descriptor['relation_type'] ?? null,
            ];
        }
    }

    protected function analyzeEloquentEvent(string $eventName): array
    {
        if (! preg_match('/eloquent\.[a-z_]+:\s*(.+)$/i', $eventName, $matches)) {
            return ['fields' => [], 'relations' => []];
        }

        $modelClass = trim($matches[1]);

        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return ['fields' => [], 'relations' => []];
        }

        $alias = $this->modelRegistry->getAlias($modelClass, Str::camel(class_basename($modelClass)));

        $fields = [];
        $relations = [];

        $modelFields = $this->modelRegistry->getFields($modelClass);

        if ($modelFields) {
            $this->processModelFields($alias, $modelClass, $modelFields, $fields, $relations);
        } else {
            $this->processModelFieldsFallback($alias, $modelClass, $fields, $relations);
        }

        return [
            'fields' => $fields,
            'relations' => $relations,
        ];
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
        $reverseRelations = Arr::get($modelFields, 'reverse_relations', []);

        app(ReverseRelationRegistrar::class)->register($modelClass, $modelFields);

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
                $alias = Arr::get($relationConfig, 'alias', $relationName);

                // Inizia con i campi diretti della relazione
                $fieldOptions = $this->formatFieldOptions(
                    Arr::get($relationConfig, 'fields', []),
                    $relatedModelClass
                );

                // Aggiungi i campi delle relazioni annidate (ricorsivamente)
                // Solo se il modello implementa HasSignal (non da Model Integration)
                // Per Model Integration, usiamo solo i campi esplicitamente configurati
                if ($relatedModelClass) {
                    $isFromModelIntegration = $this->isModelFromIntegration($modelClass);

                    // Se non è da Model Integration, aggiungi ricorsivamente tutti i campi disponibili
                    // Se è da Model Integration, usa solo i campi configurati (già in fieldOptions)
                    if (! $isFromModelIntegration) {
                        $relatedModelFields = $this->modelRegistry->getFields($relatedModelClass);
                        if ($relatedModelFields && isset($relatedModelFields['relations'])) {
                            $this->collectNestedRelationFieldsForDirect(
                                $relatedModelFields['relations'],
                                $relatedModelClass,
                                $relationName,
                                $fieldOptions,
                                ''
                            );
                        }
                    }
                }

                if (empty($fieldOptions)) {
                    continue;
                }

                $formKey = $this->makeRelationFormKey($idField);

                $relations[$idField] = [
                    'id_field' => $idField,
                    'relation_field' => $relationField,
                    'model_class' => $relatedModelClass,
                    'parent_model_class' => $modelClass, // Aggiungi il modello padre
                    'label' => $this->getRelationLabel("{$propertyName}.{$relationName}", $relatedModelClass),
                    'expand' => $expand, // Relazioni annidate da espandere
                    'field_options' => $fieldOptions,
                    'mode' => 'direct',
                    'alias' => $alias,
                    'form_key' => $formKey,
                    'relation_name' => $relationName,
                    'parent_property' => $propertyName,
                ];
            }
        }

        if (! empty($reverseRelations)) {
            $this->processReverseRelations($propertyName, $modelClass, $reverseRelations, $relations);
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
     * @param  array<int|string, string>  $fields
     * @return array<string, string>
     */
    protected function formatFieldOptions(array $fields, ?string $modelClass): array
    {
        $options = [];

        foreach ($fields as $key => $value) {
            if (is_int($key)) {
                $fieldKey = $value;
                $label = $this->getTranslatedFieldLabel($fieldKey, $modelClass);
            } else {
                $fieldKey = $key;
                $label = $value;
            }

            $options[$fieldKey] = $label;
        }

        return $options;
    }

    protected function makeRelationFormKey(string $identifier, bool $isReverse = false): string
    {
        if ($isReverse) {
            return 'reverse_' . md5($identifier);
        }

        return str_replace(['.', ' '], '_', $identifier);
    }

    protected function defaultReverseAlias(?array $descriptor): string
    {
        if (! $descriptor) {
            return 'relatedModels';
        }

        $source = class_basename($descriptor['source_model'] ?? 'Relation');
        $relation = $descriptor['relation_name'] ?? 'related';

        return Str::camel($source . '_' . $relation);
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

    /**
     * Raccoglie ricorsivamente tutti i campi delle relazioni annidate per le relazioni inverse.
     *
     * @param  array<string, mixed>  $relations  Configurazione delle relazioni
     * @param  string  $modelClass  Classe del modello corrente
     * @param  string  $baseAlias  Alias base (es: 'equipment_loan')
     * @param  array<string, string>  &$fieldOptions  Array di output per le opzioni dei campi
     * @param  string  $basePath  Path base corrente (es: 'unit' o 'unit.model')
     */
    protected function collectNestedRelationFieldsForReverse(
        array $relations,
        string $modelClass,
        string $baseAlias,
        array &$fieldOptions,
        string $basePath = ''
    ): void {
        foreach ($relations as $relationName => $relationConfig) {
            $relationExpand = $relationConfig['expand'] ?? [];
            $relationFields = $relationConfig['fields'] ?? [];
            $relatedModelClass = $this->getRelatedModelClassFromRelation($modelClass, $relationName);

            if (! $relatedModelClass) {
                continue;
            }

            $currentPath = $basePath === '' ? $relationName : "{$basePath}.{$relationName}";

            // Aggiungi i campi della relazione principale (es: unit.inventory_code o unit.model.name)
            if (! empty($relationFields)) {
                $nestedFieldOptions = $this->formatFieldOptions($relationFields, $relatedModelClass);
                foreach ($nestedFieldOptions as $fieldKey => $fieldLabel) {
                    $fullKey = "{$baseAlias}.{$currentPath}.{$fieldKey}";
                    // Formatta il path con frecce e nomi in minuscolo per leggibilità
                    $labelPath = str_replace('.', ' → ', strtolower($currentPath));
                    $fieldOptions[$fullKey] = "{$labelPath} → {$fieldLabel}";
                }
            }

            // Se ci sono relazioni annidate da espandere, processale ricorsivamente
            if (! empty($relationExpand)) {
                $relatedModelFields = $this->modelRegistry->getFields($relatedModelClass);

                // Se il modello correlato ha relazioni configurate, processale
                if ($relatedModelFields && isset($relatedModelFields['relations'])) {
                    // Filtra solo le relazioni che sono in expand
                    $nestedRelations = [];
                    foreach ($relationExpand as $nestedRelationName) {
                        if (isset($relatedModelFields['relations'][$nestedRelationName])) {
                            $nestedRelations[$nestedRelationName] = $relatedModelFields['relations'][$nestedRelationName];
                        } else {
                            // Se la relazione non è configurata, crea una entry di base per permettere la selezione
                            $nestedRelatedModelClass = $this->getRelatedModelClassFromRelation($relatedModelClass, $nestedRelationName);
                            if ($nestedRelatedModelClass) {
                                // Crea una configurazione di base con campi essenziali comuni
                                $nestedRelations[$nestedRelationName] = [
                                    'fields' => ['id', 'name'], // Campi essenziali comuni
                                    'expand' => [],
                                ];
                            }
                        }
                    }

                    // Processa ricorsivamente
                    if (! empty($nestedRelations)) {
                        $this->collectNestedRelationFieldsForReverse(
                            $nestedRelations,
                            $relatedModelClass,
                            $baseAlias,
                            $fieldOptions,
                            $currentPath
                        );
                    }
                } else {
                    // Se il modello correlato non ha getSignalFields configurato,
                    // aggiungi comunque i campi essenziali comuni per le relazioni in expand
                    foreach ($relationExpand as $nestedRelationName) {
                        $nestedRelatedModelClass = $this->getRelatedModelClassFromRelation($relatedModelClass, $nestedRelationName);
                        if ($nestedRelatedModelClass) {
                            // Aggiungi campi essenziali comuni
                            $commonFields = ['id', 'name'];
                            $nestedFieldOptions = $this->formatFieldOptions($commonFields, $nestedRelatedModelClass);
                            foreach ($nestedFieldOptions as $fieldKey => $fieldLabel) {
                                $fullKey = "{$baseAlias}.{$currentPath}.{$nestedRelationName}.{$fieldKey}";
                                // Formatta il path con frecce e nomi in minuscolo per leggibilità
                                $labelPath = str_replace('.', ' → ', strtolower($currentPath));
                                $nestedRelationLabel = ucfirst(strtolower($nestedRelationName));
                                $fieldOptions[$fullKey] = "{$labelPath} → {$nestedRelationLabel} → {$fieldLabel}";
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Raccoglie ricorsivamente tutti i campi delle relazioni annidate per le relazioni dirette.
     *
     * @param  array<string, mixed>  $relations  Configurazione delle relazioni
     * @param  string  $modelClass  Classe del modello corrente
     * @param  string  $baseRelationName  Nome della relazione base (es: 'unit')
     * @param  array<string, string>  &$fieldOptions  Array di output per le opzioni dei campi
     * @param  string  $basePath  Path base corrente (es: 'unit' o 'unit.model')
     */
    protected function collectNestedRelationFieldsForDirect(
        array $relations,
        string $modelClass,
        string $baseRelationName,
        array &$fieldOptions,
        string $basePath = ''
    ): void {
        foreach ($relations as $relationName => $relationConfig) {
            $relationExpand = $relationConfig['expand'] ?? [];
            $relationFields = $relationConfig['fields'] ?? [];
            $relatedModelClass = $this->getRelatedModelClassFromRelation($modelClass, $relationName);

            if (! $relatedModelClass) {
                continue;
            }

            $currentPath = $basePath === '' ? $relationName : "{$basePath}.{$relationName}";

            // Aggiungi i campi della relazione principale (es: unit.inventory_code o unit.model.name)
            if (! empty($relationFields)) {
                $nestedFieldOptions = $this->formatFieldOptions($relationFields, $relatedModelClass);
                foreach ($nestedFieldOptions as $fieldKey => $fieldLabel) {
                    $fullKey = "{$baseRelationName}.{$currentPath}.{$fieldKey}";
                    // Formatta il path con frecce e nomi in minuscolo per leggibilità
                    $labelPath = str_replace('.', ' → ', strtolower($currentPath));
                    $fieldOptions[$fullKey] = "{$labelPath} → {$fieldLabel}";
                }
            }

            // Se ci sono relazioni annidate da espandere, processale ricorsivamente
            if (! empty($relationExpand)) {
                $relatedModelFields = $this->modelRegistry->getFields($relatedModelClass);

                // Se il modello correlato ha relazioni configurate, processale
                if ($relatedModelFields && isset($relatedModelFields['relations'])) {
                    // Filtra solo le relazioni che sono in expand
                    $nestedRelations = [];
                    foreach ($relationExpand as $nestedRelationName) {
                        if (isset($relatedModelFields['relations'][$nestedRelationName])) {
                            $nestedRelations[$nestedRelationName] = $relatedModelFields['relations'][$nestedRelationName];
                        } else {
                            // Se la relazione non è configurata, crea una entry di base per permettere la selezione
                            $nestedRelatedModelClass = $this->getRelatedModelClassFromRelation($relatedModelClass, $nestedRelationName);
                            if ($nestedRelatedModelClass) {
                                // Crea una configurazione di base con campi essenziali comuni
                                $nestedRelations[$nestedRelationName] = [
                                    'fields' => ['id', 'name'], // Campi essenziali comuni
                                    'expand' => [],
                                ];
                            }
                        }
                    }

                    // Processa ricorsivamente
                    if (! empty($nestedRelations)) {
                        $this->collectNestedRelationFieldsForDirect(
                            $nestedRelations,
                            $relatedModelClass,
                            $baseRelationName,
                            $fieldOptions,
                            $currentPath
                        );
                    }
                } else {
                    // Se il modello correlato non ha getSignalFields configurato,
                    // aggiungi comunque i campi essenziali comuni per le relazioni in expand
                    foreach ($relationExpand as $nestedRelationName) {
                        $nestedRelatedModelClass = $this->getRelatedModelClassFromRelation($relatedModelClass, $nestedRelationName);
                        if ($nestedRelatedModelClass) {
                            // Aggiungi campi essenziali comuni
                            $commonFields = ['id', 'name'];
                            $nestedFieldOptions = $this->formatFieldOptions($commonFields, $nestedRelatedModelClass);
                            foreach ($nestedFieldOptions as $fieldKey => $fieldLabel) {
                                $fullKey = "{$baseRelationName}.{$currentPath}.{$nestedRelationName}.{$fieldKey}";
                                // Formatta il path con frecce e nomi in minuscolo per leggibilità
                                $labelPath = str_replace('.', ' → ', strtolower($currentPath));
                                $nestedRelationLabel = ucfirst(strtolower($nestedRelationName));
                                $fieldOptions[$fullKey] = "{$labelPath} → {$nestedRelationLabel} → {$fieldLabel}";
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Verifica se un modello è registrato tramite Model Integration (non implementa HasSignal).
     */
    protected function isModelFromIntegration(string $modelClass): bool
    {
        // Verifica se il modello implementa HasSignal
        if (is_subclass_of($modelClass, \Base33\FilamentSignal\Contracts\HasSignal::class)) {
            return false;
        }

        // Verifica se esiste un record SignalModelIntegration per questo modello
        $integration = \Base33\FilamentSignal\Models\SignalModelIntegration::where('model_class', $modelClass)
            ->whereNull('deleted_at')
            ->first();

        return $integration !== null;
    }
}
