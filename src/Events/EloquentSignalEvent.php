<?php

namespace Voodflow\Voodflow\Events;

use Voodflow\Voodflow\Contracts\HasSignal;
use Voodflow\Voodflow\Contracts\IdentifiableEvent;
use Voodflow\Voodflow\Contracts\PayloadProvider;
use Voodflow\Voodflow\Support\ModelRegistry;
use Illuminate\Database\Eloquent\Model;

class EloquentSignalEvent implements IdentifiableEvent, PayloadProvider
{
    public function __construct(
        protected string $eventName,
        protected string $operation,
        protected string $alias,
        protected Model $model
    ) {}

    public function signalEventIdentifier(): string
    {
        return $this->eventName;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSignalPayload(): array
    {
        // Carica le relazioni prima di convertire in array
        $this->loadRelationsIfNeeded();

        $payload = [
            'event' => [
                'type' => 'eloquent',
                'operation' => $this->operation,
                'model_class' => $this->model::class,
                'event' => $this->eventName,
            ],
        ];

        $payload[$this->alias] = $this->model->toArray();

        // Mantieni un riferimento rapido all'ID se disponibile
        if ($this->model->getKeyName()) {
            $payload["{$this->alias}_id"] = $this->model->getKey();
        }

        return $payload;
    }

    /**
     * Carica le relazioni se il modello implementa HasSignal o ha una Model Integration
     */
    protected function loadRelationsIfNeeded(): void
    {
        $modelClass = $this->model::class;

        // Se il modello implementa HasSignal, usa loadEventRelationsForDispatch
        if (is_subclass_of($modelClass, HasSignal::class) && method_exists($modelClass, 'loadEventRelationsForDispatch')) {
            $this->model->loadEventRelationsForDispatch();

            return;
        }

        // Altrimenti, carica le relazioni dalla Model Integration
        $registry = app(ModelRegistry::class);
        $modelFields = $registry->getFields($modelClass);

        if ($modelFields && isset($modelFields['relations'])) {
            $relationsToLoad = [];

            foreach ($modelFields['relations'] as $relationName => $relationConfig) {
                $relationsToLoad[] = $relationName;

                // Carica anche le relazioni annidate se configurate
                $expand = $relationConfig['expand'] ?? [];
                if (! empty($expand)) {
                    foreach ($expand as $nestedRelation) {
                        $relationsToLoad[] = "{$relationName}.{$nestedRelation}";
                    }
                }
            }

            if (! empty($relationsToLoad)) {
                $this->model->loadMissing($relationsToLoad);
            }
        }
    }
}
