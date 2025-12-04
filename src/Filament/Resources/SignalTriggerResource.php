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
use Filament\Forms\Components\Repeater;
use Base33\FilamentSignal\Filament\Infolists\Components\ActionsListEntry;
use Base33\FilamentSignal\Filament\Infolists\Components\FiltersListEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\TextSize;

use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
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
                Grid::make()
                    ->schema([
                        Group::make([
                            Section::make()
                                ->heading(function (SignalTrigger $record): \Illuminate\Contracts\Support\Htmlable {
                                    $statusColor = match ($record->status) {
                                        'active' => 'success',
                                        'disabled' => 'danger',
                                        'draft' => 'warning',
                                        default => 'gray',
                                    };
                                    $statusLabel = ucfirst($record->status);

                                    return new \Illuminate\Support\HtmlString(
                                        view('filament-signal::infolists.section-header', [
                                            'name' => $record->name,
                                            'status' => $statusLabel,
                                            'statusColor' => $statusColor,
                                        ])->render()
                                    );
                                })
                                ->compact()
                                ->schema([
                                    TextEntry::make('description')
                                        ->label(__('filament-signal::signal.fields.description'))
                                        ->hiddenLabel()
                                        ->placeholder('—')
                                        ->visible(fn(SignalTrigger $record) => !empty($record->description))
                                        ->columnSpanFull(),
                                    TextEntry::make('event_display_name')
                                        ->label(__('filament-signal::signal.fields.event'))
                                        ->state(function (SignalTrigger $record): string {
                                            $eventClassOptions = FilamentSignal::eventOptions();
                                            $displayName = $eventClassOptions[$record->event_class] ?? class_basename($record->event_class);

                                            if ($displayName === class_basename($record->event_class)) {
                                                try {
                                                    $allOptions = self::getEventClassOptions();
                                                    $displayName = $allOptions[$record->event_class] ?? $displayName;
                                                } catch (\Throwable $e) {
                                                    // Fallback
                                                }
                                            }

                                            return $displayName;
                                        }),
                                    TextEntry::make('event_class')
                                        ->label(__('filament-signal::signal.fields.event_class'))
                                        ->copyable()
                                        ->copyMessage(__('filament-signal::signal.fields.copied')),
                                ])
                                ->columns(2),
                        ])
                            ->columnSpan(8),
                        Group::make([
                            Section::make(__('filament-signal::signal.sections.trigger_conditions'))
                                ->schema([
                                    TextEntry::make('match_type_display')
                                        ->label(__('filament-signal::signal.fields.match_logic'))
                                        ->formatStateUsing(function (SignalTrigger $record): string {
                                            $matchType = $record->match_type ?? 'all';
                                            return $matchType === 'all'
                                                ? __('filament-signal::signal.options.match_type.all')
                                                : __('filament-signal::signal.options.match_type.any');
                                        })
                                        ->icon('heroicon-o-funnel'),
                                    TextEntry::make('total_actions')
                                        ->label(__('filament-signal::signal.fields.total_actions'))
                                        ->formatStateUsing(fn(SignalTrigger $record) => $record->actions()->count()),
                                ]),
                        ])
                            ->columnSpan(4),
                    ])
                    ->columns(12),
                Section::make(__('filament-signal::signal.sections.trigger_actions'))
                    ->schema([
                        ActionsListEntry::make('actions')
                            ->label(__('filament-signal::signal.fields.actions'))
                            ->state(fn(SignalTrigger $record) => $record->actions->toArray()),
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
                    Section::make(__('filament-signal::signal.sections.trigger_details'))
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->compact()
                        ->secondary()
                        ->schema([
                            Grid::make()
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
                                        ->helperText(__('filament-signal::signal.helpers.event_class'))
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
                    Section::make(__('filament-signal::signal.sections.trigger_conditions'))
                        ->icon('heroicon-o-funnel')
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
                                        ->label(__('filament-signal::signal.options.filter_blocks.equals'))
                                        ->schema([
                                            Forms\Components\TextInput::make('field')
                                                ->label(__('filament-signal::signal.fields.field'))
                                                ->required(),
                                            Forms\Components\TextInput::make('value')
                                                ->label(__('filament-signal::signal.fields.value'))
                                                ->required(),
                                        ])->columns(2),
                                    Block::make('contains')
                                        ->label(__('filament-signal::signal.options.filter_blocks.contains'))
                                        ->schema([
                                            Forms\Components\TextInput::make('field')
                                                ->label(__('filament-signal::signal.fields.field'))
                                                ->required(),
                                            Forms\Components\TextInput::make('value')
                                                ->label(__('filament-signal::signal.fields.value'))
                                                ->required(),
                                        ])->columns(2),
                                ])
                                ->collapsible(),
                        ]),
                ])->columnSpan(4),
                Group::make([
                    Section::make(__('filament-signal::signal.sections.trigger_actions'))
                        ->icon('heroicon-o-bolt')
                        ->compact()
                        ->schema([
                            Repeater::make('actions')
                                ->extraItemActions([
                                    Action::make('toggleActive')
                                        ->icon(function (array $arguments, Repeater $component): string {
                                            $itemData = $component->getRawItemState($arguments['item']);
                                            $isActive = $itemData['is_active'] ?? true;

                                            return $isActive ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle';
                                        })
                                        ->color(function (array $arguments, Repeater $component): string {
                                            $itemData = $component->getRawItemState($arguments['item']);
                                            $isActive = $itemData['is_active'] ?? true;

                                            return $isActive ? 'success' : 'danger';
                                        })
                                        ->tooltip(function (array $arguments, Repeater $component): string {
                                            $itemData = $component->getRawItemState($arguments['item']);
                                            $isActive = $itemData['is_active'] ?? true;

                                            return $isActive
                                                ? __('filament-signal::signal.actions.deactivate_action')
                                                : __('filament-signal::signal.actions.activate_action');
                                        })
                                        ->action(function (array $arguments, Repeater $component): void {
                                            $state = $component->getState();
                                            $itemId = $arguments['item'];

                                            if (isset($state[$itemId])) {
                                                $currentActive = $state[$itemId]['is_active'] ?? true;
                                                $state[$itemId]['is_active'] = ! $currentActive;
                                                $component->state($state);
                                            }
                                        }),
                                    Action::make('clone')
                                        ->label(__('filament-signal::signal.actions.clone_action'))
                                        ->icon('heroicon-o-document-duplicate')
                                        ->color('gray')
                                        ->tooltip(__('filament-signal::signal.actions.clone_action'))
                                        ->action(function (array $arguments, Repeater $component): void {
                                            $state = $component->getState();
                                            $itemId = $arguments['item'];

                                            if (isset($state[$itemId])) {
                                                $itemData = $state[$itemId];

                                                // Crea una copia dell'item
                                                $clonedData = $itemData;

                                                // Aggiungi "(Copy)" al nome se non è già presente
                                                $copySuffix = __('filament-signal::signal.actions.copy_suffix');
                                                if (isset($clonedData['name'])) {
                                                    $clonedData['name'] = str_ends_with($clonedData['name'], $copySuffix)
                                                        ? $clonedData['name'] . $copySuffix
                                                        : $clonedData['name'] . $copySuffix;
                                                }

                                                // Genera un nuovo ID per l'item clonato
                                                $newItemId = \Illuminate\Support\Str::uuid()->toString();

                                                // Aggiungi l'item clonato allo stato
                                                $state[$newItemId] = $clonedData;
                                                $component->state($state);
                                            }
                                        }),
                                ])
                                ->relationship('actions')
                                ->label(__('filament-signal::signal.fields.actions'))
                                ->hiddenLabel()
                                ->orderColumn('execution_order')
                                ->schema(static::actionRepeaterSchema())
                                ->columns(1)
                                ->collapsible()
                                ->collapsed()
                                ->addActionLabel(__('filament-signal::signal.actions.add_action'))
                                ->itemLabel(function (array $state): ?string {
                                    $order = $state['execution_order'] ?? 1;
                                    $type = strtoupper($state['action_type'] ?? '');
                                    $name = $state['name'] ?? '';

                                    if ($name) {
                                        return __('filament-signal::signal.actions.item_label', [
                                            'order' => $order,
                                            'type' => $type,
                                            'name' => $name,
                                        ]);
                                    }

                                    return __('filament-signal::signal.actions.item_label_no_name', [
                                        'order' => $order,
                                        'type' => $type,
                                    ]);
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
            Grid::make()
                ->columns(12)
                ->components([
                    Group::make([
                        // Section::make(__('filament-signal::signal.sections.general'))
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
                        Forms\Components\Hidden::make('is_active')
                            ->default(true),
                        // ]),
                    ])->columnSpan(4),

                    Group::make([
                        Grid::make()
                            ->columns([
                                'default' => 1,
                                '@md' => 2,
                                '@xl' => 2,
                            ])
                            ->visible(fn(Get $get): bool => $get('action_type') === 'webhook')
                            ->schema([
                                Forms\Components\TextInput::make('configuration.url')
                                    ->label(__('filament-signal::signal.fields.endpoint_url'))
                                    ->url()
                                    ->required(fn(Get $get): bool => $get('action_type') === 'webhook')
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('configuration.method')
                                    ->label(__('filament-signal::signal.fields.http_method'))
                                    ->options([
                                        'POST' => __('filament-signal::signal.options.http_method.POST'),
                                        'PUT' => __('filament-signal::signal.options.http_method.PUT'),
                                        'PATCH' => __('filament-signal::signal.options.http_method.PATCH'),
                                        'DELETE' => __('filament-signal::signal.options.http_method.DELETE'),
                                    ])
                                    ->default('POST'),
                                Forms\Components\Select::make('configuration.body')
                                    ->label(__('filament-signal::signal.fields.payload_mode'))
                                    ->options([
                                        'payload' => __('filament-signal::signal.options.payload_mode.payload'),
                                        'event' => __('filament-signal::signal.options.payload_mode.event'),
                                    ])
                                    ->default('event'),
                                Forms\Components\TextInput::make('configuration.secret')
                                    ->label(__('filament-signal::signal.fields.signing_secret'))
                                    ->password()
                                    ->revealable()
                                    ->default(fn () => config('signal.webhook.secret') ?: Str::random(40))
                                    ->helperText(__('filament-signal::signal.helpers.signing_secret'))
                                    ->columnSpan(2),
                                // Forms\Components\Toggle::make('configuration.verify_ssl')
                                //     ->label(__('filament-signal::signal.fields.verify_ssl'))
                                //     ->default(true),
                            ]),
                        Section::make(__('filament-signal::signal.sections.webhook_configuration_advanced'))
                            ->collapsible()
                            ->collapsed()
                            ->visible(false) // Temporaneamente nascosto - riattivare quando necessario
                            ->schema([
                                Grid::make()
                                    ->columns([
                                        'default' => 1,
                                        '@md' => 2,
                                        '@xl' => 3,
                                    ])
                                    ->schema([
                                        Forms\Components\TextInput::make('configuration.queue')
                                            ->label(__('filament-signal::signal.fields.queue'))
                                            ->placeholder(__('filament-signal::signal.placeholders.default')),
                                        Forms\Components\TextInput::make('configuration.connection')
                                            ->label(__('filament-signal::signal.fields.queue_connection')),
                                        Forms\Components\TextInput::make('configuration.timeout')
                                            ->label(__('filament-signal::signal.fields.timeout_seconds'))
                                            ->numeric()
                                            ->minValue(1),
                                        Forms\Components\TextInput::make('configuration.tries')
                                            ->label(__('filament-signal::signal.fields.max_attempts'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->placeholder(__('filament-signal::signal.placeholders.max_attempts_example')),
                                        Forms\Components\TextInput::make('configuration.backoff_strategy')
                                            ->label(__('filament-signal::signal.fields.backoff_strategy_class')),
                                        Forms\Components\Toggle::make('configuration.throw_exception_on_failure')
                                            ->label(__('filament-signal::signal.fields.throw_on_failure'))
                                            ->default(false),
                                        Forms\Components\Toggle::make('configuration.dispatch_sync')
                                            ->label(__('filament-signal::signal.fields.dispatch_synchronously'))
                                            ->helperText(__('filament-signal::signal.helpers.dispatch_sync')),
                                        Forms\Components\TagsInput::make('configuration.tags')
                                            ->label(__('filament-signal::signal.fields.horizon_tags'))
                                            ->placeholder(__('filament-signal::signal.placeholders.tag'))
                                            ->columnSpan([
                                                'default' => 1,
                                                '@md' => 2,
                                                '@xl' => 1,
                                            ]),
                                        Forms\Components\KeyValue::make('configuration.headers')
                                            ->label(__('filament-signal::signal.fields.headers'))
                                            ->keyLabel(__('filament-signal::signal.fields.header'))
                                            ->valueLabel(__('filament-signal::signal.fields.value'))
                                            ->addActionLabel(__('filament-signal::signal.fields.add_header'))
                                            ->columnSpan([
                                                'default' => 1,
                                                '@md' => 2,
                                                '@xl' => 2,
                                            ]),
                                        Forms\Components\KeyValue::make('configuration.meta')
                                            ->label(__('filament-signal::signal.fields.meta'))
                                            ->keyLabel(__('filament-signal::signal.fields.key'))
                                            ->valueLabel(__('filament-signal::signal.fields.value'))
                                            ->addActionLabel(__('filament-signal::signal.fields.add_meta'))
                                            ->columnSpan([
                                                'default' => 1,
                                                '@md' => 2,
                                                '@xl' => 3,
                                            ]),
                                        Forms\Components\TextInput::make('configuration.proxy')
                                            ->label(__('filament-signal::signal.fields.proxy'))
                                            ->placeholder(__('filament-signal::signal.placeholders.proxy'))
                                            ->columnSpan([
                                                'default' => 1,
                                                '@md' => 2,
                                                '@xl' => 3,
                                            ]),
                                        Forms\Components\TextInput::make('configuration.job')
                                            ->label(__('filament-signal::signal.fields.custom_job_class')),
                                        Forms\Components\TextInput::make('configuration.signer')
                                            ->label(__('filament-signal::signal.fields.custom_signer_class')),
                                    ]),
                            ]),
                        Section::make()
                            ->heading('')
                            ->label(__('filament-signal::signal.fields.log_info'))
                            ->schema([
                                Text::make('log_info')
                                    ->content(__('filament-signal::signal.helpers.log_info')),
                            ])
                            ->visible(fn(Get $get): bool => $get('action_type') === 'log')
                            ->columnSpanFull(),
                    ])->columnSpan(8),
                    Section::make(__('filament-signal::signal.sections.payload_configuration'))
                        ->description(__('filament-signal::signal.helpers.payload_configuration'))
                        ->icon('heroicon-o-circle-stack')
                        ->compact()
                        ->columnSpanFull()
                        ->schema(function (Get $get): array {
                            $components = [
                                Forms\Components\CheckboxList::make('configuration.payload_config.include_fields')
                                    ->label(__('filament-signal::signal.fields.essential_fields'))
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
                                    // ->helperText(__('filament-signal::signal.helpers.essential_fields'))
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

                                if (! empty($analysis['relations'])) {
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
                                            ->label(__('filament-signal::signal.fields.relation_prefix') . ': ' . $relation['label'])
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
                        ->visible(fn (Get $get): bool => filled($get('../../event_class'))),

                    // Group::make([
                    //     Section::make()->heading('RIGHT 1')->schema([]),

                    //     Group::make([
                    //         Section::make()->heading('RIGHT 2')->schema([]),

                    //         Section::make()->heading('RIGHT 3')->schema([]),
                    //     ])->columns(2),
                    // ])
                    //     ->columnSpan(8),

                    // ->components([
                    //     Group::make([
                    //         Section::make(__('filament-signal::signal.sections.general'))
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

                    //     Section::make(__('filament-signal::signal.sections.email_configuration'))
                    //         ->secondary()
                    //         ->visible(fn(Get $get): bool => $get('action_type') === 'email')
                    //         ->columnSpan(1)
                    //         ->schema([
                    //             Grid::make()
                    //                 ->columns([
                    //                     'default' => 1,
                    //                     '@md' => 2,
                    //                 ])
                    //                 ->schema([
                    //                     Forms\Components\TextInput::make('configuration.subject_override')
                    //                         ->label(__('filament-signal::signal.fields.subject'))
                    //                         ->placeholder('Leave empty to use template subject')
                    //                         ->columnSpanFull(),
                    //                     Grid::make()
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
            ->mapWithKeys(function (string $type) {
                $translationKey = "filament-signal::signal.action_types.{$type}";
                $translated = __($translationKey);

                // Se la traduzione non esiste (restituisce la chiave stessa), usa ucfirst come fallback
                $label = ($translated !== $translationKey) ? $translated : ucfirst($type);

                return [$type => $label];
            })
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
                        $copySuffix = __('filament-signal::signal.actions.copy_suffix');
                        $cloned->name = str_ends_with($record->name, $copySuffix)
                            ? $record->name . $copySuffix
                            : $record->name . $copySuffix;

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
                            $copySuffix = __('filament-signal::signal.actions.copy_suffix');
                            $clonedAction->name = str_ends_with($action->name, $copySuffix)
                                ? $action->name . $copySuffix
                                : $action->name . $copySuffix;

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
