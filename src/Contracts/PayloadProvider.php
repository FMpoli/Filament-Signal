<?php

namespace Voodflow\Voodflow\Contracts;

/**
 * Permette a un evento di fornire manualmente il payload serializzato
 * che verrà passato alle azioni di Signal.
 */
interface PayloadProvider
{
    /**
     * @return array<string, mixed>
     */
    public function toSignalPayload(): array;
}
