<?php

namespace Base33\FilamentSignal\Actions;

use Base33\FilamentSignal\Contracts\SignalActionHandler;
use Base33\FilamentSignal\Models\SignalAction;
use Base33\FilamentSignal\Models\SignalActionLog;

class LogActionHandler implements SignalActionHandler
{
    /**
     * Handle the log action. This action type is used to monitor specific logs.
     * The log is already created by SignalActionExecutor, so we just need to
     * mark it as successful and return metadata.
     */
    public function handle(SignalAction $action, array $payload, string $eventClass, ?SignalActionLog $log = null): ?array
    {
        // Il log viene già creato dal SignalActionExecutor prima di chiamare questo handler
        // Questo handler serve solo per marcare l'action come "log monitoring"
        // Non esegue alcuna operazione esterna, solo monitoraggio

        return [
            'monitored' => true,
            'action_name' => $action->name,
            'event_class' => $eventClass,
        ];
    }
}
