<?php

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
     * DEPRECATED: Action handlers are now built into self-contained nodes
     * This config section is no longer used and will be removed in future versions.
     */
    'action_handlers' => [
        // Left empty - handlers moved into node classes
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

    /*
     * Lista di modelli da escludere dalla selezione automatica nella creazione di Model Integration.
     * Utile per escludere modelli interni o modelli che non devono essere tracciati.
     */
    'excluded_models' => [
        // \App\Models\InternalModel::class,
    ],
];
