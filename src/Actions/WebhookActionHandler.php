<?php

namespace Base33\FilamentSignal\Actions;

use Base33\FilamentSignal\Contracts\SignalActionHandler;
use Base33\FilamentSignal\Models\SignalAction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class WebhookActionHandler implements SignalActionHandler
{
    public function handle(SignalAction $action, array $payload, string $eventClass): ?array
    {
        $configuration = $action->configuration ?? [];

        $url = Arr::get($configuration, 'url');
        $method = strtoupper(Arr::get($configuration, 'method', 'POST'));
        $headers = Arr::get($configuration, 'headers', []);
        $bodyMode = Arr::get($configuration, 'body', 'json');
        $extra = Arr::get($configuration, 'extra', []);

        if (blank($url)) {
            throw new InvalidArgumentException("Signal action [{$action->id}] webhook URL is missing.");
        }

        $client = Http::withHeaders($headers);

        if ($timeout = Arr::get($configuration, 'timeout')) {
            $client = $client->timeout((int) $timeout);
        }

        $body = match ($bodyMode) {
            'payload' => $payload,
            'event' => [
                'class' => $eventClass,
                'payload' => $payload,
            ],
            default => $payload,
        };

        $response = $client->send($method, $url, [
            'json' => $body,
        ]);

        if ($response->failed()) {
            throw new InvalidArgumentException(sprintf(
                'Webhook action [%s] failed with status %s',
                $action->id,
                $response->status()
            ));
        }

        return [
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
            'headers' => $response->headers(),
            'extra' => $extra,
        ];
    }
}

