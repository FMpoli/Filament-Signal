<?php

namespace Base33\FilamentSignal\Services;

use Base33\FilamentSignal\Contracts\SignalIdentifiableEvent;
use Base33\FilamentSignal\Jobs\RunSignalTrigger;
use Base33\FilamentSignal\Models\SignalTrigger;
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
        // Log diretto su file per debug
        try {
            $logFile = base_path('storage/logs/signal-debug.log');
            $logMessage = date('Y-m-d H:i:s') . ' - SignalEventProcessor::handle called - Event class: ' . ($event instanceof SignalIdentifiableEvent ? $event->signalEventIdentifier() : get_class($event)) . "\n";
            @file_put_contents($logFile, $logMessage, FILE_APPEND);
        } catch (\Throwable $e) {
            // Ignora errori di scrittura
        }

        $eventClass = $event instanceof SignalIdentifiableEvent
            ? $event->signalEventIdentifier()
            : $event::class;

        Log::info('Signal: Event received', [
            'event_class' => $eventClass,
        ]);

        $triggers = $this->findMatchingTriggers($eventClass);

        if ($triggers->isEmpty()) {
            Log::info('Signal: No active triggers found for event', [
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
            // Controlla se il trigger passa i filtri configurati
            if (! $trigger->passesFilters($payload)) {
                Log::info("Signal: Trigger [{$trigger->id}] did not pass filters", [
                    'trigger_id' => $trigger->id,
                    'trigger_name' => $trigger->name,
                    'event_class' => $eventClass,
                ]);

                continue;
            }

            Log::info("Signal: Trigger [{$trigger->id}] passed filters, executing actions", [
                'trigger_id' => $trigger->id,
                'event_class' => $eventClass,
            ]);

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
