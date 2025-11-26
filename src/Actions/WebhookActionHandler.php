<?php

namespace Base33\FilamentSignal\Actions;

use Base33\FilamentSignal\Contracts\SignalActionHandler;
use Base33\FilamentSignal\Models\SignalAction;
use Base33\FilamentSignal\Support\SignalPayloadConfigurator;
use Base33\FilamentSignal\Support\SignalPayloadFieldAnalyzer;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Spatie\WebhookServer\WebhookCall;

class WebhookActionHandler implements SignalActionHandler
{
    public function handle(SignalAction $action, array $payload, string $eventClass): ?array
    {
        $configuration = $action->configuration ?? [];

        $url = Arr::get($configuration, 'url');
        $method = strtoupper(Arr::get($configuration, 'method', 'POST'));
        $headers = Arr::get($configuration, 'headers', []);

        // Assicurati che Content-Type sia sempre application/json
        if (! isset($headers['Content-Type']) && ! isset($headers['content-type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        $bodyMode = Arr::get($configuration, 'body', 'payload');
        $meta = Arr::get($configuration, 'meta', []);
        $tags = Arr::get($configuration, 'tags', []);
        $secret = Arr::get($configuration, 'secret', config('signal.webhook.secret'));
        $queue = Arr::get($configuration, 'queue', config('signal.webhook.queue'));
        $connection = Arr::get($configuration, 'connection', config('signal.webhook.connection'));
        $timeout = Arr::get($configuration, 'timeout', config('signal.webhook.timeout'));
        $tries = Arr::get($configuration, 'tries', config('signal.webhook.tries'));
        $backoffStrategy = Arr::get($configuration, 'backoff_strategy', config('signal.webhook.backoff_strategy'));
        $verifySsl = Arr::has($configuration, 'verify_ssl')
            ? Arr::get($configuration, 'verify_ssl')
            : config('signal.webhook.verify_ssl', true);
        $throwOnFailure = Arr::get($configuration, 'throw_exception_on_failure', config('signal.webhook.throw_exception_on_failure', false));
        // Se execute_sync è true nel config, forza l'esecuzione sincrona anche per i webhook
        $dispatchSync = Arr::get($configuration, 'dispatch_sync', false) || config('signal.execute_sync', false);
        $proxy = Arr::get($configuration, 'proxy');
        $signer = Arr::get($configuration, 'signer');
        $customJob = Arr::get($configuration, 'job');

        if (blank($url)) {
            throw new InvalidArgumentException("Signal action [{$action->id}] webhook URL is missing.");
        }

        $payloadForWebhook = $this->buildPayload($bodyMode, $payload, $eventClass, $action);

        $webhookCall = WebhookCall::create()
            ->url($url)
            ->payload($payloadForWebhook)
            ->useHttpVerb($method)
            ->withHeaders($headers);

        // Spatie richiede sempre un secret, anche se vuoto
        // Se non è specificato, usa una stringa vuota (webhook senza firma)
        $secretToUse = ! blank($secret) ? $secret : '';
        $webhookCall->useSecret($secretToUse);

        if ($queue) {
            $webhookCall->onQueue($queue);
        }

        if ($connection) {
            $webhookCall->onConnection($connection);
        }

        if ($timeout) {
            $webhookCall->timeoutInSeconds((int) $timeout);
        }

        if ($tries) {
            $webhookCall->maximumTries((int) $tries);
        }

        if ($backoffStrategy) {
            $webhookCall->useBackoffStrategy($backoffStrategy);
        }

        if ($signer) {
            $webhookCall->signUsing($signer);
        }

        if ($customJob) {
            $webhookCall->useJob($customJob);
        }

        if (! is_null($verifySsl)) {
            $webhookCall->verifySsl($verifySsl);
        }

        if (! empty($meta) && is_array($meta)) {
            $webhookCall->meta($meta);
        }

        if (! empty($tags) && is_array($tags)) {
            $webhookCall->withTags(array_values($tags));
        }

        if ($proxy) {
            $webhookCall->useProxy($proxy);
        }

        $webhookCall->throwExceptionOnFailure((bool) $throwOnFailure);

        $uuid = $webhookCall->getUuid();

        $dispatchSync ? $webhookCall->dispatchSync() : $webhookCall->dispatch();

        return [
            'uuid' => $uuid,
            'url' => $url,
            'method' => $method,
            'queue' => $queue,
            'connection' => $connection,
            'dispatched_sync' => (bool) $dispatchSync,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function buildPayload(string $mode, array $payload, string $eventClass, SignalAction $action): array
    {
        $configuration = $action->configuration ?? [];
        $payloadConfig = Arr::get($configuration, 'payload_config', []);

        // Configura il payload se ci sono configurazioni
        if (! empty($payloadConfig)) {
            $relationFields = Arr::get($payloadConfig, 'relation_fields', []);

            // Se ci sono relation_fields configurati, espandi automaticamente quelle relazioni
            // (non serve più expand_relations perché tutte le relazioni sono sempre disponibili)
            if (! empty($relationFields) && is_array($relationFields)) {
                $analyzer = app(SignalPayloadFieldAnalyzer::class);
                $analysis = $analyzer->analyzeEvent($eventClass);

                $relationsMap = [];
                $expandNested = []; // Relazioni annidate da espandere automaticamente

                // Per ogni relazione che ha campi selezionati, aggiungila a expand_relations
                foreach ($relationFields as $idField => $fields) {
                    // Converti il formato safe (loan_unit_id) al formato originale (loan.unit_id)
                    $originalIdField = str_replace('_', '.', $idField);

                    if (isset($analysis['relations'][$originalIdField])) {
                        $relation = $analysis['relations'][$originalIdField];
                        if ($relation['model_class']) {
                            $relationsMap[$originalIdField] = $relation['model_class'];

                            // Se la relazione ha 'expand' definito, aggiungilo
                            if (! empty($relation['expand'])) {
                                $expandNested[$originalIdField] = $relation['expand'];
                            }
                        }
                    }
                }

                $payloadConfig['expand_relations'] = $relationsMap;
                $payloadConfig['expand_nested'] = $expandNested;
            } else {
                // Se expand_relations è ancora presente (per retrocompatibilità), convertilo
                $expandRelations = Arr::get($payloadConfig, 'expand_relations', []);
                if (is_array($expandRelations) && ! empty($expandRelations) && ! Arr::isAssoc($expandRelations)) {
                    // È un array di ID, convertilo in formato KeyValue usando l'analyzer
                    $analyzer = app(SignalPayloadFieldAnalyzer::class);
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

            $configurator = app(SignalPayloadConfigurator::class);
            $payload = $configurator->configure($payload, $payloadConfig);
        }

        return match ($mode) {
            'event' => [
                'event' => $this->cleanEventClass($eventClass),
                'timestamp' => now()->toIso8601String(),
                'data' => $payload, // Non pulire il payload, è già stato configurato da SignalPayloadConfigurator
                'metadata' => [
                    'trigger_id' => $action->trigger?->id,
                    'trigger_name' => $action->trigger?->name,
                    'action_id' => $action->id,
                    'action_name' => $action->name,
                ],
            ],
            default => $payload,
        };
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

    /**
     * Pulisce il payload per includere solo i dati essenziali
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function cleanPayload(array $payload): array
    {
        $cleaned = [];

        foreach ($payload as $key => $value) {
            // Per i modelli, estrai solo i campi essenziali
            if (is_array($value) && isset($value['id'])) {
                $cleaned[$key] = $this->extractEssentialFields($value, $key);
            } else {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }

    /**
     * Estrae solo i campi essenziali da un array (modello)
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractEssentialFields(array $data, string $type): array
    {
        // Campi essenziali per tipo
        $essentialFields = match ($type) {
            'loan' => ['id', 'status', 'loaned_at', 'due_at', 'returned_at', 'notes', 'included_accessories'],
            'unit' => ['id', 'inventory_code', 'serial_number', 'short_description', 'status'],
            'borrower', 'loaner' => ['id', 'name', 'email'],
            default => ['id'],
        };

        $result = [];
        foreach ($essentialFields as $field) {
            if (isset($data[$field])) {
                $result[$field] = $data[$field];
            }
        }

        // Aggiungi sempre created_at e updated_at se presenti
        if (isset($data['created_at'])) {
            $result['created_at'] = $data['created_at'];
        }
        if (isset($data['updated_at'])) {
            $result['updated_at'] = $data['updated_at'];
        }

        return $result;
    }
}
