<?php

namespace Voodflow\Voodflow\Support;

class ReverseRelationWarmup
{
    protected bool $warmed = false;

    public function __construct(
        protected EventRegistry $eventRegistry,
        protected PayloadFieldAnalyzer $analyzer,
        protected ModelRegistry $modelRegistry,
        protected ReverseRelationRegistrar $registrar
    ) {}

    public function warm(): void
    {
        if ($this->warmed) {
            return;
        }

        $this->warmed = true;

        // Assicurati che tutte le integrazioni registrate vengano elaborate
        foreach ($this->modelRegistry->all() as $modelClass => $fields) {
            $this->registrar->register($modelClass, $fields);
        }

        // Analizza tutti gli eventi conosciuti (registra automaticamente le relazioni inverse)
        foreach ($this->eventRegistry->all() as $event) {
            $eventClass = is_array($event) ? ($event['class'] ?? null) : (is_string($event) ? $event : null);

            if (! $eventClass || ! is_string($eventClass)) {
                continue;
            }

            try {
                $this->analyzer->analyzeEvent($eventClass);
            } catch (\Throwable $exception) {
                // Ignora gli errori: meglio qualche relazione in meno che rompere la UI
            }
        }
    }
}
