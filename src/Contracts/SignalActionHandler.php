<?php

namespace Base33\FilamentSignal\Contracts;

use Base33\FilamentSignal\Models\SignalAction;

interface SignalActionHandler
{
    /**
     * Execute the configured action and return an optional response payload to log.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function handle(SignalAction $action, array $payload, string $eventClass): ?array;
}
