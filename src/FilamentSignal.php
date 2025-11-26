<?php

namespace Base33\FilamentSignal;

use Base33\FilamentSignal\Support\SignalEventRegistry;
use Base33\FilamentSignal\Support\SignalModelRegistry;
use Base33\FilamentSignal\Support\SignalWebhookTemplate;
use Base33\FilamentSignal\Support\SignalWebhookTemplateRegistry;

class FilamentSignal
{
    public static function registerWebhookTemplate(SignalWebhookTemplate | array $template): void
    {
        if (is_array($template)) {
            $template = SignalWebhookTemplate::fromArray($template);
        }

        app(SignalWebhookTemplateRegistry::class)->register($template);
    }

    /**
     * @return array<string, SignalWebhookTemplate>
     */
    public static function webhookTemplates(): array
    {
        return app(SignalWebhookTemplateRegistry::class)->all();
    }

    /**
     * @return array<string, string>
     */
    public static function webhookTemplateOptions(): array
    {
        return app(SignalWebhookTemplateRegistry::class)->options();
    }

    /**
     * Registra un evento esposto da un plugin
     *
     * Esempio:
     * FilamentSignal::registerEvent(
     *     eventClass: \Detit\FilamentLabOps\Events\EquipmentLoanCreated::class,
     *     name: 'Equipment Loan Created',
     *     description: 'Triggered when a new equipment loan is created',
     *     group: 'LabOps'
     * );
     *
     * @param  string  $eventClass  Classe completa dell'evento
     * @param  string  $name  Nome visualizzato nell'UI
     * @param  string|null  $description  Descrizione opzionale
     * @param  string|null  $group  Gruppo per organizzare gli eventi
     */
    public static function registerEvent(string $eventClass, string $name, ?string $description = null, ?string $group = null): void
    {
        app(SignalEventRegistry::class)->register($eventClass, $name, $description, $group);
    }

    /**
     * @return array<string, string>  Array con chiave = event class, valore = nome visualizzato
     */
    public static function eventOptions(): array
    {
        return app(SignalEventRegistry::class)->options();
    }

    /**
     * @return array<string, array<string, string>>  Array raggruppato per gruppo
     */
    public static function groupedEventOptions(): array
    {
        return app(SignalEventRegistry::class)->groupedOptions();
    }

    /**
     * Registra i campi disponibili per un modello che non implementa HasSignal
     *
     * Esempio:
     * FilamentSignal::registerModelFields(
     *     modelClass: \App\Models\User::class,
     *     fields: [
     *         'essential' => ['id', 'name', 'email'],
     *         'relations' => [
     *             'profile' => ['fields' => ['phone', 'address']],
     *         ],
     *     ]
     * );
     *
     * @param  string  $modelClass  Classe del modello
     * @param  array  $fields  Array con struttura getSignalFields()
     */
    public static function registerModelFields(string $modelClass, array $fields): void
    {
        app(SignalModelRegistry::class)->register($modelClass, $fields);
    }

    /**
     * Ottiene i campi disponibili per un modello
     *
     * @param  string  $modelClass
     * @return array|null
     */
    public static function getModelFields(string $modelClass): ?array
    {
        return app(SignalModelRegistry::class)->getFields($modelClass);
    }
}
