<?php

namespace Voodflow\Voodflow\Nodes\SendWebhookNode;

use Voodflow\Voodflow\Contracts\NodeInterface;
use Voodflow\Voodflow\Execution\ExecutionContext;
use Voodflow\Voodflow\Execution\ExecutionResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Send Webhook Node
 * 
 * Self-contained webhook sender node.
 * Sends HTTP requests to configured endpoints.
 * 
 * Migrated from WebhookNode POC - now fully functional.
 */
class SendWebhookNode implements NodeInterface
{
    public static function type(): string
    {
        return 'send_webhook';
    }

    public static function name(): string
    {
        return 'Send Webhook';
    }

    public static function defaultConfig(): array
    {
        return [
            'url' => '',
            'method' => 'POST',
            'headers' => [],
            'payload_mode' => 'payload', // or 'envelope'
            'timeout' => 30,
            'signing_secret' => null,
        ];
    }

    public static function metadata(): array
    {
        return [
            'author' => 'Voodflow',
            'version' => '2.0.0',
            'color' => 'blue',
            'icon' => 'heroicon-o-paper-airplane',
            'group' => 'Actions',
            'category' => 'action',
            'description' => 'Send HTTP request to a webhook endpoint',
            'positioning' => [
                'input' => true,
                'output' => true,
            ],
        ];
    }

    /**
     * Execute webhook request
     */
    public function execute(ExecutionContext $context): ExecutionResult
    {
        $url = $context->getConfig('url');
        $method = strtoupper($context->getConfig('method', 'POST'));
        $headers = $context->getConfig('headers', []);
        $payloadMode = $context->getConfig('payload_mode', 'payload');
        $timeout = $context->getConfig('timeout', 30);
        $signingSecret = $context->getConfig('signing_secret');

        // Validate URL
        if (empty($url)) {
            return ExecutionResult::failure('Webhook URL is required');
        }

        // Build request payload
        $requestData = $this->buildPayload($context, $payloadMode);

        // Add signature if secret configured
        if ($signingSecret) {
            $signature = hash_hmac('sha256', json_encode($requestData), $signingSecret);
            $headers['X-Voodflow-Signature'] = $signature;
        }

        try {
            // Send HTTP request
            $response = Http::withHeaders($headers)
                        ->timeout($timeout)
                ->{strtolower($method)}($url, $requestData);

            // Return success with response data
            return ExecutionResult::success([
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response' => $response->json() ?? $response->body(),
                'url' => $url,
                'method' => $method,
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook request failed', [
                'url' => $url,
                'method' => $method,
                'error' => $e->getMessage(),
                'node_id' => $context->node->id,
            ]);

            return ExecutionResult::failure(
                error: "Webhook failed: {$e->getMessage()}",
                output: [
                    'url' => $url,
                    'method' => $method,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Build request payload based on mode
     */
    protected function buildPayload(ExecutionContext $context, string $mode): array
    {
        return match ($mode) {
            'envelope' => [
                'event' => class_basename($context->eventClass),
                'timestamp' => now()->toIso8601String(),
                'data' => $context->input,
                'metadata' => [
                    'workflow_id' => $context->execution->workflow_id,
                    'execution_id' => $context->execution->id,
                    'node_id' => $context->node->id,
                ],
            ],
            default => $context->input,
        };
    }

    /**
     * Validate webhook configuration
     */
    public function validate(array $config): array
    {
        $errors = [];

        if (empty($config['url'])) {
            $errors['url'] = 'URL is required';
        } elseif (!filter_var($config['url'], FILTER_VALIDATE_URL)) {
            $errors['url'] = 'Invalid URL format';
        }

        $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        if (isset($config['method']) && !in_array(strtoupper($config['method']), $allowedMethods)) {
            $errors['method'] = 'Invalid HTTP method';
        }

        return $errors;
    }
}