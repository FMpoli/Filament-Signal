<?php

namespace Base33\FilamentSignal\Contracts;

/**
 * Permette a un evento di fornire manualmente il payload serializzato
 * che verrà passato alle azioni di Signal.
 */
interface SignalPayloadProvider
{
    /**
     * @return array<string, mixed>
     */
    public function toSignalPayload(): array;
}


