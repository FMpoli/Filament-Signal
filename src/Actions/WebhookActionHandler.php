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

            if (! empty($relationFields) && is_array($relationFields)) {
                $analyzer = app(SignalPayloadFieldAnalyzer::class);
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

        $finalPayload = match ($mode) {
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
