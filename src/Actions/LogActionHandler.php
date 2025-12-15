<?php

namespace Voodflow\Voodflow\Actions;

use Voodflow\Voodflow\Contracts\ActionHandler;
use Illuminate\Support\Facades\Log;

/**
 * Log Action Handler
 * 
 * Simple logging handler that outputs to Laravel log.
 * The workflow execution is already tracked in ExecutionNode,
 * this is just for additional custom logging.
 */
class LogActionHandler implements ActionHandler
{
    public function handle(array $config, array $payload, string $eventClass): ?array
    {
        $level = $config['level'] ?? 'info';
        $message = $config['message'] ?? 'Workflow executed';

        // Log with configured level
        Log::log($level, $message, [
            'event' => $eventClass,
            'payload' => $payload,
            'config' => $config,
        ]);

        return [
            'logged' => true,
            'level' => $level,
            'message' => $message,
        ];
    }
}
