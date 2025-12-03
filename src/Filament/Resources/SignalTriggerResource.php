<?php

namespace Base33\FilamentSignal\Filament\Resources;

use BackedEnum;
use Base33\FilamentSignal\Filament\Resources\SignalTriggerResource\Pages;
use Base33\FilamentSignal\FilamentSignal;
use Base33\FilamentSignal\Models\SignalTrigger;
use Base33\FilamentSignal\Support\SignalPayloadFieldAnalyzer;
use Base33\FilamentSignal\Support\SignalWebhookTemplateRegistry;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\CodeEntry;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset as SchemaFieldset;
use Filament\Schemas\Components\Grid as SchemaGrid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SignalTriggerResource extends Resource
{
    protected static ?string $model = SignalTrigger::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-sparkles';

    public static function getNavigationGroup(): ?string
    {
        return __('filament-signal::signal.plugin.navigation.group');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // SchemaSection::make(__('filament-signal::signal.sections.trigger_details'))
                SchemaSection::make()
                    ->compact()
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('filament-signal::signal.fields.name')),
                        TextEntry::make('status')
                            ->label(__('filament-signal::signal.fields.status'))
                            ->formatStateUsing(fn(SignalTrigger $record): string => ucfirst($record->status)),
                        TextEntry::make('event_class')
                            ->label(__('filament-signal::signal.fields.event_class')),
                        TextEntry::make('match_type')

                            ->label(__('filament-signal::signal.fields.match_type'))
                            ->formatStateUsing(fn(SignalTrigger $record): ?string => $record->match_type
                                ? __('filament-signal::signal.options.match_type.' . $record->match_type)
                                : null),
                        TextEntry::make('description')
                            ->label(__('filament-signal::signal.fields.description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                SchemaSection::make(__('filament-signal::signal.sections.trigger_conditions'))
                    ->schema([
                        CodeEntry::make('filters')
                            ->label(__('filament-signal::signal.fields.filters'))
                            ->formatStateUsing(function (SignalTrigger $record): ?string {
                                $filters = $record->filters;
                                if (blank($filters)) {
                                    return null;
                                }
                                return is_string($filters)
                                    ? $filters
                                    : json_encode($filters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                            }),
                    ]),
                SchemaSection::make(__('filament-signal::signal.sections.trigger_actions'))
                    ->schema([
                        TextEntry::make('actions')
                            ->label(__('filament-signal::signal.fields.actions'))
                            ->formatStateUsing(function (SignalTrigger $record): string {
                                return view('filament-signal::infolists.actions-list', [
                                    'actions' => $record->actions->toArray(),
                                ])->render();
                            })
                            ->html(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-signal::signal.plugin.navigation.rules');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Group::make([
                    SchemaSection::make(__('filament-signal::signal.sections.trigger_details'))
                        ->compact()
                        ->secondary()
                        ->schema([
                            SchemaGrid::make()
                                ->columns([
                                    'default' => 1,
                                    '@md' => 2,
                                ])
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label(__('filament-signal::signal.fields.name'))
                                        ->required(),
                                    Forms\Components\Select::make('status')
                                        ->label(__('filament-signal::signal.fields.status'))
                                        ->options([
                                            SignalTrigger::STATUS_DRAFT => __('filament-signal::signal.options.status.draft'),
                                            SignalTrigger::STATUS_ACTIVE => __('filament-signal::signal.options.status.active'),
                                            SignalTrigger::STATUS_DISABLED => __('filament-signal::signal.options.status.disabled'),
                                        ])
                                        ->default(SignalTrigger::STATUS_DRAFT)
                                        ->required(),
                                    Forms\Components\Select::make('event_class')
                                        ->label(__('filament-signal::signal.fields.event_class'))
                                        ->options(self::getEventClassOptions())
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->helperText('Seleziona un evento dalla lista o cerca per nome. Gli eventi registrati dai plugin sono mostrati con il loro nome completo.')
                                        ->getSearchResultsUsing(function (string $search): array {
                                            $options = self::getEventClassOptions();
                                            $results = [];

                                            foreach ($options as $class => $name) {
                                                // Cerca nel nome o nella classe
                                                if (
                                                    stripos($name, $search) !== false ||
                                                    stripos($class, $search) !== false
                                                ) {
                                                    $results[$class] = $name;
                                                }
                                            }

                                            return $results;
                                        })
                                        ->getOptionLabelUsing(function (?string $value): ?string {
                                            if (! $value) {
                                                return null;
                                            }

                                            $options = self::getEventClassOptions();

                                            return $options[$value] ?? class_basename($value);
                                        })
                                        ->columnSpanFull(),
                                    Forms\Components\Select::make('metadata.webhook_template')
                                        ->label(__('filament-signal::signal.fields.webhook_template'))
                                        ->placeholder(__('filament-signal::signal.placeholders.webhook_template'))
                                        ->options(self::getWebhookTemplateOptions())
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->helperText(__('filament-signal::signal.helpers.webhook_template'))
                                        ->afterStateUpdated(function ($state, callable $set): void {
                                            static::applyWebhookTemplate($state, $set);
                                        })
                                        ->hidden()
                                        ->columnSpanFull(),
                                    Forms\Components\Textarea::make('description')
                                        ->label(__('filament-signal::signal.fields.description'))
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    SchemaSection::make(__('filament-signal::signal.sections.trigger_conditions'))

                        ->schema([
                            Forms\Components\Select::make('match_type')
                                ->label(__('filament-signal::signal.fields.match_type'))
                                ->options([
                                    SignalTrigger::MATCH_ALL => __('filament-signal::signal.options.match_type.all'),
                                    SignalTrigger::MATCH_ANY => __('filament-signal::signal.options.match_type.any'),
                                ])
                                ->default(SignalTrigger::MATCH_ALL),
                            Builder::make('filters')
                                ->label(function (Get $get): string {
                                    $filters = $get('filters');
                                    if (blank($filters) || (is_array($filters) && empty($filters))) {
                                        return __('filament-signal::signal.fields.no_filters_configured');
                                    }
                                    return __('filament-signal::signal.fields.filters');
                                })
                                ->addActionLabel(__('filament-signal::signal.actions.add_filter'))
                                ->live()
                                ->blocks([
                                    Block::make('equals')
                                        ->label('Equals')
                                        ->schema([
                                            Forms\Components\TextInput::make('field')
                                                ->label('Field')
                                                ->required(),
                                            Forms\Components\TextInput::make('value')
                                                ->label('Value')
                                                ->required(),
                                        ])->columns(2),
                                    Block::make('contains')
                                        ->label('Contains')
                                        ->schema([
                                            Forms\Components\TextInput::make('field')
                                                ->label('Field')
                                                ->required(),
                                            Forms\Components\TextInput::make('value')
                                                ->label('Value')
                                                ->required(),
                                        ])->columns(2),
                                ])
                                ->collapsible(),
                        ]),
                ])->columnSpan(4),
                Group::make([
                    SchemaSection::make(__('filament-signal::signal.sections.trigger_actions'))
                        ->schema([
                            Repeater::make('actions')
                                ->relationship('actions')
                                ->label(__('filament-signal::signal.fields.actions'))
                                ->hiddenLabel()
                                ->orderColumn('execution_order')
                                ->schema(static::actionRepeaterSchema())
                                ->columns(1)
                                ->collapsible()
                                ->addActionLabel(__('filament-signal::signal.actions.add_action'))
                                ->itemLabel(function (array $state): ?string {
                                    $order = $state['execution_order'] ?? 1;
                                    $type = strtoupper($state['action_type'] ?? '');
                                    $name = $state['name'] ?? '';

                                    if ($name) {
                                        return "Order: {$order} • {$type} • {$name}";
                                    }

                                    return "Order: {$order} • {$type}";
                                }),
                        ]),
                ])
                    ->columnSpan(8),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function getWebhookTemplateOptions(): array
    {
        return app(SignalWebhookTemplateRegistry::class)->options();
    }

    /**
     * Raccoglie tutte le opzioni degli eventi disponibili:
     * - Eventi registrati dai plugin tramite FilamentSignal::registerEvent()
     * - Eventi configurati in config('signal.registered_events')
     * - Eventi già usati nei trigger esistenti (dal database)
     *
     * @return array<string, string> Array con chiave = event class, valore = nome visualizzato
     */
    protected static function getEventClassOptions(): array
    {
        $options = [];

        // Eventi registrati dai plugin (con nome e gruppo)
        $registeredOptions = FilamentSignal::eventOptions();
        $options = array_merge($options, $registeredOptions);

        // Eventi dal config
        $configuredEvents = config('signal.registered_events', []);
        foreach ($configuredEvents as $eventClass) {
            if (is_string($eventClass) && ! isset($options[$eventClass])) {
                // Se non è già registrato con un nome, usa il nome della classe
                $shortName = class_basename($eventClass);
                $options[$eventClass] = $shortName;
            }
        }

        // Eventi dal database (già usati in trigger esistenti)
        try {
            $databaseEvents = SignalTrigger::query()
                ->select('event_class')
                ->distinct()
                ->whereNotNull('event_class')
                ->pluck('event_class')
                ->filter()
                ->toArray();

            foreach ($databaseEvents as $eventClass) {
                if (! isset($options[$eventClass])) {
                    // Se non è già presente, usa il nome della classe
                    $shortName = class_basename($eventClass);
                    $options[$eventClass] = $shortName;
                }
            }
        } catch (\Throwable $e) {
            // Ignora errori se il database non è ancora pronto
        }

        // Ordina per nome
        asort($options);

        return $options;
    }

    protected static function applyWebhookTemplate(?string $templateId, callable $set): void
    {
        $template = app(SignalWebhookTemplateRegistry::class)->find($templateId);

        if (! $template) {
            return;
        }

        $set('event_class', $template->eventClass);

        if ($template->description) {
            $set('description', $template->description);
        }

        foreach ($template->defaults as $path => $value) {
            $set($path, $value);
        }
    }

    protected static function actionRepeaterSchema(): array
    {
        return [
            SchemaGrid::make()
                ->columns(12)
                ->components([
                    Group::make([
                        // SchemaSection::make(__('filament-signal::signal.sections.general'))
                        //     ->compact()
                        // schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament-signal::signal.fields.name'))
                            ->required(),
                        Forms\Components\TextInput::make('execution_order')
                            ->label(__('filament-signal::signal.fields.execution_order'))
                            ->numeric()
                            ->default(1),
                        Forms\Components\Select::make('action_type')
                            ->label(__('filament-signal::signal.fields.action_type'))
                            ->options(self::getActionTypeOptions())
                            ->required()
                            ->live(),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('filament-signal::signal.fields.status'))
                            ->default(true),
                        Forms\Components\Select::make('template_id')
                            ->label(__('filament-signal::signal.fields.template'))
                            ->relationship('template', 'name')
                            ->searchable()
                            ->visible(fn(Get $get): bool => $get('action_type') === 'email')
                            ->required(fn(Get $get): bool => $get('action_type') === 'email'),
                        // ]),
                    ])->columnSpan(4),

                    Group::make([
                        SchemaGrid::make()
                            ->columns([
                                'default' => 1,
                                '@md' => 2,
                                '@xl' => 2,
                            ])
                            ->visible(fn(Get $get): bool => $get('action_type') === 'webhook')
                            ->schema([
                                Forms\Components\TextInput::make('configuration.url')
                                    ->label('Endpoint URL')
                                    ->url()
                                    ->required(fn(Get $get): bool => $get('action_type') === 'webhook')
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('configuration.method')
                                    ->label('HTTP Method')
                                    ->options([
                                        'POST' => 'POST',
                                        'PUT' => 'PUT',
                                        'PATCH' => 'PATCH',
                                        'DELETE' => 'DELETE',
                                    ])
                                    ->default('POST'),
                                Forms\Components\Select::make('configuration.body')
                                    ->label('Payload mode')
                                    ->options([
                                        'payload' => 'Event payload',
                                        'event' => 'Envelope (class + payload)',
                                    ])
                                    ->default('event'),
                                Forms\Components\TextInput::make('configuration.secret')
                                    ->label('Signing secret')
                                    ->password()
                                    ->default(fn() => config('signal.webhook.secret') ?: Str::random(40))
                                    ->helperText('Generato automaticamente se vuoto. Utilizzato per generare la firma con spatie/laravel-webhook-server.'),
                                Forms\Components\Toggle::make('configuration.verify_ssl')
                                    ->label('Verify SSL')
                                    ->default(true),
                            ]),
                        SchemaSection::make(__('filament-signal::signal.sections.payload_configuration'))

                            ->description('Seleziona i campi da includere nel payload e le relazioni da espandere')

                            ->schema(function (Get $get): array {
                                $components = [
                                    Forms\Components\CheckboxList::make('configuration.payload_config.include_fields')
                                        ->label('Campi essenziali')
                                        ->options(function (Get $get): array {
                                            $eventClass = $get('../../event_class');
                                            if (! $eventClass) {
                                                return [];
                                            }

                                            $analyzer = app(SignalPayloadFieldAnalyzer::class);
                                            $analysis = $analyzer->analyzeEvent($eventClass);

                                            // Mostra solo i campi essenziali del modello principale (non delle relazioni)
                                            $options = [];
                                            foreach ($analysis['fields'] as $field => $data) {
                                                // Mostra solo i campi del modello principale (es: model.id, model.status)
                                                // Escludi i campi delle relazioni (es: model.relation.field)
                                                if (substr_count($field, '.') > 1) {
                                                    continue;
                                                }

                                                // Salta i campi tecnici meno usati (created_at, updated_at, attachments, etc.)
                                                $parts = explode('.', $field);
                                                if (count($parts) === 2) {
                                                    $fieldName = $parts[1];
                                                    if (in_array($fieldName, ['created_at', 'updated_at', 'attachments'])) {
                                                        continue;
                                                    }
                                                }

                                                $options[$field] = $data['label'];
                                            }

                                            return $options;
                                        })
                                        ->columns(2)
                                        ->gridDirection('row')
                                        ->helperText('Seleziona i campi essenziali del prestito da includere.')
                                        ->columnSpanFull()
                                        ->live(onBlur: false)
                                        ->dehydrated()
                                        ->rules([])
                                        ->afterStateHydrated(function ($state, callable $set, Get $get) {
                                            // Pulisci i valori non validi quando viene caricato lo stato
                                            $eventClass = $get('../../event_class');
                                            if (! $eventClass || ! is_array($state)) {
                                                return;
                                            }

                                            $analyzer = app(SignalPayloadFieldAnalyzer::class);
                                            $analysis = $analyzer->analyzeEvent($eventClass);

                                            $validFields = [];
                                            foreach ($analysis['fields'] as $field => $data) {
                                                if (substr_count($field, '.') <= 1) {
                                                    // Salta i campi tecnici meno usati
                                                    $parts = explode('.', $field);
                                                    if (count($parts) === 2) {
                                                        $fieldName = $parts[1];
                                                        if (! in_array($fieldName, ['created_at', 'updated_at', 'attachments'])) {
                                                            $validFields[] = $field;
                                                        }
                                                    } else {
                                                        $validFields[] = $field;
                                                    }
                                                }
                                            }

                                            $filtered = array_intersect($state, $validFields);
                                            if (count($filtered) !== count($state)) {
                                                $set('configuration.payload_config.include_fields', array_values($filtered));
                                            }
                                        })
                                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                            // Pulisci i valori che non sono più nelle opzioni disponibili quando cambia l'evento
                                            $eventClass = $get('../../event_class');
                                            if (! $eventClass || ! is_array($state)) {
                                                return;
                                            }

                                            $analyzer = app(SignalPayloadFieldAnalyzer::class);
                                            $analysis = $analyzer->analyzeEvent($eventClass);

                                            $validFields = [];
                                            foreach ($analysis['fields'] as $field => $data) {
                                                if (substr_count($field, '.') <= 1) {
                                                    // Salta i campi tecnici meno usati
                                                    $parts = explode('.', $field);
                                                    if (count($parts) === 2) {
                                                        $fieldName = $parts[1];
                                                        if (! in_array($fieldName, ['created_at', 'updated_at', 'attachments'])) {
                                                            $validFields[] = $field;
                                                        }
                                                    } else {
                                                        $validFields[] = $field;
                                                    }
                                                }
                                            }

                                            $filtered = array_intersect($state, $validFields);
                                            if (count($filtered) !== count($state)) {
                                                $set('configuration.payload_config.include_fields', array_values($filtered));
                                            }
                                        }),
                                ];

                                // Aggiungi le CheckboxList per le relazioni direttamente nella stessa sezione
                                $eventClass = $get('../../event_class');
                                if ($eventClass) {
                                    $analyzer = app(SignalPayloadFieldAnalyzer::class);
                                    $analysis = $analyzer->analyzeEvent($eventClass);

                                    if (!empty($analysis['relations'])) {
                                        foreach ($analysis['relations'] as $relation) {
                                            $fieldOptions = $relation['field_options'] ?? [];
                                            if (empty($fieldOptions)) {
                                                continue;
                                            }

                                            $alias = $relation['alias'] ?? 'relation';
                                            $formKey = $relation['form_key'] ?? str_replace(['.', ' '], '_', $relation['id_field']);

                                            $options = [];
                                            foreach ($fieldOptions as $fieldKey => $label) {
                                                // Formatta l'etichetta per renderla più leggibile
                                                $formattedLabel = $label;

                                                if (str_contains($label, '.')) {
                                                    $parts = explode('.', $label);
                                                    $formattedParts = [];
                                                    foreach ($parts as $part) {
                                                        $trimmed = trim($part);
                                                        $formattedParts[] = ucfirst(strtolower($trimmed));
                                                    }
                                                    $formattedLabel = implode(' → ', $formattedParts);
                                                } elseif (str_contains($label, ' - ')) {
                                                    $formattedLabel = str_replace(' - ', ' → ', $label);
                                                }

                                                $options["{$alias}.{$fieldKey}"] = $formattedLabel;
                                            }

                                            $components[] = Forms\Components\CheckboxList::make($formKey)
                                                ->label($relation['label'])
                                                ->options($options)
                                                ->columns(2)
                                                ->gridDirection('row')
                                                ->bulkToggleable()
                                                ->statePath("configuration.payload_config.relation_fields.{$formKey}")
                                                ->rules([])
                                                ->live(onBlur: false)
                                                ->dehydrated()
                                                ->columnSpanFull()
                                                ->afterStateHydrated(function (?array $state, callable $set) use ($options, $formKey): void {
                                                    if (blank($state)) {
                                                        return;
                                                    }

                                                    $valid = array_intersect($state, array_keys($options));
                                                    if (count($valid) !== count($state)) {
                                                        $set("configuration.payload_config.relation_fields.{$formKey}", array_values($valid));
                                                    }
                                                })
                                                ->afterStateUpdated(function ($state, callable $set) use ($options, $formKey): void {
                                                    if (! is_array($state)) {
                                                        return;
                                                    }

                                                    $valid = array_intersect($state, array_keys($options));
                                                    if (count($valid) !== count($state)) {
                                                        $set("configuration.payload_config.relation_fields.{$formKey}", array_values($valid));
                                                    }
                                                });
                                        }
                                    }
                                }

                                return $components;
                            })
                            ->visible(fn(Get $get): bool => filled($get('../../event_class'))),
                        SchemaSection::make(__('filament-signal::signal.sections.webhook_configuration_advanced'))
                            ->collapsible()
                            ->collapsed()
                            ->visible(false) // Temporaneamente nascosto - riattivare quando necessario
                            ->schema([
                                SchemaGrid::make()
                                    ->columns([
                                        'default' => 1,
                                        '@md' => 2,
                                        '@xl' => 3,
                                    ])
                                    ->schema([
                                        Forms\Components\TextInput::make('configuration.queue')
                                            ->label('Queue')
                                            ->placeholder('default'),
                                        Forms\Components\TextInput::make('configuration.connection')
                                            ->label('Queue connection'),
                                        Forms\Components\TextInput::make('configuration.timeout')
                                            ->label('Timeout (seconds)')
                                            ->numeric()
                                            ->minValue(1),
                                        Forms\Components\TextInput::make('configuration.tries')
                                            ->label('Max attempts')
                                            ->numeric()
                                            ->minValue(1)
                                            ->placeholder('Esempio: 3'),
                                        Forms\Components\TextInput::make('configuration.backoff_strategy')
                                            ->label('Backoff strategy class'),
                                        Forms\Components\Toggle::make('configuration.throw_exception_on_failure')
                                            ->label('Throw on failure')
                                            ->default(false),
                                        Forms\Components\Toggle::make('configuration.dispatch_sync')
                                            ->label('Dispatch synchronously')
                                            ->helperText('Esegue la chiamata nella stessa richiesta invece che in coda.'),
                                        Forms\Components\TagsInput::make('configuration.tags')
                                            ->label('Horizon tags')
                                            ->placeholder('tag')
                                            ->columnSpan([
                                                'default' => 1,
                                                '@md' => 2,
                                                '@xl' => 1,
                                            ]),
                                        Forms\Components\KeyValue::make('configuration.headers')
                                            ->label('Headers')
                                            ->keyLabel('Header')
                                            ->valueLabel('Value')
                                            ->addActionLabel('Add header')
                                            ->columnSpan([
                                                'default' => 1,
                                                '@md' => 2,
                                                '@xl' => 2,
                                            ]),
                                        Forms\Components\KeyValue::make('configuration.meta')
                                            ->label('Meta')
                                            ->keyLabel('Key')
                                            ->valueLabel('Value')
                                            ->addActionLabel('Add meta')
                                            ->columnSpan([
                                                'default' => 1,
                                                '@md' => 2,
                                                '@xl' => 3,
                                            ]),
                                        Forms\Components\TextInput::make('configuration.proxy')
                                            ->label('Proxy')
                                            ->placeholder('http://proxy.server:3128')
                                            ->columnSpan([
                                                'default' => 1,
                                                '@md' => 2,
                                                '@xl' => 3,
                                            ]),
                                        Forms\Components\TextInput::make('configuration.job')
                                            ->label('Custom job class'),
                                        Forms\Components\TextInput::make('configuration.signer')
                                            ->label('Custom signer class'),
                                    ]),
                            ]),
                        SchemaSection::make(__('filament-signal::signal.sections.email_configuration'))
                            ->secondary()
                            ->visible(fn(Get $get): bool => $get('action_type') === 'email')
                            ->columnSpan(1)
                            ->schema([
                                SchemaGrid::make()
                                    ->columns([
                                        'default' => 1,
                                        '@md' => 2,
                                    ])
                                    ->schema([
                                        Forms\Components\TextInput::make('configuration.subject_override')
                                            ->label(__('filament-signal::signal.fields.subject'))
                                            ->placeholder('Leave empty to use template subject')
                                            ->columnSpanFull(),
                                        SchemaGrid::make()
                                            ->columns([
                                                'default' => 1,
                                                '@md' => 3,
                                            ])
                                            ->schema([
                                                Forms\Components\KeyValue::make('configuration.recipients.to')
                                                    ->label('To recipients')
                                                    ->keyLabel('Email')
                                                    ->valueLabel('Name')
                                                    ->addActionLabel('Add recipient'),
                                                Forms\Components\KeyValue::make('configuration.recipients.cc')
                                                    ->label('CC recipients')
                                                    ->keyLabel('Email')
                                                    ->valueLabel('Name')
                                                    ->addActionLabel('Add recipient'),
                                                Forms\Components\KeyValue::make('configuration.recipients.bcc')
                                                    ->label('BCC recipients')
                                                    ->keyLabel('Email')
                                                    ->valueLabel('Name')
                                                    ->addActionLabel('Add recipient'),
                                            ]),
                                    ]),
                            ]),

                    ])->columnSpan(8),

                    // Group::make([
                    //     SchemaSection::make()->heading('RIGHT 1')->schema([]),

                    //     Group::make([
                    //         SeSchemaSectionction::make()->heading('RIGHT 2')->schema([]),

                    //         SchemaSection::make()->heading('RIGHT 3')->schema([]),
                    //     ])->columns(2),
                    // ])
                    //     ->columnSpan(8),

                    // ->components([
                    //     Group::make([
                    //         SchemaSection::make(__('filament-signal::signal.sections.general'))
                    //             ->compact()
                    //             ->schema([
                    //                 Forms\Components\TextInput::make('name')
                    //                     ->label(__('filament-signal::signal.fields.name'))
                    //                     ->required(),
                    //                 Forms\Components\TextInput::make('execution_order')
                    //                     ->label(__('filament-signal::signal.fields.execution_order'))
                    //                     ->numeric()
                    //                     ->default(1),
                    //                 Forms\Components\Select::make('action_type')
                    //                     ->label(__('filament-signal::signal.fields.action_type'))
                    //                     ->options(self::getActionTypeOptions())
                    //                     ->required()
                    //                     ->live(),
                    //                 Forms\Components\Toggle::make('is_active')
                    //                     ->label(__('filament-signal::signal.fields.status'))
                    //                     ->default(true),
                    //                 Forms\Components\Select::make('template_id')
                    //                     ->label(__('filament-signal::signal.fields.template'))
                    //                     ->relationship('template', 'name')
                    //                     ->searchable()
                    //                     ->visible(fn(Get $get): bool => $get('action_type') === 'email')
                    //                     ->required(fn(Get $get): bool => $get('action_type') === 'email'),
                    //             ])
                    //             ->columnSpan(4),
                    //             Group::make([
                    //                 Section::make()->heading('RIGHT 1')->schema([]),

                    //                 Group::make([
                    //                     Section::make()->heading('RIGHT 2')->schema([]),

                    //                     Section::make()->heading('RIGHT 3')->schema([]),
                    //                 ])->columns(2),
                    //             ])
                    //                 ->columnSpan(8),
                    //         ])
                    //     ]),

                    //     SchemaSection::make(__('filament-signal::signal.sections.email_configuration'))
                    //         ->secondary()
                    //         ->visible(fn(Get $get): bool => $get('action_type') === 'email')
                    //         ->columnSpan(1)
                    //         ->schema([
                    //             SchemaGrid::make()
                    //                 ->columns([
                    //                     'default' => 1,
                    //                     '@md' => 2,
                    //                 ])
                    //                 ->schema([
                    //                     Forms\Components\TextInput::make('configuration.subject_override')
                    //                         ->label(__('filament-signal::signal.fields.subject'))
                    //                         ->placeholder('Leave empty to use template subject')
                    //                         ->columnSpanFull(),
                    //                     SchemaGrid::make()
                    //                         ->columns([
                    //                             'default' => 1,
                    //                             '@md' => 3,
                    //                         ])
                    //                         ->schema([
                    //                             Forms\Components\KeyValue::make('configuration.recipients.to')
                    //                                 ->label('To recipients')
                    //                                 ->keyLabel('Email')
                    //                                 ->valueLabel('Name')
                    //                                 ->addActionLabel('Add recipient'),
                    //                             Forms\Components\KeyValue::make('configuration.recipients.cc')
                    //                                 ->label('CC recipients')
                    //                                 ->keyLabel('Email')
                    //                                 ->valueLabel('Name')
                    //                                 ->addActionLabel('Add recipient'),
                    //                             Forms\Components\KeyValue::make('configuration.recipients.bcc')
                    //                                 ->label('BCC recipients')
                    //                                 ->keyLabel('Email')
                    //                                 ->valueLabel('Name')
                    //                                 ->addActionLabel('Add recipient'),
                    //                         ]),
                    //                 ]),
                    //         ]),

                ]),
        ];
    }

    protected static function getActionTypeOptions(): array
    {
        return collect(config('signal.action_handlers', []))
            ->keys()
            ->mapWithKeys(fn(string $type) => [$type => ucfirst($type)])
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-signal::signal.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_class')
                    ->label(__('filament-signal::signal.fields.event_class'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('filament-signal::signal.fields.status'))
                    ->colors([
                        'secondary' => SignalTrigger::STATUS_DRAFT,
                        'success' => SignalTrigger::STATUS_ACTIVE,
                        'danger' => SignalTrigger::STATUS_DISABLED,
                    ]),
                Tables\Columns\TextColumn::make('actions_count')
                    ->counts('actions')
                    ->label(__('filament-signal::signal.fields.actions')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-signal::signal.fields.updated_at'))
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament-signal::signal.fields.status'))
                    ->options([
                        SignalTrigger::STATUS_DRAFT => __('filament-signal::signal.options.status.draft'),
                        SignalTrigger::STATUS_ACTIVE => __('filament-signal::signal.options.status.active'),
                        SignalTrigger::STATUS_DISABLED => __('filament-signal::signal.options.status.disabled'),
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn(SignalTrigger $record): string => static::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(false),
                EditAction::make()
                    ->url(fn(SignalTrigger $record): string => static::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(false),
                Action::make('clone')
                    ->label(__('filament-signal::signal.actions.clone'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(__('filament-signal::signal.actions.clone_trigger'))
                    ->modalDescription(__('filament-signal::signal.actions.clone_trigger_description'))
                    ->action(function (SignalTrigger $record) {
                        // Replica solo gli attributi fillable, escludendo attributi virtuali come actions_count
                        $cloned = new SignalTrigger;
                        $cloned->fill($record->only($record->getFillable()));

                        // Aggiungi "(Copy)" al nome se non è già presente
                        $cloned->name = str_ends_with($record->name, ' (Copy)')
                            ? $record->name . ' (Copy)'
                            : $record->name . ' (Copy)';

                        $cloned->status = SignalTrigger::STATUS_DRAFT;
                        $cloned->activated_at = null;
                        $cloned->save();

                        // Clona anche le azioni associate
                        foreach ($record->actions as $action) {
                            // Replica solo gli attributi fillable, escludendo attributi virtuali
                            $clonedAction = new \Base33\FilamentSignal\Models\SignalAction;
                            $clonedAction->fill($action->only($action->getFillable()));
                            $clonedAction->trigger_id = $cloned->id;

                            // Aggiungi "(Copy)" al nome dell'azione se non è già presente
                            $clonedAction->name = str_ends_with($action->name, ' (Copy)')
                                ? $action->name . ' (Copy)'
                                : $action->name . ' (Copy)';

                            $clonedAction->save();
                        }

                        \Filament\Notifications\Notification::make()
                            ->title(__('filament-signal::signal.actions.clone_success'))
                            ->success()
                            ->send();

                        return redirect()->route(static::getRouteBaseName() . '.edit', ['record' => $cloned]);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSignalTriggers::route('/'),
            'create' => Pages\CreateSignalTrigger::route('/create'),
            'view' => Pages\ViewSignalTrigger::route('/{record}'),
            'edit' => Pages\EditSignalTrigger::route('/{record}/edit'),
        ];
    }
}
