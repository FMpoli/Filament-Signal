<?php

namespace Base33\FilamentSignal\Services;

use Base33\FilamentSignal\Models\SignalTrigger;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SignalEventRegistrar
{
    public function __construct(
        protected Dispatcher $dispatcher,
        protected SignalEventProcessor $processor
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
        $configured = collect(config('signal.registered_events', []));

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

        return $configured->merge($databaseEvents)->filter()->unique()->values();
    }
}
