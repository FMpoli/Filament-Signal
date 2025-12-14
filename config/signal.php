<?php

use Voodflow\Voodflow\Actions\LogActionHandler;
use Voodflow\Voodflow\Actions\WebhookActionHandler;
use Voodflow\Voodflow\Models\Edge;
use Voodflow\Voodflow\Models\Execution;
use Voodflow\Voodflow\Models\ExecutionNode;
use Voodflow\Voodflow\Models\Node;
use Voodflow\Voodflow\Models\Workflow;

return [
    'auto_discover_events' => env('FILAMENT_SIGNAL_AUTO_DISCOVER_EVENTS', false),

    'queue_connection' => env('FILAMENT_SIGNAL_QUEUE', null),

    /*
     * Se true, esegue i trigger immediatamente senza usare le code.
     * Utile per sviluppo o quando non hai un worker configurato.
     * Se false, usa le code (richiede un worker in esecuzione).
     */
    'execute_sync' => env('FILAMENT_SIGNAL_EXECUTE_SYNC', false),

    'registered_events' => [
        // \App\Events\LoanCreated::class,
    ],

    'models' => [
        'workflow' => Workflow::class,
        'node' => Node::class,
        'edge' => Edge::class,
        'execution' => Execution::class,
        'execution_node' => ExecutionNode::class,
    ],

    'table_names' => [
        'workflows' => 'voodflow_workflows',      // era signal_workflows
        'nodes' => 'voodflow_nodes',              // era signal_nodes
        'edges' => 'voodflow_edges',              // era signal_edges
        'executions' => 'voodflow_executions',    // era signal_executions
        'execution_nodes' => 'voodflow_execution_nodes',
        'model_integrations' => 'voodflow_model_integrations',
        'triggers' => 'voodflow_triggers',
        'actions' => 'voodflow_actions',
        'templates' => 'voodflow_templates',
        'action_logs' => 'voodflow_action_logs',
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

    /*
     * Action handlers registrati nel sistema.
     *
     * Per aggiungere nuovi action handlers da plugin esterni, puoi:
     * 1. Creare un Service Provider nel tuo plugin
     * 2. Nel metodo boot(), aggiungere:
     *
     *    config(['signal.action_handlers.telegram' => \YourPlugin\Actions\TelegramActionHandler::class]);
     *
     * Oppure pubblicare il file di configurazione e aggiungere manualmente:
     *
     *    'telegram' => \YourPlugin\Actions\TelegramActionHandler::class,
     *
     * Il tuo handler deve implementare SignalActionHandler interface.
     */
    'action_handlers' => [
        'log' => LogActionHandler::class,
        'webhook' => WebhookActionHandler::class,
    ],

    'webhook' => [
        'secret' => env('FILAMENT_SIGNAL_WEBHOOK_SECRET'),
        'queue' => env('FILAMENT_SIGNAL_WEBHOOK_QUEUE'),
        'connection' => env('FILAMENT_SIGNAL_WEBHOOK_CONNECTION'),
        'timeout' => env('FILAMENT_SIGNAL_WEBHOOK_TIMEOUT'),
        'tries' => env('FILAMENT_SIGNAL_WEBHOOK_TRIES'),
        'backoff_strategy' => env('FILAMENT_SIGNAL_WEBHOOK_BACKOFF', 'Spatie\\WebhookServer\\BackoffStrategy\\ExponentialBackoffStrategy'),
        'verify_ssl' => env('FILAMENT_SIGNAL_WEBHOOK_VERIFY_SSL', true),
        'throw_exception_on_failure' => env('FILAMENT_SIGNAL_WEBHOOK_THROW_ON_FAILURE', false),
    ],

    'webhook_templates' => [
        // [
        //     'id' => 'user-created',
        //     'name' => 'User created',
        //     'event_class' => \App\Events\UserCreated::class,
        //     'description' => 'Triggered when a user is created.',
        //     'defaults' => [
        //         'metadata.some_key' => 'value',
        //     ],
        // ],
    ],

    /*
     * Lista di modelli da escludere dalla selezione automatica nella creazione di Model Integration.
     * Utile per escludere modelli interni o modelli che non devono essere tracciati.
     */
    'excluded_models' => [
        // \App\Models\InternalModel::class,
    ],
];
