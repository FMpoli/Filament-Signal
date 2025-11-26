<?php

return [
    'plugin' => [
        'name' => 'Signal',
        'navigation' => [
            'group' => 'Automation',
            'rules' => 'Automation Rules',
            'templates' => 'Templates',
            'logs' => 'Execution Logs',
        ],
    ],
    'actions' => [
        'add_action' => 'Add action',
        'view_log' => 'View log',
    ],
    'fields' => [
        'name' => 'Name',
        'description' => 'Description',
        'status' => 'Status',
        'slug' => 'Slug',
        'subject' => 'Subject',
        'content_html' => 'HTML content',
        'content_text' => 'Plain text content',
        'updated_at' => 'Updated at',
        'event' => 'Event',
        'filters' => 'Filters',
        'match_type' => 'Match type',
        'actions' => 'Actions',
        'action_type' => 'Action type',
        'configuration' => 'Configuration',
        'template' => 'Template',
        'channel' => 'Channel',
        'event_class' => 'Event class',
        'execution_order' => 'Execution order',
        'activated_at' => 'Activated at',
        'action_logs' => 'Execution logs',
        'executed_at' => 'Executed at',
        'status_message' => 'Status message',
        'response_preview' => 'Response preview',
        'payload_preview' => 'Payload preview',
        'webhook_template' => 'Model preset',
    ],
    'sections' => [
        'template_content' => 'Template content',
        'trigger_details' => 'Trigger details',
        'trigger_conditions' => 'Conditions',
        'trigger_actions' => 'Actions',
        'email_configuration' => 'Email configuration',
        'webhook_configuration' => 'Webhook configuration',
        'webhook_configuration_advanced' => 'Advanced settings',
        'log_details' => 'Log details',
        'payload' => 'Payload',
        'response' => 'Response',
    ],
    'helpers' => [
        'webhook_template' => 'Select a predefined model/event to auto-fill the event class and defaults.',
    ],
    'placeholders' => [
        'webhook_template' => 'Select a preset',
    ],
    'options' => [
        'status' => [
            'draft' => 'Draft',
            'active' => 'Active',
            'disabled' => 'Disabled',
        ],
        'match_type' => [
            'all' => 'All conditions',
            'any' => 'Any condition',
        ],
        'action_status' => [
            'pending' => 'Pending',
            'success' => 'Successful',
            'failed' => 'Failed',
        ],
    ],
];
