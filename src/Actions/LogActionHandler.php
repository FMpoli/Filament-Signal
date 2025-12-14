<?php

namespace Voodflow\Voodflow\Actions;

use Voodflow\Voodflow\Contracts\ActionHandler;
use Voodflow\Voodflow\Models\SignalAction;
use Voodflow\Voodflow\Models\SignalActionLog;
use Voodflow\Voodflow\Support\PayloadConfigurator;
use Voodflow\Voodflow\Support\PayloadFieldAnalyzer;
use Illuminate\Support\Arr;

class LogActionHandler implements ActionHandler
{
    /**
     * Handle the log action. This action type is used to monitor specific logs.
     * The log is already created by SignalActionExecutor, so we just need to
     * build the payload according to configuration and update the log.
     */
    public function handle(SignalAction $action, array $payload, string $eventClass, ?SignalActionLog $log = null): ?array
    {
        $configuration = $action->configuration ?? [];
        // Usa body per log (ora isolato nel form, nessun conflitto)
        // Fallback a log_body solo per retrocompatibilità con vecchie configurazioni
        $bodyMode = Arr::get($configuration, 'body') ?? Arr::get($configuration, 'log_body', 'payload');

        // Costruisci il payload finale come fa WebhookActionHandler
        $payloadForLog = $this->buildPayload($bodyMode, $payload, $eventClass, $action, $configuration);

        // Aggiorna il log con il payload finale elaborato
        // IMPORTANTE: Salva esattamente lo stesso payload che verrebbe inviato a un webhook con la stessa configurazione
        if ($log) {
            $log->update(['payload' => $payloadForLog]);
        }

        return [
            'monitored' => true,
            'action_name' => $action->name,
            'event_class' => $eventClass,
            'payload_mode' => $bodyMode,
        ];
    }

    /**
     * Costruisce il payload finale in base al mode configurato
     * Replica la logica di WebhookActionHandler::buildPayload
     */
    protected function buildPayload(string $mode, array $payload, string $eventClass, SignalAction $action, array $configuration): array
    {
        $payloadConfig = Arr::get($configuration, 'payload_config', []);

        // Configura il payload se ci sono configurazioni
        // IMPORTANTE: Replica esattamente la stessa logica di WebhookActionHandler::buildPayload
        if (! empty($payloadConfig)) {
            $relationFields = Arr::get($payloadConfig, 'relation_fields', []);

            if (! empty($relationFields) && is_array($relationFields)) {
                $analyzer = app(PayloadFieldAnalyzer::class);
                $analysis = $analyzer->analyzeEvent($eventClass);

                $relationMetaMap = [];
                foreach ($analysis['relations'] as $relation) {
                    $formKey = $relation['form_key'] ?? str_replace(['.', ' '], '_', $relation['id_field']);
                    $relationMetaMap[$formKey] = $relation;
                }

                $relationsMap = [];
                $expandNested = [];
                $reverseSelections = [];

                foreach ($relationFields as $formKey => $fields) {
                    $relationMeta = $relationMetaMap[$formKey] ?? null;
                    if (! $relationMeta) {
                        continue;
                    }

                    if (($relationMeta['mode'] ?? 'direct') === 'reverse') {
                        // Aggiungi sempre la relazione inversa, anche se fields è vuoto
                        // (verrà gestito da filterReverseRelationFields con campi essenziali)
                        $reverseSelections[] = [
                            'meta' => $relationMeta,
                            'fields' => is_array($fields) ? $fields : [],
                        ];

                        continue;
                    }

                    $idField = $relationMeta['id_field'] ?? null;
                    if (! $idField || empty($relationMeta['model_class'])) {
                        continue;
                    }

                    $relationsMap[$idField] = $relationMeta['model_class'];

                    if (! empty($relationMeta['expand'])) {
                        $expandNested[$idField] = $relationMeta['expand'];
                    }
                }

                $payloadConfig['expand_relations'] = $relationsMap;
                $payloadConfig['expand_nested'] = $expandNested;
                $payloadConfig['reverse_relations'] = $reverseSelections;
                $payloadConfig['relation_meta_map'] = $relationMetaMap;
            } else {
                // Se expand_relations è ancora presente (per retrocompatibilità), convertilo
                $expandRelations = Arr::get($payloadConfig, 'expand_relations', []);
                if (is_array($expandRelations) && ! empty($expandRelations) && ! Arr::isAssoc($expandRelations)) {
                    // È un array di ID, convertilo in formato KeyValue usando l'analyzer
                    $analyzer = app(PayloadFieldAnalyzer::class);
                    $analysis = $analyzer->analyzeEvent($eventClass);

                    $relationsMap = [];
                    $expandNested = [];

                    foreach ($expandRelations as $idField) {
                        if (isset($analysis['relations'][$idField])) {
                            $relation = $analysis['relations'][$idField];
                            if ($relation['model_class']) {
                                $relationsMap[$idField] = $relation['model_class'];

                                if (! empty($relation['expand'])) {
                                    $expandNested[$idField] = $relation['expand'];
                                }
                            }
                        }
                    }

                    $payloadConfig['expand_relations'] = $relationsMap;
                    $payloadConfig['expand_nested'] = $expandNested;
                }
            }

            $configurator = app(PayloadConfigurator::class);
            $payload = $configurator->configure($payload, $payloadConfig);
        }

        $finalPayload = match ($mode) {
            'event' => [
                'event' => $this->cleanEventClass($eventClass),
                'timestamp' => now()->toIso8601String(),
                'data' => $payload, // Non pulire il payload, è già stato configurato da PayloadConfigurator
                'metadata' => [
                    'trigger_id' => $action->trigger?->id,
                    'trigger_name' => $action->trigger?->name,
                    'action_id' => $action->id,
                    'action_name' => $action->name,
                ],
            ],
            default => $payload,
        };

        return $finalPayload;
    }

    /**
     * Pulisce il nome della classe evento per renderlo più leggibile
     */
    protected function cleanEventClass(string $eventClass): string
    {
        // Rimuove il namespace e restituisce solo il nome della classe
        $parts = explode('\\', $eventClass);
        $className = end($parts);

        // Converte da PascalCase a snake_case per consistenza
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }
}
