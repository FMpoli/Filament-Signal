<?php

use Base33\FilamentSignal\Actions\LogActionHandler;
use Base33\FilamentSignal\Actions\WebhookActionHandler;
use Base33\FilamentSignal\Models\SignalEdge;
use Base33\FilamentSignal\Models\SignalExecution;
use Base33\FilamentSignal\Models\SignalExecutionNode;
use Base33\FilamentSignal\Models\SignalNode;
use Base33\FilamentSignal\Models\SignalWorkflow;

return [
    'auto_discover_events' => env('VOODFLOW_AUTO_DISCOVER_EVENTS', false),

    'queue_connection' => env('VOODFLOW_QUEUE', null),

    /*
     * Se true, esegue i trigger immediatamente senza usare le code.
     * Utile per sviluppo o quando non hai un worker configurato.
     * Se false, usa le code (richiede un worker in esecuzione).
     */
    'execute_sync' => env('VOODFLOW_EXECUTE_SYNC', false),

    'registered_events' => [
        // \App\Events\LoanCreated::class,
    ],

    'models' => [
        'workflow' => SignalWorkflow::class,
        'node' => SignalNode::class,
        'edge' => SignalEdge::class,
        'execution' => SignalExecution::class,
        'execution_node' => SignalExecutionNode::class,
    ],

    'table_names' => [
        'workflows' => 'voodflow_workflows',
        'nodes' => 'voodflow_nodes',
        'edges' => 'voodflow_edges',
        'executions' => 'voodflow_executions',
        'execution_nodes' => 'voodflow_execution_nodes',
        'model_integrations' => 'voodflow_model_integrations',
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
        'secret' => env('VOODFLOW_WEBHOOK_SECRET'),
        'queue' => env('VOODFLOW_WEBHOOK_QUEUE'),
        'connection' => env('VOODFLOW_WEBHOOK_CONNECTION'),
        'timeout' => env('VOODFLOW_WEBHOOK_TIMEOUT'),
        'tries' => env('VOODFLOW_WEBHOOK_TRIES'),
        'backoff_strategy' => env('VOODFLOW_WEBHOOK_BACKOFF', 'Spatie\\WebhookServer\\BackoffStrategy\\ExponentialBackoffStrategy'),
        'verify_ssl' => env('VOODFLOW_WEBHOOK_VERIFY_SSL', true),
        'throw_exception_on_failure' => env('VOODFLOW_WEBHOOK_THROW_ON_FAILURE', false),
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
