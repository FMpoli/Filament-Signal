<?php

namespace Voodflow\Voodflow\Services;

use Voodflow\Voodflow\Events\EloquentSignalEvent;
use Voodflow\Voodflow\Models\SignalTrigger;
use Voodflow\Voodflow\Support\EloquentEventMap;
use Voodflow\Voodflow\Support\EventRegistry;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class EventRegistrar
{
    public function __construct(
        protected Dispatcher $dispatcher,
        protected EventProcessor $processor,
        protected EventRegistry $eventRegistry,
        protected EloquentEventMap $eloquentEventMap
    ) {
    }

    public function register(): void
    {
        $events = $this->discoverEvents();

        Log::info('Signal: Registering event listeners', [
            'events_count' => $events->count(),
            'events' => $events->toArray(),
        ]);

        foreach ($events as $eventName) {
            // Rimuovi i listener esistenti per questo evento per evitare duplicati
            // Nota: questo rimuove TUTTI i listener per questo evento, non solo quelli di Signal
            // Ma è necessario per evitare listener duplicati quando si ricarica dopo aver salvato una Model Integration
            $this->dispatcher->forget($eventName);

            Log::info('Signal: Registering listener for event', [
                'event_name' => $eventName,
            ]);

            $this->dispatcher->listen($eventName, function (...$payload) use ($eventName): void {
                // Log diretto su file per debug
                try {
                    $logFile = base_path('storage/logs/signal-debug.log');
                    $logMessage = date('Y-m-d H:i:s') . " - Event listener called - Event: {$eventName}, Payload count: " . count($payload) . "\n";
                    @file_put_contents($logFile, $logMessage, FILE_APPEND);
                } catch (\Throwable $e) {
                    // Ignora errori di scrittura
                }

                Log::info('Signal: Event listener called', [
                    'event_name' => $eventName,
                    'payload_count' => count($payload),
                ]);

                $event = $this->wrapEventPayload($eventName, $payload);

                if (!$event) {
                    Log::info('Signal: Event wrapped to null, skipping', [
                        'event_name' => $eventName,
                    ]);

                    return;
                }

                Log::info('Signal: Calling processor', [
                    'event_name' => $eventName,
                    'event_class' => get_class($event),
                ]);

                $this->processor->handle($event);
            });
        }
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
        // Eventi dal config
        $configured = collect(config('signal.registered_events', []));

        // Eventi registrati dai plugin tramite FilamentSignal::registerEvent()
        $registeredEvents = collect(array_keys($this->eventRegistry->all()));

        // Eventi già usati nei trigger esistenti (dal database)
        $databaseEvents = collect();

        try {
            $databaseEvents = SignalTrigger::query()
                ->select('event_class')
                ->distinct()
                ->pluck('event_class');
        } catch (Throwable $exception) {
            Log::debug('Signal could not load events from database.', [
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
