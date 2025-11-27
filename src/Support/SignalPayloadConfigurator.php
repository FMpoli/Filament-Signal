<?php

namespace Base33\FilamentSignal\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class SignalPayloadConfigurator
{
    /**
     * Configura e filtra il payload in base alle configurazioni
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $config  Configurazione dei campi da includere/escludere
     * @return array<string, mixed>
     */
    public function configure(array $payload, array $config = []): array
    {
        $includeFields = Arr::get($config, 'include_fields', []);
        $excludeFields = Arr::get($config, 'exclude_fields', []);
        $expandRelations = Arr::get($config, 'expand_relations', []);
        $expandNested = Arr::get($config, 'expand_nested', []);
        $relationFields = Arr::get($config, 'relation_fields', []); // Campi selezionati per ogni relazione
        $reverseSelections = Arr::get($config, 'reverse_relations', []);
        $relationMetaMap = Arr::get($config, 'relation_meta_map', []);

        // IMPORTANTE: Espandi PRIMA le relazioni, così i campi espansi possono essere inclusi nel filtro
        if (! empty($expandRelations)) {
            $payload = $this->expandRelations($payload, $expandRelations, $expandNested);
        }

        if (! empty($reverseSelections)) {
            $payload = $this->appendReverseRelations($payload, $reverseSelections);
        }

        // Poi filtra i campi da includere (PRIMA di filtrare le relazioni, così le relazioni vengono preservate)
        if (! empty($includeFields)) {
            $payload = $this->includeOnly($payload, $includeFields, $relationFields, $relationMetaMap);
        }

        // Filtra i campi delle relazioni selezionati (DOPO includeOnly, così le relazioni sono già nel risultato)
        if (! empty($relationFields)) {
            $payload = $this->filterRelationFields($payload, $relationFields, $relationMetaMap);
        }

        // Infine rimuovi i campi esclusi
        if (! empty($excludeFields)) {
            $payload = $this->excludeFields($payload, $excludeFields);
        }

        return $payload;
    }

    /**
     * @param  array<int, array{meta: array, fields: array<string>}>  $reverseSelections
     */
    protected function appendReverseRelations(array $payload, array $reverseSelections): array
    {
        if (empty($reverseSelections)) {
            return $payload;
        }

        foreach ($reverseSelections as $selection) {
            $meta = $selection['meta'] ?? [];
            $parentKey = $meta['parent_property'] ?? null;
            $alias = $meta['alias'] ?? null;
            $foreignKey = $meta['foreign_key'] ?? null;
            // Per le relazioni inverse, model_class contiene il modello sorgente (es: EquipmentLoan)
            $modelClass = $meta['model_class'] ?? null;

            if (! $parentKey || ! $alias || ! $foreignKey || ! $modelClass) {
                continue;
            }

            if (
                ! isset($payload[$parentKey]) ||
                ! is_array($payload[$parentKey]) ||
                ! isset($payload[$parentKey]['id'])
            ) {
                continue;
            }

            $parentId = $payload[$parentKey]['id'];
            $records = $this->fetchReverseRelationRecords($modelClass, $foreignKey, $parentId, $meta['expand'] ?? []);

            $payload[$parentKey][$alias] = $records;
        }

        return $payload;
    }

