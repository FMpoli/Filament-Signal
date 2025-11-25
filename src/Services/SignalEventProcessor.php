<?php

namespace Base33\FilamentSignal\Services;

use Base33\FilamentSignal\Jobs\RunSignalTrigger;
use Base33\FilamentSignal\Models\SignalTrigger;
use Base33\FilamentSignal\Support\SignalPayloadFactory;
use Illuminate\Support\Collection;

class SignalEventProcessor
{
    public function __construct(
        protected SignalPayloadFactory $payloadFactory,
    ) {}

    public function handle(object $event): void
    {
        $eventClass = $event::class;

        $triggers = $this->findMatchingTriggers($eventClass);

        if ($triggers->isEmpty()) {
            return;
        }

        $payload = $this->payloadFactory->fromEvent($event);

        foreach ($triggers as $trigger) {
            RunSignalTrigger::dispatch(
                triggerId: $trigger->id,
                eventClass: $eventClass,
                payload: $payload,
            );
        }
    }

    protected function findMatchingTriggers(string $eventClass): Collection
    {
        return SignalTrigger::query()
            ->active()
            ->where('event_class', $eventClass)
            ->with(['actions' => fn ($query) => $query->active()->orderBy('execution_order')])
            ->get();
    }
}
