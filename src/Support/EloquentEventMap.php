<?php

namespace Voodflow\Voodflow\Support;

/**
 * Tiene traccia delle informazioni necessarie per trasformare
 * gli eventi Eloquent (eloquent.created: ModelClass) in eventi Signal.
 */
class EloquentEventMap
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $map = [];

    /**
     * @param  array{model_class: string, alias?: string|null, operation?: string|null}  $metadata
     */
    public function register(string $eventName, array $metadata): void
    {
        $this->map[$eventName] = $metadata;
    }

    public function forget(string $eventName): void
    {
        unset($this->map[$eventName]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $eventName): ?array
    {
        return $this->map[$eventName] ?? null;
    }
}
