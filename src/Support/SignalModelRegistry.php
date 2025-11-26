<?php

namespace Base33\FilamentSignal\Support;

/**
 * Registro per modelli che non implementano HasSignal ma vogliono esporre campi per segnali
 */
class SignalModelRegistry
{
    /**
     * @var array<string, array{essential: array, relations?: array}>
     */
    protected array $models = [];

    /**
     * Registra i campi disponibili per un modello
     *
     * @param  string  $modelClass  Classe del modello
     * @param  array  $fields  Array con struttura getSignalFields()
     */
    public function register(string $modelClass, array $fields): void
    {
        $this->models[$modelClass] = $fields;
    }

    /**
     * Ottiene i campi disponibili per un modello
     */
    public function getFields(string $modelClass): ?array
    {
        // Prima controlla se il modello implementa HasSignal
        if (is_subclass_of($modelClass, \Base33\FilamentSignal\Contracts\HasSignal::class)) {
            return $modelClass::getSignalFields();
        }

        // Poi controlla la registrazione esterna
        return $this->models[$modelClass] ?? null;
    }

    /**
     * Verifica se un modello ha campi definiti
     */
    public function hasFields(string $modelClass): bool
    {
        return $this->getFields($modelClass) !== null;
    }

    /**
     * @return array<string, array>
     */
    public function all(): array
    {
        return $this->models;
    }
}
