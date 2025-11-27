<?php

namespace Base33\FilamentSignal\Contracts;

/**
 * Eventi che implementano questa interfaccia possono personalizzare
 * l'identificatore usato dai trigger (di default è il nome della classe).
 */
interface SignalIdentifiableEvent
{
    /**
     * Restituisce la stringa usata come identificatore dell'evento nei trigger.
     */
    public function signalEventIdentifier(): string;
}


