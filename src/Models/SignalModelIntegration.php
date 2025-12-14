<?php

namespace Voodflow\Voodflow\Models;

use Voodflow\Voodflow\Voodflow;
use Voodflow\Voodflow\Support\SignalEloquentEventMap;
use Voodflow\Voodflow\Support\SignalEventRegistry;
use Voodflow\Voodflow\Support\SignalModelRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SignalModelIntegration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'model_class',
        'model_alias',
        'fields',
        'eloquent_events',
        'custom_events',
    ];

    protected $casts = [
        'fields' => 'array',
        'eloquent_events' => 'array',
        'custom_events' => 'array',
    ];

    public function getTable()
    {
        return config('signal.table_names.model_integrations', 'signal_model_integrations');
    }

    protected static function booted(): void
    {
        static::saved(fn (self $integration) => $integration->refreshSignalRegistrations());
        static::deleted(fn (self $integration) => $integration->unregisterFromSignal());
        static::forceDeleted(fn (self $integration) => $integration->unregisterFromSignal());
    }

    public function refreshSignalRegistrations(): void
    {
        $this->unregisterFromSignal();
        $this->registerOnBoot();

        // IMPORTANTE: Ricarica i listener degli eventi dopo aver registrato nuovi eventi
        // Questo è necessario perché SignalEventRegistrar::register() viene chiamato solo all'avvio
        // e se aggiungiamo una nuova Model Integration dopo l'avvio, i listener non vengono ricaricati
        if (app()->isBooted()) {
            app(\Base33\FilamentSignal\Services\SignalEventRegistrar::class)->register();
        }
    }

    public function registerOnBoot(): void
    {
        $fields = $this->getNormalizedFields();

        if (! empty($fields)) {
            FilamentSignal::registerModelFields($this->model_class, $fields, $this->getAlias());
        }

        foreach ($this->getEloquentEventDescriptors() as $descriptor) {
            FilamentSignal::registerEvent(
                $descriptor['event'],
                $descriptor['label'],
                $descriptor['description'],
                $descriptor['group']
            );

            app(SignalEloquentEventMap::class)->register($descriptor['event'], [
                'model_class' => $this->model_class,
                'alias' => $descriptor['alias'],
                'operation' => $descriptor['operation'],
            ]);
        }

        foreach ($this->getCustomEventDescriptors() as $descriptor) {
            FilamentSignal::registerEvent(
                $descriptor['event'],
                $descriptor['label'],
                $descriptor['description'],
                $descriptor['group']
            );
        }
    }

    public function unregisterFromSignal(): void
    {
        app(SignalModelRegistry::class)->forget($this->model_class);

        foreach ($this->getEloquentEventDescriptors() as $descriptor) {
            app(SignalEventRegistry::class)->forget($descriptor['event']);
            app(SignalEloquentEventMap::class)->forget($descriptor['event']);
        }

        foreach ($this->getCustomEventDescriptors() as $descriptor) {
            app(SignalEventRegistry::class)->forget($descriptor['event']);
        }
    }

    public function getAlias(): string
    {
        if ($this->model_alias) {
            return $this->model_alias;
        }

        $class = class_basename($this->model_class);

        return Str::camel($class);
    }

    /**
     * Restituisce i campi normalizzati nello stesso formato di HasSignal.
     *
     * @return array{essential: array, relations?: array}
     */
    public function getNormalizedFields(): array
    {
        $definition = [
            'essential' => [],
            'relations' => [],
            'reverse_relations' => [],
        ];

        $essential = Arr::get($this->fields, 'essential', []);

        foreach ($essential as $field) {
            $fieldName = $field['field'] ?? null;
            if (! $fieldName) {
                continue;
            }

            $label = $field['label'] ?? null;

            if ($label) {
                $definition['essential'][$fieldName] = $label;
            } else {
                $definition['essential'][] = $fieldName;
            }
        }

        $relations = Arr::get($this->fields, 'relations', []);

        foreach ($relations as $relation) {
            $mode = $relation['relation_mode'] ?? 'direct';

            if ($mode === 'reverse') {
                $descriptor = $relation['relation_descriptor'] ?? null;
                if (! $descriptor) {
                    continue;
                }

                $definition['reverse_relations'][] = [
                    'descriptor' => $descriptor,
                    'alias' => $relation['alias'] ?? null,
                    'fields' => $this->normalizeRelationFields($relation['fields'] ?? []),
                    'expand' => array_values(array_filter($relation['expand'] ?? [])),
                ];

                continue;
            }

            $name = $relation['name'] ?? null;
            if (! $name) {
                continue;
            }

            $definition['relations'][$name] = [
                'alias' => $relation['alias'] ?? null,
                'fields' => $this->normalizeRelationFields($relation['fields'] ?? []),
                'expand' => array_values(array_filter($relation['expand'] ?? [])),
            ];
        }

        return $definition;
    }

    /**
     * @param  array<int, array{field?: string|null, label?: string|null}>  $fields
     * @return array<int|string, string>
     */
    protected function normalizeRelationFields(array $fields): array
    {
        $relationFields = [];

        foreach ($fields as $field) {
            $fieldName = $field['field'] ?? null;
            if (! $fieldName) {
                continue;
            }

            $label = $field['label'] ?? null;

            if ($label) {
                $relationFields[$fieldName] = $label;
            } else {
                $relationFields[] = $fieldName;
            }
        }

        return $relationFields;
    }

    /**
     * @return array<int, array{event: string, operation: string, label: string, description: string|null, group: string, alias: string}>
     */
    public function getEloquentEventDescriptors(): array
    {
        $events = [];

        $seen = [];

        foreach ($this->eloquent_events ?? [] as $operation) {
            $operation = strtolower($operation);

            $eventName = "eloquent.{$operation}: {$this->model_class}";
            if (isset($seen[$eventName])) {
                continue;
            }
            $seen[$eventName] = true;

            $events[] = [
                'event' => $eventName,
                'operation' => $operation,
                'alias' => $this->getAlias(),
                'label' => "{$this->name} • " . $this->readableOperation($operation),
                'description' => __('filament-signal::signal.model_integrations.events.eloquent_description', [
                    'model' => $this->name,
                    'operation' => $this->readableOperation($operation),
                ]),
                'group' => __('filament-signal::signal.model_integrations.groups.eloquent'),
            ];
        }

        return $events;
    }

    /**
     * @return array<int, array{event: string, label: string, description: string|null, group: string}>
     */
    public function getCustomEventDescriptors(): array
    {
        $events = [];

        foreach ($this->custom_events ?? [] as $event) {
            $class = $event['class'] ?? null;
            if (! $class) {
                continue;
            }

            $events[] = [
                'event' => $class,
                'label' => $event['label'] ?? class_basename($class),
                'description' => $event['description'] ?? null,
                'group' => $event['group'] ?? $this->name,
            ];
        }

        return $events;
    }

    protected function readableOperation(string $operation): string
    {
        return match ($operation) {
            'created' => __('filament-signal::signal.model_integrations.operations.created'),
            'updated' => __('filament-signal::signal.model_integrations.operations.updated'),
            'deleted' => __('filament-signal::signal.model_integrations.operations.deleted'),
            'restored' => __('filament-signal::signal.model_integrations.operations.restored'),
            default => Str::headline($operation),
        };
    }
}
