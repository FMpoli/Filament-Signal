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
    'fields' => [
        'name' => 'Name',
        'description' => 'Description',
        'status' => 'Status',
        'event' => 'Event',
        'filters' => 'Filters',
        'match_type' => 'Match type',
        'actions' => 'Actions',
        'action_type' => 'Action type',
        'configuration' => 'Configuration',
        'template' => 'Template',
        'channel' => 'Channel',
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
