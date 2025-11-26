<?php

namespace Base33\FilamentSignal\Contracts;

/**
 * Interfaccia per modelli che espongono campi disponibili per webhook/email
 */
interface HasSignal
{
    /**
     * Restituisce i campi disponibili per i segnali (webhook/email)
     * 
     * @return array{
     *     essential: array<string|int, string>,  // ['field' => 'Label'] o ['field']
     *     relations?: array<string, array{
     *         fields?: array<string|int, string>,  // Campi da includere della relazione
     *         expand?: array<string>,              // Relazioni annidate da espandere
     *     }>
     * }
     */
    public static function getSignalFields(): array;
}

