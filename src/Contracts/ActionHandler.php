<?php

namespace Voodflow\Voodflow\Contracts;

use Voodflow\Voodflow\Models\SignalAction;
use Voodflow\Voodflow\Models\SignalActionLog;

interface ActionHandler
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