    protected function filterReverseRelationFields(array $payload, array $meta, array $selectedFields): array
    {
        $parentKey = $meta['parent_property'] ?? null;
        $alias = $meta['alias'] ?? null;

        if (! $parentKey || ! $alias) {
            return $payload;
        }

        if (
            ! isset($payload[$parentKey][$alias]) ||
            ! is_array($payload[$parentKey][$alias])
        ) {
            return $payload;
        }

        $records = $payload[$parentKey][$alias];
        $fieldKeys = [];
        $nestedFields = []; // Campi annidati: ['unit' => ['inventory_code', 'serial_number'], 'unit.model' => ['name']]

        foreach ($selectedFields as $field) {
            $parts = explode('.', $field);
            if (count($parts) >= 2 && $parts[0] === $alias) {
                if (count($parts) === 2) {
                    // Campo principale (es: equipment_loan.id)
                    $fieldKeys[] = $parts[1];
                } else {
                    // Campo annidato (es: equipment_loan.unit.inventory_code)
                    $relationPath = implode('.', array_slice($parts, 1, -1)); // unit o unit.model
                    $fieldName = $parts[count($parts) - 1]; // inventory_code o name
                    if (! isset($nestedFields[$relationPath])) {
                        $nestedFields[$relationPath] = [];
                    }
                    if (! in_array($fieldName, $nestedFields[$relationPath])) {
                        $nestedFields[$relationPath][] = $fieldName;
                    }
                }
            }
        }


        // Se non ci sono campi selezionati, usa i campi essenziali del modello correlato
        if (empty($fieldKeys)) {
            $modelClass = $meta['reverse_source_model'] ?? $meta['model_class'] ?? null;
            if ($modelClass && class_exists($modelClass)) {
                $registry = app(SignalModelRegistry::class);
                $modelFields = $registry->getFields($modelClass);
                if ($modelFields && isset($modelFields['essential'])) {
                    $fieldKeys = array_keys($modelFields['essential']);
                } else {
                    // Fallback: includi almeno i campi comuni
                    $fieldKeys = ['id', 'name', 'created_at', 'updated_at'];
                }
            } else {
                // Fallback: includi almeno id e name se disponibili
                $fieldKeys = ['id', 'name'];
            }
        }

        // Assicurati che 'id' sia sempre incluso
        if (! in_array('id', $fieldKeys)) {
            $fieldKeys[] = 'id';
        }

        $payload[$parentKey][$alias] = array_map(function ($record) use ($fieldKeys, $nestedFields) {
            if (! is_array($record)) {
                return $record;
            }

            // Filtra solo i campi richiesti del record principale
            $filtered = [];
            foreach ($fieldKeys as $key) {
                if (Arr::has($record, $key)) {
                    Arr::set($filtered, $key, Arr::get($record, $key));
                }
            }

            // Filtra i campi delle relazioni annidate se sono stati selezionati
            // Ordina i path per lunghezza (prima i più corti) per processare correttamente le relazioni annidate
            $sortedPaths = array_keys($nestedFields);
            usort($sortedPaths, fn($a, $b) => substr_count($a, '.') <=> substr_count($b, '.'));
            
            foreach ($sortedPaths as $relationPath) {
                $selectedFields = $nestedFields[$relationPath];
                $pathParts = explode('.', $relationPath);
                
                // Verifica che la relazione esista nel record
                $currentData = $record;
                $exists = true;
                foreach ($pathParts as $part) {
                    if (! isset($currentData[$part]) || ! is_array($currentData[$part])) {
                        $exists = false;
                        break;
                    }
                    $currentData = $currentData[$part];
                }
                
                if (! $exists) {
                    continue;
                }
                
                // Ottieni il valore corrente nel record filtrato (se esiste)
                $currentFiltered = Arr::get($filtered, $relationPath, []);
                if (! is_array($currentFiltered)) {
                    $currentFiltered = [];
                }
                
                // Aggiungi solo i campi selezionati
                foreach ($selectedFields as $field) {
                    if (isset($currentData[$field])) {
                        $currentFiltered[$field] = $currentData[$field];
                    }
                }
                
                // Imposta il valore filtrato solo se ci sono campi
                if (! empty($currentFiltered)) {
                    Arr::set($filtered, $relationPath, $currentFiltered);
                }
            }

            return $filtered;
        }, $records);

        return $payload;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchReverseRelationRecords(string $modelClass, string $foreignKey, mixed $parentId, array $expand = []): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        try {
            $query = $modelClass::query()->where($foreignKey, $parentId);

            // Se non ci sono relazioni da espandere specificate, prova a leggere quelle configurate nel modello
            if (empty($expand)) {
                $registry = app(SignalModelRegistry::class);
                $modelFields = $registry->getFields($modelClass);
                if ($modelFields && isset($modelFields['relations'])) {
                    foreach ($modelFields['relations'] as $relationName => $relationConfig) {
                        $relationExpand = $relationConfig['expand'] ?? [];
                        if (! empty($relationExpand)) {
                            // Costruisci il path completo per l'espansione (es: unit.model, unit.brand, unit.type)
                            // Laravel caricherà automaticamente anche la relazione principale (unit)
                            foreach ($relationExpand as $nestedRelation) {
                                $expandPath = "{$relationName}.{$nestedRelation}";
                                if (! in_array($expandPath, $expand)) {
                                    $expand[] = $expandPath;
                                }
                            }
                        }
                    }
                }
            }

            if (! empty($expand)) {
                $query->with($expand);
            }

            $records = $query->get()->map(fn ($model) => $model->toArray())->all();

            return $records;
        } catch (\Throwable $exception) {
            return [];
        }
    }

