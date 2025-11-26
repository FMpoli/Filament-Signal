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

        // IMPORTANTE: Espandi PRIMA le relazioni, così i campi espansi possono essere inclusi nel filtro
        if (! empty($expandRelations)) {
            $payload = $this->expandRelations($payload, $expandRelations, $expandNested);
        }

        // Poi filtra i campi da includere (PRIMA di filtrare le relazioni, così le relazioni vengono preservate)
        if (! empty($includeFields)) {
            $payload = $this->includeOnly($payload, $includeFields, $relationFields);
        }

        // Filtra i campi delle relazioni selezionati (DOPO includeOnly, così le relazioni sono già nel risultato)
        if (! empty($relationFields)) {
            $payload = $this->filterRelationFields($payload, $relationFields);
        }

        // Infine rimuovi i campi esclusi
        if (! empty($excludeFields)) {
            $payload = $this->excludeFields($payload, $excludeFields);
        }

        return $payload;
    }

    /**
     * Include solo i campi specificati, preservando le relazioni espanse
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string>  $fields
     * @param  array<string, array<string>>  $relationFields  Campi delle relazioni selezionati
     * @return array<string, mixed>
     */
    protected function includeOnly(array $payload, array $fields, array $relationFields = []): array
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
                    // Crea una mappa delle relazioni che hanno campi selezionati
                    $relationsWithFields = [];
                    foreach ($relationFields as $idField => $selectedFields) {
                        if (empty($selectedFields) || ! is_array($selectedFields)) {
                            continue;
                        }

                        // Converti il formato safe (loan_unit_id) al formato originale (loan.unit_id)
                        $originalIdField = str_contains($idField, '.') ? $idField : str_replace('_', '.', $idField);
                        $parts = explode('.', $originalIdField);

                        if (count($parts) >= 2 && $parts[0] === $parentKey) {
                            $relationIdField = str_replace('_id', '', $parts[1]);
                            $relationsWithFields[$relationIdField] = true;
                        }
                    }

                    // Includi tutte le relazioni che hanno campi selezionati
                    foreach ($relationsWithFields as $relationName => $_) {
                        if (isset($parentData[$relationName]) && is_array($parentData[$relationName])) {
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
    protected function filterRelationFields(array $payload, array $relationFields): array
    {
        foreach ($relationFields as $idField => $selectedFields) {
            if (empty($selectedFields) || ! is_array($selectedFields)) {
                continue;
            }

            // Il formato può essere "loan.unit_id" o "loan_unit_id" (safe format)
            // Converti il formato safe al formato originale se necessario
            $originalIdField = str_contains($idField, '.') ? $idField : str_replace('_', '.', $idField);

            // Estrai il nome della relazione (es: loan.borrower_id -> borrower)
            $parts = explode('.', $originalIdField);
            if (count($parts) < 2) {
                continue;
            }

            $relationName = str_replace('_id', '', $parts[1]);
            $parentKey = $parts[0]; // es: loan

            // Naviga nel payload fino alla relazione
            if (! isset($payload[$parentKey][$relationName]) || ! is_array($payload[$parentKey][$relationName])) {
                continue;
            }

            // Filtra solo i campi selezionati
            $relationData = $payload[$parentKey][$relationName];
            $filtered = [];

            foreach ($selectedFields as $field) {
                // Il campo è nel formato "relationName.fieldKey" (es: borrower.name o unit.inventory_code)
                $fieldParts = explode('.', $field);
                if (count($fieldParts) === 2 && $fieldParts[0] === $relationName) {
                    $fieldKey = $fieldParts[1];
                    if (isset($relationData[$fieldKey])) {
                        $filtered[$fieldKey] = $relationData[$fieldKey];
                    }
                }
            }

            // Mantieni sempre id se presente
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

        $commonModels = [
            'User' => \App\Models\User::class,
            'EquipmentUnit' => \Detit\FilamentLabOps\Models\EquipmentUnit::class,
            'EquipmentModel' => \Detit\FilamentLabOps\Models\EquipmentModel::class,
            'EquipmentBrand' => \Detit\FilamentLabOps\Models\EquipmentBrand::class,
            'EquipmentType' => \Detit\FilamentLabOps\Models\EquipmentType::class,
        ];

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

        // Campi specifici per tipo di modello
        $specific = match (true) {
            str_contains($modelClass, 'EquipmentUnit') => ['inventory_code', 'serial_number', 'internal_code', 'status', 'condition'],
            str_contains($modelClass, 'EquipmentModel') => ['name', 'model_number', 'short_description'],
            str_contains($modelClass, 'EquipmentBrand') => ['name'],
            str_contains($modelClass, 'EquipmentType') => ['name'],
            str_contains($modelClass, 'EquipmentLoan') => ['status', 'loaned_at', 'due_at', 'returned_at', 'notes'],
            str_contains($modelClass, 'User') => ['name', 'email'],
            default => [],
        };

        return array_unique(array_merge($common, $specific));
    }
}
