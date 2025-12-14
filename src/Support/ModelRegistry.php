<?php

namespace Voodflow\Voodflow\Support;

/**
 * Registro per modelli che non implementano HasSignal ma vogliono esporre campi per segnali
 */
class ModelRegistry
{
    /**
     * @var array<string, array{essential: array, relations?: array}>
     */
    protected array $models = [];

    /**
     * @var array<string, string>
     */
    protected array $aliases = [];

    /**
     * Registra i campi disponibili per un modello
     *
     * @param  string  $modelClass  Classe del modello
     * @param  array  $fields  Array con struttura getSignalFields()
     */
    public function register(string $modelClass, array $fields, ?string $alias = null): void
    {
        $this->models[$modelClass] = $fields;

        if ($alias) {
            $this->aliases[$modelClass] = $alias;
        }

        app(ReverseRelationRegistrar::class)->register($modelClass, $fields);
    }

    /**
     * Ottiene i campi disponibili per un modello
     */
    public function getFields(string $modelClass): ?array
    {
        // Prima controlla se il modello implementa HasSignal
        if (is_subclass_of($modelClass, \Voodflow\Voodflow\Contracts\HasSignal::class)) {
            $fields = $modelClass::getSignalFields();
            app(ReverseRelationRegistrar::class)->register($modelClass, $fields);

            return $fields;
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

    public function getAlias(string $modelClass, ?string $default = null): ?string
    {
        return $this->aliases[$modelClass] ?? $default;
    }

    public function forget(string $modelClass): void
    {
        unset($this->models[$modelClass], $this->aliases[$modelClass]);
    }
}