    /**
     * Include solo i campi specificati, preservando le relazioni espanse
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string>  $fields
     * @param  array<string, array<string>>  $relationFields  Campi delle relazioni selezionati
     * @param  array<string, array>  $relationMetaMap  Metadati delle relazioni indicizzati per form_key
     * @return array<string, mixed>
     */
    protected function includeOnly(array $payload, array $fields, array $relationFields = [], array $relationMetaMap = []): array
    {
        $result = [];

        // Raccogli tutti i campi root (non annidati) e quelli annidati
        $rootFields = [];
        $nestedFields = [];

        foreach ($fields as $field) {
            if (str_contains($field, '.')) {
                $nestedFields[] = $field;
            } else {
                $rootFields[] = $field;
            }
        }

        // Se ci sono campi root, includili (es: previousStatus, currentStatus)
        foreach ($rootFields as $field) {
            if (Arr::has($payload, $field)) {
                $value = Arr::get($payload, $field);
                Arr::set($result, $field, $value);
            }
        }

        // Se ci sono campi annidati (es: loan.id, loan.status), costruisci l'oggetto loan
        if (! empty($nestedFields)) {
            $parentKeys = [];
            foreach ($nestedFields as $field) {
                $parts = explode('.', $field);
                if (count($parts) >= 2) {
                    $parentKey = $parts[0]; // es: loan
                    $parentKeys[$parentKey] = true;
                }
            }

            // Per ogni parent key (es: loan), costruisci l'oggetto con i campi selezionati
            foreach ($parentKeys as $parentKey => $_) {
                if (! isset($payload[$parentKey]) || ! is_array($payload[$parentKey])) {
                    continue;
                }

                $parentData = $payload[$parentKey];
                $filteredParent = [];

                // Includi i campi selezionati per questo parent
                foreach ($nestedFields as $field) {
                    $parts = explode('.', $field);
                    if (count($parts) >= 2 && $parts[0] === $parentKey) {
                        $fieldPath = implode('.', array_slice($parts, 1));
                        if (Arr::has($parentData, $fieldPath)) {
                            $value = Arr::get($parentData, $fieldPath);
                            Arr::set($filteredParent, $fieldPath, $value);
                        }
                    }
                }

                // IMPORTANTE: Preserva le relazioni espanse dentro il parent se ci sono relationFields configurati
                // Le relazioni sono già state filtrate da filterRelationFields, quindi includile così come sono
                if (! empty($relationFields)) {
                    $relationsWithFields = [];
                    foreach ($relationFields as $formKey => $selectedFields) {
                        if (empty($selectedFields) || ! is_array($selectedFields)) {
                            continue;
                        }

                        $meta = $relationMetaMap[$formKey] ?? null;
                        if ($meta) {
                            if (($meta['parent_property'] ?? null) !== $parentKey) {
                                continue;
                            }

                            $alias = $meta['alias'] ?? null;
                            if (! $alias) {
                                continue;
                            }

                            $relationsWithFields[$alias] = true;
                            continue;
                        }

                        [$legacyParent, $legacyRelation] = $this->parseLegacyRelationKey($formKey);

                        if ($legacyParent === $parentKey && $legacyRelation) {
                            $relationsWithFields[$legacyRelation] = true;
                        }
                    }

                    foreach (array_keys($relationsWithFields) as $relationName) {
                        if (isset($parentData[$relationName])) {
                            $filteredParent[$relationName] = $parentData[$relationName];
                        }
                    }
                }

                $result[$parentKey] = $filteredParent;
            }
        }

        return $result;
    }

    /**
     * Esclude i campi specificati
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string>  $fields
     * @return array<string, mixed>
     */
    protected function excludeFields(array $payload, array $fields): array
    {
        foreach ($fields as $field) {
            Arr::forget($payload, $field);
        }

        return $payload;
    }

