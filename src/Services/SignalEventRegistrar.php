<?php

namespace Base33\FilamentSignal\Services;

use Base33\FilamentSignal\Events\EloquentSignalEvent;
use Base33\FilamentSignal\Models\SignalTrigger;
use Base33\FilamentSignal\Support\SignalEventRegistry;
use Base33\FilamentSignal\Support\SignalEloquentEventMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SignalEventRegistrar
{
    public function __construct(
        protected Dispatcher $dispatcher,
        protected SignalEventProcessor $processor,
        protected SignalEventRegistry $eventRegistry,
        protected SignalEloquentEventMap $eloquentEventMap
    ) {}

    public function register(): void
    {
        $events = $this->discoverEvents();

        foreach ($events as $eventName) {
            $this->dispatcher->listen($eventName, function (...$payload) use ($eventName): void {
                $event = $this->wrapEventPayload($eventName, $payload);

                if (! $event) {
                    return;
                }

                $this->processor->handle($event);
            });
        }
    }

    protected function wrapEventPayload(string $eventName, array $payload): ?object
    {
        if (Str::startsWith($eventName, 'eloquent.')) {
            $model = $payload[0] ?? null;

            if (! $model instanceof Model) {
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
