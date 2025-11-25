<?php

use Base33\FilamentSignal\Actions\EmailActionHandler;
use Base33\FilamentSignal\Actions\WebhookActionHandler;
use Base33\FilamentSignal\Models\SignalAction;
use Base33\FilamentSignal\Models\SignalActionLog;
use Base33\FilamentSignal\Models\SignalTemplate;
use Base33\FilamentSignal\Models\SignalTrigger;

return [
    'auto_discover_events' => env('FILAMENT_SIGNAL_AUTO_DISCOVER_EVENTS', false),

    'queue_connection' => env('FILAMENT_SIGNAL_QUEUE', null),

    'registered_events' => [
        // \App\Events\LoanCreated::class,
    ],

    'models' => [
        'trigger' => SignalTrigger::class,
        'action' => SignalAction::class,
        'action_log' => SignalActionLog::class,
        'template' => SignalTemplate::class,
    ],

    'table_names' => [
        'triggers' => 'signal_triggers',
        'actions' => 'signal_actions',
        'action_logs' => 'signal_action_logs',
        'templates' => 'signal_templates',
    ],

    'editor' => [
        'tiptap' => [
            'toolbar_buttons' => [
                'bold',
                'italic',
                'underline',
                'strike',
                'bulletList',
                'orderedList',
                'h2',
                'h3',
                'blockquote',
                'link',
                'codeBlock',
            ],
        ],
    ],

    'action_handlers' => [
        'email' => EmailActionHandler::class,
        'webhook' => WebhookActionHandler::class,
    ],
];
