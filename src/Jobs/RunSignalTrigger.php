<?php

namespace Voodflow\Voodflow\Jobs;

use Voodflow\Voodflow\Models\SignalTrigger;
use Voodflow\Voodflow\Services\SignalActionExecutor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunSignalTrigger implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        protected int $triggerId,
        protected string $eventClass,
        protected array $payload,
    ) {
        if ($connection = config('voodflow.queue_connection')) {
            $this->onConnection($connection);
        }
    }

    public function handle(SignalActionExecutor $executor): void
    {
        $trigger = SignalTrigger::with(['actions' => fn ($query) => $query->active()->orderBy('execution_order')->with('template')])
            ->find($this->triggerId);

        if (! $trigger) {
            return;
        }

        // Controlla se il trigger passa i filtri configurati
        if (! $trigger->passesFilters($this->payload)) {
            \Illuminate\Support\Facades\Log::debug("Signal: Trigger [{$trigger->id}] did not pass filters in job", [
                'trigger_id' => $trigger->id,
                'event_class' => $this->eventClass,
            ]);

            return;
        }

        foreach ($trigger->actions as $action) {
            $executor->execute($action, $this->payload, $this->eventClass);
        }
    }
}
