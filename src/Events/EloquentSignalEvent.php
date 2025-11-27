<?php

namespace Base33\FilamentSignal\Events;

use Base33\FilamentSignal\Contracts\SignalIdentifiableEvent;
use Base33\FilamentSignal\Contracts\SignalPayloadProvider;
use Illuminate\Database\Eloquent\Model;

class EloquentSignalEvent implements SignalIdentifiableEvent, SignalPayloadProvider
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
}
