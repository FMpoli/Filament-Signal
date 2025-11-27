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
     *     eventClass: \Vendor\Plugin\Events\ModelCreated::class,
     *     name: 'Model Created',
     *     description: 'Triggered when a new model is created',
     *     group: 'Plugin'
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
     * @return array<string, string> Array con chiave = event class, valore = nome visualizzato
     */
    public static function eventOptions(): array
    {
        return app(SignalEventRegistry::class)->options();
    }

    /**
     * @return array<string, array<string, string>> Array raggruppato per gruppo
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
     * @param  string|null  $alias  Nome logico da usare nel payload (es: loan)
     */
    public static function registerModelFields(string $modelClass, array $fields, ?string $alias = null): void
    {
        app(SignalModelRegistry::class)->register($modelClass, $fields, $alias);
    }

    /**
     * Ottiene i campi disponibili per un modello
     */
    public static function getModelFields(string $modelClass): ?array
    {
        return app(SignalModelRegistry::class)->getFields($modelClass);
    }
}
