<?php

namespace Base33\FilamentSignal\Services;

use Base33\FilamentSignal\Jobs\RunSignalTrigger;
use Base33\FilamentSignal\Models\SignalTrigger;
use Base33\FilamentSignal\Services\SignalActionExecutor;
use Base33\FilamentSignal\Support\SignalPayloadFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SignalEventProcessor
{
    public function __construct(
        protected SignalPayloadFactory $payloadFactory,
    ) {}

    public function handle(object $event): void
    {
        $eventClass = $event::class;

        Log::debug("Signal: Event received", [
            'event_class' => $eventClass,
        ]);

        $triggers = $this->findMatchingTriggers($eventClass);

        if ($triggers->isEmpty()) {
            Log::debug("Signal: No active triggers found for event", [
                'event_class' => $eventClass,
            ]);
            return;
        }

        Log::info("Signal: Found {$triggers->count()} trigger(s) for event", [
            'event_class' => $eventClass,
            'trigger_ids' => $triggers->pluck('id')->toArray(),
        ]);

        $payload = $this->payloadFactory->fromEvent($event);

        $useSync = config('signal.execute_sync', false) || config('queue.default') === 'sync';

        foreach ($triggers as $trigger) {
            if ($useSync) {
                // Esegui immediatamente senza coda
                $trigger->load(['actions' => fn ($query) => $query->active()->orderBy('execution_order')->with('template')]);
                $executor = app(SignalActionExecutor::class);
                
                foreach ($trigger->actions as $action) {
                    $executor->execute($action, $payload, $eventClass);
                }
            } else {
                // Metti in coda
                RunSignalTrigger::dispatch(
                    triggerId: $trigger->id,
                    eventClass: $eventClass,
                    payload: $payload,
                );
            }
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
