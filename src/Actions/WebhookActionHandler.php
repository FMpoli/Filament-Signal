<?php

namespace Voodflow\Voodflow\Actions;

use Voodflow\Voodflow\Contracts\ActionHandler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Action Handler
 * 
 * Sends HTTP requests to configured webhooks.
 * Results are stored in ExecutionNode.output
 */
class WebhookActionHandler implements ActionHandler
{
    public function handle(array $config, array $payload, string $eventClass): ?array
    {
        $url = $config['url'] ?? null;
        $method = strtoupper($config['method'] ?? 'POST');
        $headers = $config['headers'] ?? [];
        $signingSecret = $config['signing_secret'] ?? null;

        if (!$url) {
            return [
                'success' => false,
                'error' => 'No URL configured',
            ];
        }

        // Build request data
        $requestData = match ($config['payload_mode'] ?? 'payload') {
            'envelope' => [
                'event' => class_basename($eventClass),
                'timestamp' => now()->toIso8601String(),
                'data' => $payload,
            ],
            default => $payload,
        };

        // Add signature header if secret configured
        if ($signingSecret) {
            $signature = hash_hmac('sha256', json_encode($requestData), $signingSecret);
            $headers['X-Voodflow-Signature'] = $signature;
        }

        try {
            // Send request
            $response = Http::withHeaders($headers)
                        ->timeout($config['timeout'] ?? 30)
                ->{strtolower($method)}($url, $requestData);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response' => $response->json() ?? $response->body(),
                'url' => $url,
                'method' => $method,
            ];

        } catch (\Exception $e) {
            Log::error('Webhook failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url,
            ];
        }
    }
}