    /**
     * Filtra i campi delle relazioni in base alle selezioni dell'utente
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, array<string>>  $relationFields  Array di [relation_id => [field1, field2, ...]]
     * @return array<string, mixed>
     */
    protected function filterRelationFields(array $payload, array $relationFields, array $relationMetaMap = []): array
    {
        foreach ($relationFields as $formKey => $selectedFields) {
            if (! is_array($selectedFields)) {
                continue;
            }

            $meta = $relationMetaMap[$formKey] ?? null;
            if ($meta) {
                if (($meta['mode'] ?? 'direct') === 'reverse') {
                    // Per le relazioni inverse, gestiamo anche il caso con selectedFields vuoto
                    $payload = $this->filterReverseRelationFields($payload, $meta, $selectedFields);

                    continue;
                }

                $parentKey = $meta['parent_property'] ?? null;
                $alias = $meta['alias'] ?? null;

                if (! $parentKey || ! $alias) {
                    continue;
                }

                if (! isset($payload[$parentKey][$alias]) || ! is_array($payload[$parentKey][$alias])) {
                    continue;
                }

                $relationData = $payload[$parentKey][$alias];
                $fieldKeys = [];

                // Se non ci sono campi selezionati, usa i campi essenziali del modello correlato
                if (empty($selectedFields)) {
                    $modelClass = $meta['model_class'] ?? null;
                    if ($modelClass && class_exists($modelClass)) {
                        $registry = app(SignalModelRegistry::class);
                        $modelFields = $registry->getFields($modelClass);
                        if ($modelFields && isset($modelFields['essential'])) {
                            $fieldKeys = array_keys($modelFields['essential']);
                        } else {
                            // Fallback: includi almeno i campi comuni
                            $fieldKeys = ['id', 'name', 'created_at', 'updated_at'];
                        }
                    } else {
                        // Fallback: includi almeno id e name se disponibili
                        $fieldKeys = ['id', 'name'];
                    }
                } else {
                    // Estrai i field keys dai selectedFields
                    foreach ($selectedFields as $field) {
                        $fieldParts = explode('.', $field);
                        if (count($fieldParts) === 2 && $fieldParts[0] === $alias) {
                            $fieldKeys[] = $fieldParts[1];
                        }
                    }
                }

                // Assicurati che 'id' sia sempre incluso
                if (! in_array('id', $fieldKeys)) {
                    $fieldKeys[] = 'id';
                }

                $filtered = [];
                foreach ($fieldKeys as $fieldKey) {
                    if (Arr::has($relationData, $fieldKey)) {
                        Arr::set($filtered, $fieldKey, Arr::get($relationData, $fieldKey));
                    }
                }

                $payload[$parentKey][$alias] = $filtered;

                continue;
            }

            [$parentKey, $relationName] = $this->parseLegacyRelationKey($formKey);

            if (! $parentKey || ! $relationName) {
                continue;
            }

            if (! isset($payload[$parentKey][$relationName]) || ! is_array($payload[$parentKey][$relationName])) {
                continue;
            }

            $relationData = $payload[$parentKey][$relationName];
            $filtered = [];

            foreach ($selectedFields as $field) {
                $fieldParts = explode('.', $field);
                if (count($fieldParts) === 2 && $fieldParts[0] === $relationName) {
                    $fieldKey = $fieldParts[1];
                    if (isset($relationData[$fieldKey])) {
                        $filtered[$fieldKey] = $relationData[$fieldKey];
                    }
                }
            }

            if (isset($relationData['id'])) {
                $filtered['id'] = $relationData['id'];
            }
            if (isset($relationData['name'])) {
                $filtered['name'] = $relationData['name'];
            }

            $payload[$parentKey][$relationName] = $filtered;
        }

        return $payload;
    }

    protected function parseLegacyRelationKey(string $key): array
    {
        $originalIdField = str_contains($key, '.') ? $key : str_replace('_', '.', $key);
        $parts = explode('.', $originalIdField);

        if (count($parts) < 2) {
            return [null, null];
        }

        $parentKey = $parts[0];
        $relationName = str_replace('_id', '', $parts[1]);

        return [$parentKey, $relationName];
    }

