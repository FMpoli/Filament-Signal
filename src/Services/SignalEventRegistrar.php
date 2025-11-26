<?php

namespace Base33\FilamentSignal\Services;

use Base33\FilamentSignal\Models\SignalTrigger;
use Base33\FilamentSignal\Support\SignalEventRegistry;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SignalEventRegistrar
{
    public function __construct(
        protected Dispatcher $dispatcher,
        protected SignalEventProcessor $processor,
        protected SignalEventRegistry $eventRegistry
    ) {}

    public function register(): void
    {
        $events = $this->discoverEvents();

        foreach ($events as $eventClass) {
            $this->dispatcher->listen($eventClass, function (object $event): void {
                $this->processor->handle($event);
            });
        }
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
