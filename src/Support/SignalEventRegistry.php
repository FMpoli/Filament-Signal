<?php

namespace Voodflow\Voodflow\Support;

class SignalEventRegistry
{
    /**
     * @var array<string, array{class: string, name: string, description?: string, group?: string}>
     */
    protected array $events = [];

    /**
     * Registra un evento esposto da un plugin
     *
     * @param  string  $eventClass  Classe completa dell'evento (es: Detit\FilamentLabOps\Events\EquipmentLoanCreated)
     * @param  string  $name  Nome visualizzato nell'UI
     * @param  string|null  $description  Descrizione opzionale
     * @param  string|null  $group  Gruppo per organizzare gli eventi (es: "LabOps", "Users", ecc.)
     */
    public function register(string $eventClass, string $name, ?string $description = null, ?string $group = null): void
    {
        $this->events[$eventClass] = [
            'class' => $eventClass,
            'name' => $name,
            'description' => $description,
            'group' => $group,
        ];
    }

    public function forget(string $eventClass): void
    {
        unset($this->events[$eventClass]);
    }

    /**
     * @return array<string, array{class: string, name: string, description?: string, group?: string}>
     */
    public function all(): array
    {
        return $this->events;
    }

    /**
     * Restituisce le opzioni per una select, organizzate per gruppo
     *
     * @return array<string, string> Array con chiave = event class, valore = nome visualizzato
     */
    public function options(): array
    {
        $options = [];

        foreach ($this->events as $event) {
            $label = $event['name'];
            if ($event['group']) {
                $label = "{$event['group']} - {$label}";
            }
            $options[$event['class']] = $label;
        }

        return $options;
    }

    /**
     * Restituisce le opzioni raggruppate per gruppo
     *
     * @return array<string, array<string, string>> Array con chiave = gruppo, valore = array di eventi
     */
    public function groupedOptions(): array
    {
        $grouped = [];

        foreach ($this->events as $event) {
            $group = $event['group'] ?? 'Other';
            if (! isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][$event['class']] = $event['name'];
        }

        // Ordina i gruppi
        ksort($grouped);

        return $grouped;
    }

    public function find(?string $eventClass): ?array
    {
        if (! $eventClass) {
            return null;
        }

        return $this->events[$eventClass] ?? null;
    }
}
