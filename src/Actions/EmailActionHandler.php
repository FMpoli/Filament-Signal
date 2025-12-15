<?php

namespace Voodflow\Voodflow\Actions;

use Voodflow\Voodflow\Contracts\ActionHandler;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Email Action Handler
 * 
 * Sends emails using configured templates.
 * Results stored in ExecutionNode.output
 */
class EmailActionHandler implements ActionHandler
{
    public function handle(array $config, array $payload, string $eventClass): ?array
    {
        $to = $config['to'] ?? null;
        $subject = $config['subject'] ?? 'Workflow Notification';
        $template = $config['template'] ?? null;

        if (!$to) {
            return [
                'success' => false,
                'error' => 'No recipient configured',
            ];
        }

        try {
            // TODO: Implement email sending with template
            // For now, just log
            Log::info('Email would be sent', [
                'to' => $to,
                'subject' => $subject,
                'payload' => $payload,
            ]);

            return [
                'success' => true,
                'to' => $to,
                'subject' => $subject,
                'note' => 'Email handler not yet fully implemented',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
