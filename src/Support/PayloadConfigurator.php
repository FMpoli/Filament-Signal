<?php

namespace Voodflow\Voodflow\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class PayloadConfigurator
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

        // IMPORTANTE: Filtra PRIMA le relazioni usando getSignalFields(), POI espandi solo se necessario
        // Questo evita che expandRelations sovrascriva i campi già caricati con solo i campi essenziali

        // Se includeFields è vuoto, usa i campi essenziali dal modello principale (se disponibile)
        if (empty($includeFields)) {
            // Cerca il modello principale nel payload (es: 'loan' -> EquipmentLoan)
            $mainModelClass = $this->findMainModelClass($payload, $relationMetaMap);
            if ($mainModelClass) {
                $registry = app(ModelRegistry::class);
                $modelFields = $registry->getFields($mainModelClass);
                if ($modelFields && isset($modelFields['essential'])) {
                    // Estrai i nomi dei campi (gestisci sia array associativi che numerici)
                    $essentialFieldNames = [];
                    foreach ($modelFields['essential'] as $key => $value) {
                        if (is_int($key)) {
                            $essentialFieldNames[] = $value;
                        } else {
                            $essentialFieldNames[] = $key;
                        }
                    }
                    // Costruisci includeFields con i campi essenziali (es: ['loan.id', 'loan.status', ...])
                    $mainKey = $this->findMainKey($payload);
                    if ($mainKey) {
                        foreach ($essentialFieldNames as $fieldName) {
                            $includeFields[] = $mainKey . '.' . $fieldName;
                        }
                        // Aggiungi anche i campi root dell'evento (es: previousStatus, currentStatus)
                        foreach (['previousStatus', 'currentStatus'] as $rootField) {
                            if (isset($payload[$rootField])) {
                                $includeFields[] = $rootField;
                            }
                        }
                    }
                }
            }
        }

        // IMPORTANTE: Se ci sono relazioni inverse, salva gli id originali PRIMA di filtrare il payload
        // perché appendReverseRelations() ne ha bisogno per recuperare i record correlati
        $originalIds = [];
        if (! empty($reverseSelections)) {
            foreach ($reverseSelections as $selection) {
                $meta = $selection['meta'] ?? [];
                $parentKey = $meta['parent_property'] ?? null;
                if ($parentKey && isset($payload[$parentKey]['id'])) {
                    $originalIds[$parentKey] = $payload[$parentKey]['id'];
                }
            }

            // Assicurati che 'id' sia sempre incluso nei includeFields quando ci sono relazioni inverse
            $mainKey = $this->findMainKey($payload);
            if ($mainKey && ! empty($includeFields)) {
                $idField = $mainKey . '.id';
                if (! in_array($idField, $includeFields)) {
                    $includeFields[] = $idField;
                }
            } elseif ($mainKey && empty($includeFields)) {
                // Se includeFields è vuoto, aggiungi almeno id
                $includeFields[] = $mainKey . '.id';
            }
        }

        // Poi filtra i campi da includere (PRIMA di filtrare le relazioni, così le relazioni vengono preservate)
        if (! empty($includeFields)) {
            $payload = $this->includeOnly($payload, $includeFields, $relationFields, $relationMetaMap);
        }

        // IMPORTANTE: Aggiungi PRIMA le relazioni inverse al payload, così possono essere filtrate successivamente
        // Le relazioni inverse devono essere aggiunte prima di filterRelationFields() perché altrimenti
        // filterReverseRelationFields() non trova i record da filtrare
        if (! empty($reverseSelections)) {
            $payload = $this->appendReverseRelations($payload, $reverseSelections, $originalIds);
        }

        // Filtra i campi delle relazioni selezionati PRIMA di expandRelations
        // così preserviamo i campi già caricati (inventory_code, serial_number, ecc.)
        // Questo include anche il filtraggio delle relazioni inverse appena aggiunte
        if (! empty($relationFields)) {
            $payload = $this->filterRelationFields($payload, $relationFields, $relationMetaMap);
        }

        // POI espandi le relazioni, ma solo se necessario (non sovrascrivere i dati già filtrati)
        // IMPORTANTE: expandRelations NON deve sovrascrivere le relazioni già filtrate da filterRelationFields
        // Se una relazione è già presente con più di solo 'id', significa che è già stata filtrata
        // e non deve essere espansa (perché espandere sovrascriverebbe i dati filtrati con solo i campi essenziali)
        // IMPORTANTE: Se expandRelations è vuoto ma ci sono relazioni configurate in getSignalFields(),
        // dobbiamo comunque espandere le relazioni annidate (es: unit.model, unit.brand, unit.type)
        if (! empty($expandRelations)) {
            $payload = $this->expandRelations($payload, $expandRelations, $expandNested);
        } elseif (! empty($relationFields)) {
            // Se expandRelations è vuoto ma ci sono relationFields, prova a espandere le relazioni annidate
            // usando la configurazione da getSignalFields()
            $payload = $this->expandNestedRelationsFromConfig($payload, $relationFields, $relationMetaMap);
        }

        // Infine rimuovi i campi esclusi
        if (! empty($excludeFields)) {
            $payload = $this->excludeFields($payload, $excludeFields);
        }

        return $payload;
    }

    /**
     * @param  array<int, array{meta: array, fields: array<string>}>  $reverseSelections
     * @param  array<string, mixed>  $originalIds  Array di [parentKey => id] per recuperare l'id se non è nel payload filtrato
     */
    protected function appendReverseRelations(array $payload, array $reverseSelections, array $originalIds = []): array
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

            if (! isset($payload[$parentKey]) || ! is_array($payload[$parentKey])) {
                continue;
            }

            // Prova a recuperare l'id dal payload filtrato, altrimenti usa quello originale
            $parentId = $payload[$parentKey]['id'] ?? $originalIds[$parentKey] ?? null;

            if (! $parentId) {
                continue;
            }

            // Determina le relazioni da espandere: usa quelle specificate nel meta, o quelle configurate nel modello
            $expand = $meta['expand'] ?? [];

            // Se expand è vuoto, prova a leggere le relazioni da espandere da getSignalFields() del modello correlato
            if (empty($expand) && $modelClass) {
                $registry = app(ModelRegistry::class);
                $modelFields = $registry->getFields($modelClass);
                if ($modelFields && isset($modelFields['relations'])) {
                    foreach ($modelFields['relations'] as $relationName => $relationConfig) {
                        $relationExpand = $relationConfig['expand'] ?? [];
                        if (! empty($relationExpand)) {
                            // Costruisci il path completo per l'espansione (es: unit.model, unit.brand, unit.type)
                            foreach ($relationExpand as $nestedRelation) {
                                $expandPath = "{$relationName}.{$nestedRelation}";
                                if (! in_array($expandPath, $expand)) {
                                    $expand[] = $expandPath;
                                }
                            }
                            // Aggiungi anche la relazione principale se non è già presente
                            if (! in_array($relationName, $expand)) {
                                $expand[] = $relationName;
                            }
                        }
                    }
                }
            }

            $records = $this->fetchReverseRelationRecords($modelClass, $foreignKey, $parentId, $expand);

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
                $registry = app(ModelRegistry::class);
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
            usort($sortedPaths, fn ($a, $b) => substr_count($a, '.') <=> substr_count($b, '.'));

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
                $registry = app(ModelRegistry::class);
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

                // USA LA CONFIGURAZIONE DA getSignalFields() DEL MODELLO PADRE invece dei campi selezionati nell'UI
                // Questo funziona come "model integration" - la configurazione è nel codice, non nell'UI
                // Il modello padre è quello che ha la relazione (es: EquipmentLoan), non il modello correlato (es: EquipmentUnit)
                $parentModelClass = $meta['parent_model_class'] ?? null;

                // FALLBACK: Se parent_model_class non è nel meta, prova a ottenerlo dal payload
                // Il parentKey (es: 'loan') dovrebbe corrispondere al modello principale dell'evento
                if (! $parentModelClass && isset($payload[$parentKey]) && isset($payload[$parentKey]['id'])) {
                    // Prova a ottenere il modello padre dal config o dal payload stesso
                    // Per ora, assumiamo che il modello padre sia quello che ha getSignalFields() configurato
                    // e che la chiave nel payload corrisponda al nome del modello (es: 'loan' -> EquipmentLoan)
                }

                $fieldKeys = [];
                $nestedFields = [];

                // IMPORTANTE: Usa SOLO i campi selezionati nella Payload Configuration
                // La Model Integration definisce i campi DISPONIBILI, ma la Payload Configuration decide quali INCLUDERE
                // Se non ci sono campi selezionati, non includere quella relazione (o solo 'id' se necessario)
                if (! empty($selectedFields)) {
                    // Estrai i campi selezionati dall'UI
                    // I campi possono essere in formato:
                    // - "parentKey.alias.field" (es: "blog_post.author.name")
                    // - "alias.field" (es: "author.name")
                    // - "field" (es: "name")
                    foreach ($selectedFields as $field) {
                        $parts = explode('.', $field);

                        // Rimuovi il parentKey se presente (es: "blog_post.author.name" -> "author.name")
                        if (count($parts) >= 3 && $parts[0] === $parentKey) {
                            $parts = array_slice($parts, 1);
                        }

                        if (count($parts) === 1) {
                            // Campo diretto della relazione (es: "name", "email")
                            if (! in_array($field, $fieldKeys)) {
                                $fieldKeys[] = $field;
                            }
                        } elseif (count($parts) === 2 && $parts[0] === $alias) {
                            // Campo diretto della relazione quando è specificato con l'alias (es: "author.name")
                            $nestedField = $parts[1];
                            if (! in_array($nestedField, $fieldKeys)) {
                                $fieldKeys[] = $nestedField;
                            }
                        } elseif (count($parts) >= 2) {
                            // Campo di una relazione annidata (es: "author.photo" quando alias è "author" e c'è una relazione "photo")
                            // Oppure campo di una relazione diversa (es: "category.name" quando alias è "author")
                            $firstPart = $parts[0];

                            if ($firstPart === $alias) {
                                // Se la prima parte è l'alias, potrebbe essere un campo diretto o una relazione annidata
                                if (count($parts) === 2) {
                                    // Campo diretto (es: "author.name")
                                    $nestedField = $parts[1];
                                    if (! in_array($nestedField, $fieldKeys)) {
                                        $fieldKeys[] = $nestedField;
                                    }
                                } else {
                                    // Relazione annidata (es: "author.photo.url")
                                    $nestedRelation = $parts[1];
                                    $nestedField = $parts[2] ?? null;
                                    if ($nestedField) {
                                        if (! isset($nestedFields[$nestedRelation])) {
                                            $nestedFields[$nestedRelation] = [];
                                        }
                                        if (! in_array($nestedField, $nestedFields[$nestedRelation])) {
                                            $nestedFields[$nestedRelation][] = $nestedField;
                                        }
                                    }
                                }
                            } else {
                                // Relazione diversa (non gestita qui, sarà gestita quando processiamo quella relazione)
                                // Per ora, ignoriamo
                            }
                        }
                    }
                }

                // Se non ci sono campi selezionati, non includere la relazione
                // La Model Integration definisce solo cosa è DISPONIBILE, non cosa viene incluso
                // La Payload Configuration decide quali campi includere nel payload finale
                if (empty($fieldKeys) && empty($nestedFields)) {
                    // Se non ci sono campi selezionati, rimuovi completamente la relazione dal payload
                    unset($payload[$parentKey][$alias]);

                    continue;
                }

                // Assicurati che 'id' sia sempre incluso
                if (! in_array('id', $fieldKeys)) {
                    $fieldKeys[] = 'id';
                }

                // Filtra i campi diretti - IMPORTANTE: seleziona SOLO i campi in fieldKeys
                // Se fieldKeys è vuoto (solo 'id'), allora includiamo solo 'id'
                // NON includere altri campi che non sono in fieldKeys
                $filtered = [];
                foreach ($fieldKeys as $fieldKey) {
                    if (Arr::has($relationData, $fieldKey)) {
                        Arr::set($filtered, $fieldKey, Arr::get($relationData, $fieldKey));
                    }
                }

                // IMPORTANTE: Se fieldKeys contiene solo 'id', allora filtered dovrebbe contenere solo 'id'
                // Se filtered contiene altri campi, significa che qualcosa non va

                // Filtra le relazioni annidate se necessario
                foreach ($nestedFields as $nestedRelation => $nestedFieldKeys) {
                    // Verifica se la relazione annidata è già presente nel payload
                    if (isset($relationData[$nestedRelation]) && is_array($relationData[$nestedRelation])) {
                        $nestedFiltered = [];
                        foreach ($nestedFieldKeys as $nestedFieldKey) {
                            if (Arr::has($relationData[$nestedRelation], $nestedFieldKey)) {
                                Arr::set($nestedFiltered, $nestedFieldKey, Arr::get($relationData[$nestedRelation], $nestedFieldKey));
                            }
                        }
                        // Assicurati che 'id' sia sempre incluso per le relazioni annidate
                        if (! isset($nestedFiltered['id']) && isset($relationData[$nestedRelation]['id'])) {
                            $nestedFiltered['id'] = $relationData[$nestedRelation]['id'];
                        }
                        if (! empty($nestedFiltered)) {
                            $filtered[$nestedRelation] = $nestedFiltered;
                        }
                    } elseif (isset($relationData[$nestedRelation . '_id']) && $relationData[$nestedRelation . '_id']) {
                        // Se la relazione non è caricata ma c'è l'ID, prova a caricarla
                        // Questo può accadere se le relazioni annidate non sono state espanse
                        $nestedId = $relationData[$nestedRelation . '_id'];
                        $relationModelClass = $meta['model_class'] ?? null;

                        if ($relationModelClass && class_exists($relationModelClass)) {
                            try {
                                // Usa reflection per ottenere la classe del modello correlato dalla relazione
                                $relationModel = new $relationModelClass;
                                if (method_exists($relationModel, $nestedRelation)) {
                                    $relation = $relationModel->{$nestedRelation}();
                                    if (method_exists($relation, 'getRelated')) {
                                        $nestedModelClass = get_class($relation->getRelated());

                                        if ($nestedModelClass && class_exists($nestedModelClass)) {
                                            $nestedModel = $nestedModelClass::find($nestedId);
                                            if ($nestedModel) {
                                                $nestedFiltered = [];
                                                foreach ($nestedFieldKeys as $nestedFieldKey) {
                                                    if ($nestedModel->offsetExists($nestedFieldKey)) {
                                                        $nestedFiltered[$nestedFieldKey] = $nestedModel->{$nestedFieldKey};
                                                    }
                                                }
                                                // Assicurati che 'id' sia sempre incluso
                                                if (! isset($nestedFiltered['id'])) {
                                                    $nestedFiltered['id'] = $nestedModel->id;
                                                }
                                                if (! empty($nestedFiltered)) {
                                                    $filtered[$nestedRelation] = $nestedFiltered;
                                                }
                                            }
                                        }
                                    }
                                }
                            } catch (\Throwable $e) {
                                // Ignora errori
                            }
                        }
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
        $this->recursiveExpand($payload, $rootRelations, $expandNested, '');

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

            // Se la relazione annidata è già presente con dati, preservala
            if (isset($modelData[$relationName]) && is_array($modelData[$relationName]) && count($modelData[$relationName]) > 1) {
                // La relazione è già caricata, non sovrascriverla
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
                    // IMPORTANTE: Usa i campi da getSignalFields() del modello PADRE (parentModelClass)
                    // perché la configurazione della relazione annidata è nel modello padre
                    // Es: quando espando unit.model, la configurazione è in EquipmentUnit::getSignalFields()['relations']['model']
                    $registry = app(ModelRegistry::class);
                    $parentModelFields = $registry->getFields($parentModelClass);
                    $fieldsToInclude = ['id'];

                    if ($parentModelFields && isset($parentModelFields['relations'][$relationName])) {
                        // Usa i campi configurati per questa relazione nel modello padre
                        $relationConfig = $parentModelFields['relations'][$relationName];
                        if (isset($relationConfig['fields']) && is_array($relationConfig['fields'])) {
                            foreach ($relationConfig['fields'] as $field) {
                                if (is_int($field)) {
                                    // Array numerico: il valore è il nome del campo
                                    $fieldsToInclude[] = $field;
                                } else {
                                    // Array associativo: la chiave è il nome del campo
                                    $fieldsToInclude[] = $field;
                                }
                            }
                        }
                    } else {
                        // Fallback: usa i campi essenziali
                        $fieldsToInclude = array_merge($fieldsToInclude, $this->getEssentialFields($modelClass));
                    }

                    // Rimuovi duplicati e assicurati che 'id' sia sempre incluso
                    $fieldsToInclude = array_unique($fieldsToInclude);
                    if (! in_array('id', $fieldsToInclude)) {
                        array_unshift($fieldsToInclude, 'id');
                    }

                    $modelData[$relationName] = [
                        'id' => $relatedModel->id,
                        'name' => $this->getModelName($relatedModel),
                        ...$relatedModel->only($fieldsToInclude),
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

        // Rimuoviamo questo codice che usa una variabile non definita
        // Se necessario, definire $commonModels prima di questo punto

        return null;
    }

    /**
     * Espande ricorsivamente i campi ID nel payload
     *
     * @param  array<string, string>  $relations
     * @param  array<string, array<string>>  $expandNested
     */
    protected function recursiveExpand(mixed &$data, array $relations, array $expandNested = [], string $currentPath = ''): void
    {
        if (! is_array($data)) {
            return;
        }

        foreach ($data as $key => &$value) {
            // Cerca se questo campo è una relazione da espandere
            if (str_ends_with($key, '_id') && isset($relations[$key]) && $value && is_numeric($value)) {
                $modelClass = $relations[$key];
                $relationField = str_replace('_id', '', $key);

                // Costruisci il path completo per il matching con expandNested
                $fullPath = $currentPath ? $currentPath . '.' . $key : $key;

                // Se la relazione non è già presente o è solo un ID
                $hasOnlyId = isset($data[$relationField]) && is_array($data[$relationField]) && count($data[$relationField]) === 1 && isset($data[$relationField]['id']);
                $notPresent = ! isset($data[$relationField]) || ! is_array($data[$relationField]);

                // IMPORTANTE: Se la relazione è già presente con più di solo 'id', NON espanderla
                // perché significa che è già stata filtrata da filterRelationFields o caricata con tutti i campi
                // Espandere qui sovrascriverebbe i dati già filtrati con solo i campi essenziali
                if ($notPresent || $hasOnlyId) {
                    // La relazione non esiste o è solo un ID, espandila completamente
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
                                $nextPath = $currentPath ? $currentPath . '.' . $relationField : $relationField;
                                $this->recursiveExpand($data[$relationField], $relations, $expandNested, $nextPath);
                            }
                        }
                    } catch (\Throwable $e) {
                        // Ignora errori
                    }
                } else {
                    // La relazione è già presente con dati (filtrata da filterRelationFields o caricata da loadEventRelationsForDispatch)
                    // PRESERVALA COMPLETAMENTE - NON SOVRASCRIVERLA!
                    // Non aggiungere 'name' o altri campi, i dati sono già corretti e completi
                    // Espandi solo le relazioni annidate se configurate (es: model, brand, type per unit)
                    $nestedToExpand = [];
                    foreach ($expandNested as $nestedIdField => $nestedRelations) {
                        // Se expandNested contiene 'loan.unit_id' => ['model', 'brand', 'type']
                        // e stiamo processando 'unit_id' in 'loan', usa quelle relazioni
                        if ($nestedIdField === $fullPath || $nestedIdField === $key || str_ends_with($nestedIdField, '.' . $key)) {
                            $nestedToExpand = $nestedRelations;

                            break;
                        }
                    }
                    if (! empty($nestedToExpand)) {
                        $this->expandNestedRelationsInModel($data[$relationField], $nestedToExpand, $modelClass);
                    }
                }
            } elseif (is_array($value)) {
                // Continua la ricerca ricorsiva
                $nextPath = $currentPath ? $currentPath . '.' . $key : $key;
                $this->recursiveExpand($value, $relations, $expandNested, $nextPath);
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

    /**
     * Trova la chiave principale nel payload (es: 'loan')
     *
     * @param  array<string, mixed>  $payload
     */
    protected function findMainKey(array $payload): ?string
    {
        // Cerca chiavi comuni che rappresentano il modello principale
        $commonKeys = ['loan', 'equipment_loan', 'user', 'equipment_unit'];

        foreach ($commonKeys as $key) {
            if (isset($payload[$key]) && is_array($payload[$key]) && isset($payload[$key]['id'])) {
                return $key;
            }
        }

        // Se non trovato, cerca la prima chiave che ha un array con 'id'
        foreach ($payload as $key => $value) {
            if (is_array($value) && isset($value['id']) && ! in_array($key, ['previousStatus', 'currentStatus', 'metadata'])) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Trova la classe del modello principale dal payload
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, array>  $relationMetaMap
     */
    protected function findMainModelClass(array $payload, array $relationMetaMap): ?string
    {
        $mainKey = $this->findMainKey($payload);
        if (! $mainKey) {
            return null;
        }

        // Cerca parent_model_class nel relationMetaMap
        foreach ($relationMetaMap as $meta) {
            $parentKey = $meta['parent_property'] ?? null;
            if ($parentKey === $mainKey) {
                return $meta['parent_model_class'] ?? null;
            }
        }

        // Fallback: prova a dedurre dal nome della chiave
        $modelClassMap = [
            // 'loan' => 'DetIT\\FilamentLabOps\\Models\\EquipmentLoan',
            // 'equipment_loan' => 'DetIT\\FilamentLabOps\\Models\\EquipmentLoan',
            // 'unit' => 'DetIT\\FilamentLabOps\\Models\\EquipmentUnit',
            // 'equipment_unit' => 'DetIT\\FilamentLabOps\\Models\\EquipmentUnit',
        ];

        return $modelClassMap[$mainKey] ?? null;
    }

    /**
     * Espande le relazioni annidate usando la configurazione da getSignalFields()
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, array<string>>  $relationFields
     * @param  array<string, array>  $relationMetaMap
     * @return array<string, mixed>
     */
    protected function expandNestedRelationsFromConfig(array $payload, array $relationFields, array $relationMetaMap): array
    {
        foreach ($relationFields as $formKey => $selectedFields) {
            if (! is_array($selectedFields)) {
                continue;
            }

            $meta = $relationMetaMap[$formKey] ?? null;
            if (! $meta || ($meta['mode'] ?? 'direct') === 'reverse') {
                continue;
            }

            $parentKey = $meta['parent_property'] ?? null;
            $alias = $meta['alias'] ?? null;
            $relationName = $meta['relation_name'] ?? $alias ?? null;
            $parentModelClass = $meta['parent_model_class'] ?? null;

            if (! $parentKey || ! $alias || ! $parentModelClass || ! $relationName) {
                continue;
            }

            if (! isset($payload[$parentKey][$alias]) || ! is_array($payload[$parentKey][$alias])) {
                continue;
            }

            // Ottieni la configurazione della relazione da getSignalFields()
            $registry = app(ModelRegistry::class);
            $parentModelFields = $registry->getFields($parentModelClass);

            if ($parentModelFields && isset($parentModelFields['relations'][$relationName])) {
                $relationConfig = $parentModelFields['relations'][$relationName];
                $expandList = $relationConfig['expand'] ?? [];

                if (! empty($expandList)) {
                    // Espandi le relazioni annidate
                    $relationModelClass = $meta['model_class'] ?? null;
                    if ($relationModelClass) {
                        $this->expandNestedRelationsInModel($payload[$parentKey][$alias], $expandList, $relationModelClass);
                    }
                }
            }
        }

        return $payload;
    }
}
