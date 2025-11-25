<?php

return [
    'plugin' => [
        'name' => 'Signal',
        'navigation' => [
            'group' => 'Automazioni',
            'rules' => 'Regole di automazione',
            'templates' => 'Template',
            'logs' => 'Log esecuzioni',
        ],
    ],
    'fields' => [
        'name' => 'Nome',
        'description' => 'Descrizione',
        'status' => 'Stato',
        'event' => 'Evento',
        'filters' => 'Filtri',
        'match_type' => 'Tipo di corrispondenza',
        'actions' => 'Azioni',
        'action_type' => 'Tipo di azione',
        'configuration' => 'Configurazione',
        'template' => 'Template',
        'channel' => 'Canale',
    ],
    'options' => [
        'status' => [
            'draft' => 'Bozza',
            'active' => 'Attiva',
            'disabled' => 'Disattivata',
        ],
        'match_type' => [
            'all' => 'Tutte le condizioni',
            'any' => 'Almeno una condizione',
        ],
        'action_status' => [
            'pending' => 'In attesa',
            'success' => 'Completata',
            'failed' => 'Fallita',
        ],
    ],
];