    /**
     * Espande le relazioni sostituendo gli ID con i dati completi
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $relations  Array di mapping: ['field_id' => 'ModelClass'] o ['loan.unit_id' => 'ModelClass']
     * @param  array<string, array<string>>  $expandNested  Array di relazioni annidate da espandere: ['loan.unit_id' => ['model', 'brand']]
     * @return array<string, mixed>
     */
    protected function expandRelations(array $payload, array $relations, array $expandNested = []): array
    {
        if (empty($relations)) {
            return $payload;
        }

        // Separa le relazioni root da quelle annidate
        $rootRelations = [];
        $nestedRelations = [];

        foreach ($relations as $idField => $modelClass) {
            if (str_contains($idField, '.')) {
                $nestedRelations[$idField] = $modelClass;
            } else {
                $rootRelations[$idField] = $modelClass;
            }
        }

        // Espandi le relazioni a livello root
        $this->recursiveExpand($payload, $rootRelations, $expandNested);

        // Espandi le relazioni annidate
        foreach ($nestedRelations as $nestedIdField => $modelClass) {
            $parts = explode('.', $nestedIdField);
            $idField = array_pop($parts);
            $path = implode('.', $parts);

            // Naviga nel payload fino al punto giusto
            $target = &$payload;
            foreach ($parts as $part) {
                if (! isset($target[$part]) || ! is_array($target[$part])) {
                    continue 2; // Salta questa relazione se il percorso non esiste
                }
                $target = &$target[$part];
            }

            // Se il campo ID esiste e ha un valore, espandi la relazione
            if (isset($target[$idField]) && $target[$idField] && is_numeric($target[$idField])) {
                $relationField = str_replace('_id', '', $idField);

                if (! isset($target[$relationField]) || ! is_array($target[$relationField]) || ! isset($target[$relationField]['name'])) {
                    try {
                        if (class_exists($modelClass)) {
                            $model = $modelClass::find($target[$idField]);
                            if ($model) {
                                $target[$relationField] = [
                                    'id' => $model->id,
                                    'name' => $this->getModelName($model),
                                    ...$model->only($this->getEssentialFields($modelClass)),
                                ];

                                // Se ci sono relazioni annidate da espandere, espandile
                                if (isset($expandNested[$nestedIdField]) && ! empty($expandNested[$nestedIdField])) {
                                    $this->expandNestedRelationsInModel($target[$relationField], $expandNested[$nestedIdField], $modelClass);
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        // Ignora errori
                    }
                }
            }
        }

        return $payload;
    }

    /**
     * Espande le relazioni annidate in un modello già caricato
     */
    protected function expandNestedRelationsInModel(array &$modelData, array $relationsToExpand, string $parentModelClass): void
    {
        foreach ($relationsToExpand as $relationName) {
            $idField = $relationName . '_id';

            if (! isset($modelData[$idField]) || ! $modelData[$idField]) {
                continue;
            }

            // Indovina la classe del modello
            $modelClass = $this->guessModelClass($idField, $parentModelClass);

            if (! $modelClass || ! class_exists($modelClass)) {
                continue;
            }

            try {
                $relatedModel = $modelClass::find($modelData[$idField]);
                if ($relatedModel) {
                    $modelData[$relationName] = [
                        'id' => $relatedModel->id,
                        'name' => $this->getModelName($relatedModel),
                        ...$relatedModel->only($this->getEssentialFields($modelClass)),
                    ];
                }
            } catch (\Throwable $e) {
                // Ignora errori
            }
        }
    }

    /**
     * Indovina la classe del modello da un campo ID
     */
    protected function guessModelClass(string $idField, string $parentModelClass): ?string
    {
        $relationName = str_replace('_id', '', $idField);
        $parentNamespace = substr($parentModelClass, 0, strrpos($parentModelClass, '\\'));

        $possibleNames = [
            ucfirst($relationName),
            ucfirst(str_replace('_', '', ucwords($relationName, '_'))),
        ];

        foreach ($possibleNames as $name) {
            $possibleClass = $parentNamespace . '\\' . $name;
            if (class_exists($possibleClass) && is_subclass_of($possibleClass, Model::class)) {
                return $possibleClass;
            }
        }

        // Prova a ottenere il modello correlato usando reflection sulla relazione
        // Questo è completamente agnostico
        try {
            if (class_exists($parentModelClass) && is_subclass_of($parentModelClass, Model::class)) {
                $model = new $parentModelClass;
                if (method_exists($model, $relationName)) {
                    $relation = $model->{$relationName}();
                    if (method_exists($relation, 'getRelated')) {
                        $relatedModel = $relation->getRelated();

                        return get_class($relatedModel);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignora errori
        }

        // Fallback: solo User (modello standard Laravel) per nomi comuni
        $commonUserRelations = ['user', 'borrower', 'loaner', 'author', 'creator', 'owner'];
        if (in_array(strtolower($relationName), $commonUserRelations)) {
            if (class_exists(\App\Models\User::class)) {
                return \App\Models\User::class;
            }
        }

        foreach ($commonModels as $key => $class) {
            if (stripos($relationName, strtolower($key)) !== false && class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Espande ricorsivamente i campi ID nel payload
     *
     * @param  array<string, string>  $relations
     * @param  array<string, array<string>>  $expandNested
     */
    protected function recursiveExpand(mixed &$data, array $relations, array $expandNested = []): void
    {
        if (! is_array($data)) {
            return;
        }

        foreach ($data as $key => &$value) {
            // Cerca se questo campo è una relazione da espandere
            if (str_ends_with($key, '_id') && isset($relations[$key]) && $value && is_numeric($value)) {
                $modelClass = $relations[$key];
                $relationField = str_replace('_id', '', $key);

                // Se la relazione non è già presente o non è un array completo
                if (! isset($data[$relationField]) || ! is_array($data[$relationField]) || ! isset($data[$relationField]['name'])) {
                    try {
                        if (class_exists($modelClass)) {
                            $model = $modelClass::find($value);
                            if ($model) {
                                $data[$relationField] = [
                                    'id' => $model->id,
                                    'name' => $this->getModelName($model),
                                    ...$model->only($this->getEssentialFields($modelClass)),
                                ];

                                // Espandi ricorsivamente le relazioni annidate
                                $this->recursiveExpand($data[$relationField], $relations);
                            }
                        }
                    } catch (\Throwable $e) {
                        // Ignora errori
                    }
                } else {
                    // Se la relazione è già presente, espandi le sue relazioni annidate
                    $this->recursiveExpand($data[$relationField], $relations);
                }
            } elseif (is_array($value)) {
                // Continua la ricerca ricorsiva
                $this->recursiveExpand($value, $relations);
            }
        }
    }

    /**
     * Espande le relazioni annidate in un modello già caricato
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $relations
     * @return array<string, mixed>
     */
    protected function expandNestedRelations(array $data, string $modelClass, array $relations): array
    {
        // Cerca campi ID nel modello e espandili se sono nelle relazioni configurate
        foreach ($data as $key => $value) {
            if (str_ends_with($key, '_id') && $value) {
                $relationKey = str_replace('_id', '', $key);
                $nestedModelClass = $relations[$key] ?? null;

                // Se la relazione non è già caricata come oggetto
                if (! isset($data[$relationKey]) || ! is_array($data[$relationKey])) {
                    if ($nestedModelClass && class_exists($nestedModelClass)) {
                        try {
                            $model = $nestedModelClass::find($value);
                            if ($model) {
                                $data[$relationKey] = [
                                    'id' => $model->id,
                                    'name' => $this->getModelName($model),
                                    ...$model->only($this->getEssentialFields($nestedModelClass)),
                                ];

                                // Espandi ricorsivamente le relazioni annidate
                                $data[$relationKey] = $this->expandNestedRelations($data[$relationKey], $nestedModelClass, $relations);
                            }
                        } catch (\Throwable $e) {
                            // Ignora errori
                        }
                    }
                } else {
                    // Se la relazione è già caricata, espandi le sue relazioni annidate
                    $data[$relationKey] = $this->expandNestedRelations($data[$relationKey], $nestedModelClass ?? '', $relations);
                }
            }
        }

        return $data;
    }

    /**
     * Ottiene il nome del modello (cerca 'name', 'title', 'label', ecc.)
     */
    protected function getModelName(Model $model): ?string
    {
        $nameFields = ['name', 'title', 'label', 'description', 'short_description'];

        foreach ($nameFields as $field) {
            if (isset($model->$field)) {
                return $model->$field;
            }
        }

        return null;
    }

    /**
     * Ottiene i campi essenziali per un modello
     *
     * @return array<string>
     */
    protected function getEssentialFields(string $modelClass): array
    {
        // Campi essenziali comuni
        $common = ['id', 'name', 'title', 'label', 'description', 'short_description', 'status', 'created_at', 'updated_at'];

        // Campi specifici per tipo di modello (solo generici)
        $specific = match (true) {
            str_contains($modelClass, 'User') => ['name', 'email'],
            default => [],
        };

        return array_unique(array_merge($common, $specific));
    }
}

