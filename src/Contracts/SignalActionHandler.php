<?php

namespace Base33\FilamentSignal\Contracts;

use Base33\FilamentSignal\Models\SignalAction;
use Base33\FilamentSignal\Models\SignalActionLog;

interface SignalActionHandler
{
    /**
     * Execute the configured action and return an optional response payload to log.
     *
     * @param  array<string, mixed>  $payload
     * @param  SignalActionLog|null  $log  Il log dell'azione, può essere aggiornato con il payload finale
     * @return array<string, mixed>|null
     */
    public function handle(SignalAction $action, array $payload, string $eventClass, ?SignalActionLog $log = null): ?array;
}
