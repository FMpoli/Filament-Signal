<?php

namespace Voodflow\Voodflow\Services;

use Voodflow\Voodflow\Events\EloquentSignalEvent;
use Voodflow\Voodflow\Models\Workflow;
use Voodflow\Voodflow\Support\EloquentEventMap;
use Voodflow\Voodflow\Support\EventRegistry;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Event Registrar
 * 
 * Registers Laravel event listeners for workflows.
 * Updated to use WorkflowExecutor instead of legacy EventProcessor.
 */
class EventRegistrar
{
    public function __construct(
        protected Dispatcher $dispatcher,
        protected WorkflowExecutor $workflowExecutor,
        protected EventRegistry $eventRegistry,
        protected EloquentEventMap $eloquentEventMap
    ) {
    }

    public function register(): void
    {
        $events = $this->discoverEvents();

        Log::info('Voodflow: Registering event listeners', [
            'events_count' => $events->count(),
            'events' => $events->toArray(),
        ]);

        foreach ($events as $eventName) {
            // Remove existing listeners to avoid duplicates
            $this->dispatcher->forget($eventName);

            Log::info('Voodflow: Registering listener for event', [
                'event_name' => $eventName,
            ]);

            $this->dispatcher->listen($eventName, function (...$payload) use ($eventName): void {
                $event = $this->wrapEventPayload($eventName, $payload);

                if (!$event) {
                    Log::debug('Voodflow: Event wrapped to null, skipping', [
                        'event_name' => $eventName,
                    ]);
                    return;
                }

                // Find workflows listening to this event
                $workflows = $this->findWorkflowsForEvent($eventName);

                foreach ($workflows as $workflow) {
                    try {
                        // Extract payload data from event object
                        $payloadData = $this->extractPayloadFromEvent($event);

                        // Execute workflow with WorkflowExecutor
                        $this->workflowExecutor->execute($workflow, $payloadData, $eventName);
                    } catch (Throwable $e) {
                        Log::error('Voodflow: Workflow execution failed', [
                            'workflow_id' => $workflow->id,
                            'event' => $eventName,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
        }
    }

    /**
     * Find workflows that listen to a specific event
     */
    protected function findWorkflowsForEvent(string $eventClass): Collection
    {
        return Workflow::where('status', 'active')
            ->where('event_class', $eventClass)
            ->get();
    }

    /**
     * Extract payload data from event object
     */
    protected function extractPayloadFromEvent(object $event): array
    {
        if ($event instanceof EloquentSignalEvent) {
            return [
                'operation' => $event->operation,
                'alias' => $event->alias,
                'model' => $event->model->toArray(),
                'model_class' => get_class($event->model),
            ];
        }

        // For other event types, try to extract public properties
        return get_object_vars($event);
    }

    protected function wrapEventPayload(string $eventName, array $payload): ?object
    {
        if (Str::startsWith($eventName, 'eloquent.')) {
            $model = $payload[0] ?? null;

            if (!$model instanceof Model) {
                return null;
            }

            $metadata = $this->eloquentEventMap->find($eventName) ?? [];

            $alias = $metadata['alias'] ?? Str::camel(class_basename($model));
            $operation = $metadata['operation'] ?? $this->extractOperationFromEventName($eventName);

            return new EloquentSignalEvent(
                $eventName,
                $operation,
                $alias,
                $model
            );
        }

        return $payload[0] ?? null;
    }

    protected function extractOperationFromEventName(string $eventName): string
    {
        if (preg_match('/eloquent\.([a-z_]+):/i', $eventName, $matches)) {
            return strtolower($matches[1]);
        }

        return 'event';
    }

    protected function discoverEvents(): Collection
    {
        // Events from config
        $configured = collect(config('voodflow.registered_events', []));

        // Events registered by plugins via FilamentSignal::registerEvent()
        $registeredEvents = collect(array_keys($this->eventRegistry->all()));

        // Events from active workflows
        $databaseEvents = collect();

        try {
            $databaseEvents = Workflow::where('status', 'active')
                ->select('event_class')
                ->distinct()
                ->pluck('event_class');
        } catch (Throwable $exception) {
            Log::debug('Voodflow could not load events from database.', [
                'exception' => $exception->getMessage(),
            ]);
        }

        return $configured
            ->merge($registeredEvents)
            ->merge($databaseEvents)
            ->filter()
            ->unique()
            ->values();
    }
}
