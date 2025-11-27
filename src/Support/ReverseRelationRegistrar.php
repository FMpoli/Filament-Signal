<?php

namespace Base33\FilamentSignal\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReverseRelationRegistrar
{
    public function __construct(
        protected ReverseRelationRegistry $registry
    ) {}

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array{essential?: array, relations?: array}  $modelFields
     */
    public function register(string $modelClass, array $modelFields): void
    {
        if (! class_exists($modelClass)) {
            return;
        }

        $relations = $modelFields['relations'] ?? [];

        if (empty($relations)) {
            return;
        }

        try {
            $model = app($modelClass);
        } catch (\Throwable $exception) {
            return;
        }

        foreach ($relations as $relationName => $relationConfig) {
            if (! method_exists($model, $relationName)) {
                continue;
            }

            try {
                $relationInstance = $model->{$relationName}();
            } catch (\Throwable $exception) {
                continue;
            }

            if (! $relationInstance instanceof BelongsTo) {
                continue;
            }

            $relatedModel = $relationInstance->getRelated();
            $targetClass = get_class($relatedModel);

            $descriptorKey = sprintf(
                '%s::%s->%s',
                $modelClass,
                $relationName,
                $targetClass
            );

            $fields = $relationConfig['fields'] ?? [];
            $expand = $relationConfig['expand'] ?? [];

            $this->registry->register($targetClass, [
                'key' => $descriptorKey,
                'source_model' => $modelClass,
                'relation_name' => $relationName,
                'label' => sprintf(
                    '%s → %s',
                    Str::headline(class_basename($modelClass)),
                    Str::headline($relationName)
                ),
                'foreign_key' => $this->getForeignKeyName($relationInstance, $relationName),
                'relation_type' => class_basename($relationInstance),
                'model_fields' => [
                    'fields' => $fields,
                    'expand' => $expand,
                ],
            ]);
        }
    }

    /**
     * Ottiene il nome della foreign key da una relazione BelongsTo
     */
    protected function getForeignKeyName(BelongsTo $relation, string $relationName): ?string
    {
        // Prova diversi metodi per ottenere il foreign key
        if (method_exists($relation, 'getForeignKeyName')) {
            return $relation->getForeignKeyName();
        }

        if (method_exists($relation, 'getForeignKey')) {
            $key = $relation->getForeignKey();
            // Se è un array, prendi il primo elemento
            if (is_array($key)) {
                return $key[0] ?? null;
            }
            return $key;
        }

        // Fallback: usa il nome della relazione + _id
        return Str::snake($relationName) . '_id';
    }
}


