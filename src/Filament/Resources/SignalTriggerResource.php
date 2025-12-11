<?php

namespace Base33\FilamentSignal\Filament\Resources;

use BackedEnum;
use Base33\FilamentSignal\Filament\Infolists\Components\ActionsListEntry;
use Base33\FilamentSignal\Filament\Infolists\Components\FiltersListEntry;
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
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
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
            ->columns(12)
            ->components([

                Group::make([
                    Section::make()
                        ->icon('heroicon-o-bolt')
                        ->compact()
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
                                ->visible(fn(SignalTrigger $record) => ! empty($record->description))
                                ->columnSpanFull(),
                            TextEntry::make('event_display_name')
                                ->label(__('filament-signal::signal.fields.event_source'))
                                ->icon('heroicon-o-sparkles')
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
                                ->icon('heroicon-o-code-bracket')
                                ->copyable()
                                ->copyMessage(__('filament-signal::signal.fields.copied')),
                        ])
                        ->columns(2),
                    Section::make(__('filament-signal::signal.fields.execution_pipeline'))
                        ->icon('heroicon-o-bolt')
                        ->schema([
                            ActionsListEntry::make('actions')
                                ->label(__('filament-signal::signal.fields.actions'))
                                ->hiddenLabel()
                                ->state(fn(SignalTrigger $record) => $record->actions->toArray()),
                        ]),
                ])
                    ->columnSpan(8),
                Group::make([
                    Section::make(__('filament-signal::signal.fields.filter_logic'))
                        ->icon('heroicon-o-funnel')
                        ->compact()
                        ->schema([
                            FiltersListEntry::make('filters')
                                ->label(__('filament-signal::signal.fields.filters'))
                                ->hiddenLabel()
                                ->state(function (SignalTrigger $record): array {
                                    $filters = $record->filters ?? [];

                                    // Se è null, prova a leggerlo direttamente dal database
                                    if ($filters === null || (is_array($filters) && empty($filters))) {
                                        try {
                                            $rawFilters = $record->getRawOriginal('filters');
                                            if ($rawFilters !== null && $rawFilters !== '') {
                                                $decoded = json_decode($rawFilters, true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                    $filters = $decoded;
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            // Ignora errori
                                        }
                                    }

                                    return $filters;
                                }),

                        ]),
                ])
                    ->columnSpan(4),
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
                        ->compact()
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
                                ->blocks(static::getFilterBlocks())

                                ->collapsible(),
                        ]),
                ])->columnSpan(4),
                Group::make([
                    Section::make(__('filament-signal::signal.sections.trigger_actions'))
                        ->icon('heroicon-o-bolt')
                        ->compact()
                        ->schema([
                            Repeater::make('actions')
                                ->cloneable()
                                ->cloneAction(
                                    fn($action) => $action
                                        ->after(function (array $arguments, Repeater $component): void {
                                            // Dopo che Filament ha clonato l'item, personalizzalo
                                            $state = $component->getState();
                                            $itemId = $arguments['item'];

                                            // Trova l'item appena clonato (quello senza ID o con ID null)
                                            foreach ($state as $id => $data) {
                                                if ($id !== $itemId && (!isset($data['id']) || $data['id'] === null)) {
                                                    $copySuffix = __('filament-signal::signal.actions.copy_suffix');

                                                    // Aggiungi "(Copy)" al nome se non è già presente
                                                    if (isset($data['name']) && !str_ends_with($data['name'], $copySuffix)) {
                                                        $state[$id]['name'] = $data['name'] . $copySuffix;
                                                    }

                                                    // Incrementa l'execution_order se presente
                                                    if (isset($data['execution_order'])) {
                                                        $state[$id]['execution_order'] = ($data['execution_order'] ?? 1) + 1;
                                                    }

                                                    break;
                                                }
                                            }

                                            $component->state($state);
                                        })
                                )
                                ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                    // Normalizza configuration da JSON string a array se necessario
                                    if (isset($data['configuration']) && is_string($data['configuration'])) {
                                        $data['configuration'] = json_decode($data['configuration'], true) ?? [];
                                    }
                                    if (!isset($data['configuration']) || !is_array($data['configuration'])) {
                                        $data['configuration'] = [];
                                    }
                                    return $data;
                                })
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
    public static function getWebhookTemplateOptions(): array
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
    public static function getEventClassOptions(): array
    {
        $options = [
             'TBD' => 'To Be Defined (Draft)',
        ];

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

    public static function applyWebhookTemplate(?string $templateId, callable $set): void
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

    public static function actionRepeaterSchema(): array
    {
        return [
            Grid::make()
                ->columns(12)
                ->components([
                    Group::make(static::getBaseActionFields())->columnSpan(4),
                    Group::make()
                        ->schema(function (Get $get): array {
                            $actionType = $get('action_type');
                            return static::getActionTypeSchema($actionType);
                        })
                        ->columnSpan(8),
                    ...static::getPayloadConfigurationSchema(),
                ]),
        ];
    }

    /**
     * Campi base comuni a tutte le azioni
     */
    public static function getBaseActionFields(): array
    {
        return [
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
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    static::resetActionConfiguration($state, $set);
                }),
            Forms\Components\Hidden::make('is_active')
                ->default(true),
        ];
    }

    /**
     * Ottiene lo schema per un tipo di azione specifico (supporta plugin esterni)
     */
    public static function getActionTypeSchema(?string $actionType): array
    {
        if (!$actionType) {
            return [];
        }

        // Prima controlla se c'è uno schema custom registrato da plugin
        $customSchema = static::getCustomActionSchema($actionType);
        if ($customSchema) {
            return $customSchema;
        }

        // Fallback agli schemi built-in
        return match ($actionType) {
            'webhook' => static::getWebhookActionSchema(),
            'log' => static::getLogActionSchema(),
            'email' => static::getEmailActionSchema(),
            default => [],
        };
    }

    /**
     * Permette a plugin esterni di registrare schemi custom tramite config
     */
    public static function getCustomActionSchema(string $actionType): ?array
    {
        $customSchemas = config('signal.action_schemas', []);
        $schema = $customSchemas[$actionType] ?? null;

        if (is_callable($schema)) {
            return $schema();
        }

        return is_array($schema) ? $schema : null;
    }

    /**
     * Schema per azioni di tipo webhook
     */
    public static function getWebhookActionSchema(): array
    {
        return [
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
                        ->default('event')
                        ->required(),
                    Forms\Components\TextInput::make('configuration.secret')
                        ->label(__('filament-signal::signal.fields.signing_secret'))
                        ->password()
                        ->revealable()
                        ->default(fn() => config('signal.webhook.secret') ?: Str::random(40))
                        ->helperText(__('filament-signal::signal.helpers.signing_secret'))
                        ->columnSpan(2),
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
        ];
    }

    /**
     * Schema per azioni di tipo log
     */
    public static function getLogActionSchema(): array
    {
        return [
            Grid::make()
                ->columns([
                    'default' => 1,
                    '@md' => 2,
                    '@xl' => 2,
                ])
                ->visible(fn(Get $get): bool => $get('action_type') === 'log')
                ->schema([
                    Forms\Components\Select::make('configuration.body')
                        ->label(__('filament-signal::signal.fields.payload_mode'))
                        ->options([
                            'payload' => __('filament-signal::signal.options.payload_mode.payload'),
                            'event' => __('filament-signal::signal.options.payload_mode.event'),
                        ])
                        ->default('payload')
                        ->required()

                ]),
            Section::make()
                ->heading('')
                ->compact()

                ->schema([
                    Text::make('log_info')
                        ->content(__('filament-signal::signal.helpers.log_info')),
                ])
                ->visible(fn(Get $get): bool => $get('action_type') === 'log')
                ->columnSpanFull(),
        ];
    }

    /**
     * Schema per azioni di tipo email
     */
    protected static function getEmailActionSchema(): array
    {
        return [];
    }

    /**
     * Reset della configurazione quando cambia il tipo di azione
     */
    public static function resetActionConfiguration(string $newType, callable $set): void
    {
        // Pulisci la configurazione quando si cambia tipo
        // Mantieni solo i campi comuni (name, execution_order, is_active)
        $set('configuration', []);

        // Imposta i default per il nuovo tipo (supporta plugin esterni)
        static::setActionTypeDefaults($newType, $set);
    }

    /**
     * Imposta i default per un tipo di azione (supporta plugin esterni)
     */
    public static function setActionTypeDefaults(string $actionType, callable $set): void
    {
        // Prima controlla se ci sono default custom registrati da plugin
        $customDefaults = config('signal.action_defaults', []);
        if (isset($customDefaults[$actionType]) && is_callable($customDefaults[$actionType])) {
            $customDefaults[$actionType]($set);
            return;
        }

        // Fallback ai default built-in
        match ($actionType) {
            'webhook' => static::setWebhookDefaults($set),
            'log' => static::setLogDefaults($set),
            'email' => static::setEmailDefaults($set),
            default => null,
        };
    }

    /**
     * Default per azioni webhook
     */
    public static function setWebhookDefaults(callable $set): void
    {
        $set('configuration.method', 'POST');
        $set('configuration.body', 'event');
        if (!config('signal.webhook.secret')) {
            $set('configuration.secret', Str::random(40));
        }
    }

    /**
     * Default per azioni log
     */
    public static function setLogDefaults(callable $set): void
    {
        $set('configuration.body', 'payload');
    }

    /**
     * Default per azioni email
     */
    public static function setEmailDefaults(callable $set): void
    {
        // Nessun default specifico per email al momento
    }

    /**
     * Schema per Payload Configuration (isolato per ogni azione)
     */
    public static function getPayloadConfigurationSchema(): array
    {
        return [
            Section::make(__('filament-signal::signal.sections.payload_configuration'))
                ->description(__('filament-signal::signal.helpers.payload_configuration'))
                ->compact()
                ->icon('heroicon-o-circle-stack')
                ->columnSpanFull()
                ->schema(function (Get $get): array {
                    // Helper to find event_class with various nesting levels
                    $findEventClass = function (Get $get) {
                        $paths = [
                            '../../../../event_class', // Nested in Repeater/Forms
                            '../../../event_class',    // Deeply nested
                            '../../event_class',       // Standard nesting
                            '../event_class',          // Shallow nesting
                            'event_class',             // Root level
                        ];
                        foreach ($paths as $path) {
                            try {
                                if ($val = $get($path)) return $val;
                            } catch (\Throwable $e) {}
                        }
                        return null;
                    };

                    $components = [
                        Forms\Components\CheckboxList::make('configuration.payload_config.include_fields')
                            ->label(__('filament-signal::signal.fields.essential_fields'))
                            ->options(function (Get $get) use ($findEventClass): array {
                                $eventClass = $findEventClass($get);
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
                            ->columnSpanFull()
                            ->live(onBlur: false)
                            ->dehydrated()
                            ->rules([])
                            ->afterStateHydrated(function ($state, callable $set, Get $get) use ($findEventClass) {
                                // Pulisci i valori non validi quando viene caricato lo stato
                                $eventClass = $findEventClass($get);
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
                            ->afterStateUpdated(function ($state, callable $set, Get $get) use ($findEventClass) {
                                // Pulisci i valori che non sono più nelle opzioni disponibili quando cambia l'evento
                                $eventClass = $findEventClass($get);
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
                    $eventClass = $findEventClass($get);
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

                                    // Se fieldKey inizia già con alias seguito da punto, non concatenarlo di nuovo
                                    // (es: "unit.type.name" non diventa "unit.unit.type.name")
                                    // Le chiavi possono essere:
                                    // - "type.name" (senza alias) -> diventa "unit.type.name"
                                    // - "unit.type.name" (con alias) -> rimane "unit.type.name"
                                    $aliasPrefix = "{$alias}.";
                                    if (str_starts_with($fieldKey, $aliasPrefix)) {
                                        $options[$fieldKey] = $formattedLabel;
                                    } else {
                                        $options["{$alias}.{$fieldKey}"] = $formattedLabel;
                                    }
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
                }),
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
                Tables\Columns\TextColumn::make('status')
                    ->label(__('filament-signal::signal.fields.status'))
                    ->badge()
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
                Action::make('flow')
                    ->label(__('filament-signal::signal.actions.flow_view'))
                    ->icon('heroicon-o-squares-2x2')
                    ->color('info')
                    ->url(fn(SignalTrigger $record): string => static::getUrl('flow', ['record' => $record]))
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

    /**
     * @return array<Block>
     */
    public static function getFilterBlocks(): array
    {
        return [
            Block::make('equals')
                ->label(static::getFilterBlockLabel('equals', __('filament-signal::signal.options.filter_blocks.equals')))
                ->schema([
                    Forms\Components\Select::make('field')
                        ->label(__('filament-signal::signal.fields.field'))
                        ->options(fn(Get $get): array => static::getFilterFieldOptions($get))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('value')
                        ->label(__('filament-signal::signal.fields.value'))
                        ->required()
                        ->live(),
                ])->columns(1),
            Block::make('not_equals')
                ->label(static::getFilterBlockLabel('not_equals', __('filament-signal::signal.options.filter_blocks.not_equals')))
                ->schema([
                    Forms\Components\Select::make('field')
                        ->label(__('filament-signal::signal.fields.field'))
                        ->options(fn(Get $get): array => static::getFilterFieldOptions($get))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('value')
                        ->label(__('filament-signal::signal.fields.value'))
                        ->required()
                        ->live(),
                ])->columns(1),
            Block::make('contains')
                ->label(static::getFilterBlockLabel('contains', __('filament-signal::signal.options.filter_blocks.contains')))
                ->schema([
                    Forms\Components\Select::make('field')
                        ->label(__('filament-signal::signal.fields.field'))
                        ->options(fn(Get $get): array => static::getFilterFieldOptions($get))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('value')
                        ->label(__('filament-signal::signal.fields.value'))
                        ->required()
                        ->live(),
                ])->columns(1),
            Block::make('not_contains')
                ->label(static::getFilterBlockLabel('not_contains', __('filament-signal::signal.options.filter_blocks.not_contains')))
                ->schema([
                    Forms\Components\Select::make('field')
                        ->label(__('filament-signal::signal.fields.field'))
                        ->options(fn(Get $get): array => static::getFilterFieldOptions($get))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('value')
                        ->label(__('filament-signal::signal.fields.value'))
                        ->required()
                        ->live(),
                ])->columns(1),
            Block::make('greater_than')
                ->label(static::getFilterBlockLabel('greater_than', __('filament-signal::signal.options.filter_blocks.greater_than')))
                ->schema([
                    Forms\Components\Select::make('field')
                        ->label(__('filament-signal::signal.fields.field'))
                        ->options(fn(Get $get): array => static::getFilterFieldOptions($get))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('value')
                        ->label(__('filament-signal::signal.fields.value'))
                        ->required()
                        ->live(),
                ])->columns(1),
            Block::make('greater_than_or_equal')
                ->label(static::getFilterBlockLabel('greater_than_or_equal', __('filament-signal::signal.options.filter_blocks.greater_than_or_equal')))
                ->schema([
                    Forms\Components\Select::make('field')
                        ->label(__('filament-signal::signal.fields.field'))
                        ->options(fn(Get $get): array => static::getFilterFieldOptions($get))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('value')
                        ->label(__('filament-signal::signal.fields.value'))
                        ->required()
                        ->live(),
                ])->columns(1),
            Block::make('less_than')
                ->label(static::getFilterBlockLabel('less_than', __('filament-signal::signal.options.filter_blocks.less_than')))
                ->schema([
                    Forms\Components\Select::make('field')
                        ->label(__('filament-signal::signal.fields.field'))
                        ->options(fn(Get $get): array => static::getFilterFieldOptions($get))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('value')
                        ->label(__('filament-signal::signal.fields.value'))
                        ->required()
                        ->live(),
                ])->columns(1),
            Block::make('less_than_or_equal')
                ->label(static::getFilterBlockLabel('less_than_or_equal', __('filament-signal::signal.options.filter_blocks.less_than_or_equal')))
                ->schema([
                    Forms\Components\Select::make('field')
                        ->label(__('filament-signal::signal.fields.field'))
                        ->options(fn(Get $get): array => static::getFilterFieldOptions($get))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\TextInput::make('value')
                        ->label(__('filament-signal::signal.fields.value'))
                        ->required()
                        ->live(),
                ])->columns(1),
            Block::make('in')
                ->label(static::getFilterBlockLabel('in', __('filament-signal::signal.options.filter_blocks.in')))
                ->schema([
                    Forms\Components\Select::make('field')
                        ->label(__('filament-signal::signal.fields.field'))
                        ->options(fn(Get $get): array => static::getFilterFieldOptions($get))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\Textarea::make('value')
                        ->label(__('filament-signal::signal.fields.value'))
                        ->helperText(__('filament-signal::signal.helpers.filter_in_value'))
                        ->required()
                        ->live(),
                ])->columns(1),
            Block::make('not_in')
                ->label(static::getFilterBlockLabel('not_in', __('filament-signal::signal.options.filter_blocks.not_in')))
                ->schema([
                    Forms\Components\Select::make('field')
                        ->label(__('filament-signal::signal.fields.field'))
                        ->options(fn(Get $get): array => static::getFilterFieldOptions($get))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\Textarea::make('value')
                        ->label(__('filament-signal::signal.fields.value'))
                        ->helperText(__('filament-signal::signal.helpers.filter_in_value'))
                        ->required()
                        ->live(),
                ])->columns(1),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSignalTriggers::route('/'),
            'create' => Pages\CreateSignalTrigger::route('/create'),
            'view' => Pages\ViewSignalTrigger::route('/{record}'),
            'edit' => Pages\EditSignalTrigger::route('/{record}/edit'),
            'flow' => Pages\FlowSignalTrigger::route('/{record}/flow'),
        ];
    }

    /**
     * Genera un label dinamico per i blocchi dei filtri che mostra un riassunto del contenuto
     *
     * @param  string  $type  Tipo di filtro (equals, contains, etc.)
     * @param  string  $typeLabel  Label tradotto del tipo di filtro
     */
    public static function getFilterBlockLabel(string $type, string $typeLabel): \Closure
    {
        return function (?array $state) use ($typeLabel): string {
            if ($state === null) {
                return $typeLabel;
            }

            // Nei Builder blocks, i dati possono essere in $state['data'] o direttamente in $state
            // Prova entrambi i percorsi
            $data = $state['data'] ?? $state;
            $field = $data['field'] ?? '';
            $value = $data['value'] ?? '';

            if (blank($field) && blank($value)) {
                return $typeLabel;
            }

            // Ottieni il label formattato del campo dalle opzioni disponibili
            // Usa sempre il label formattato invece del nome tecnico
            $fieldLabel = $field;

            try {
                // Per ottenere le opzioni dei campi, dobbiamo accedere all'event_class dal form principale
                // Prova prima dal request (modo create/edit)
                $eventClass = null;
                $requestData = request()->input('data');
                if (is_array($requestData) && isset($requestData['event_class'])) {
                    $eventClass = $requestData['event_class'];
                }

                // Se non trovato, prova a ottenere dal record corrente (modo edit)
                if (! $eventClass) {
                    try {
                        $record = request()->route('record');
                        if ($record && method_exists($record, 'getAttribute')) {
                            $eventClass = $record->getAttribute('event_class');
                        }
                    } catch (\Throwable $e) {
                        // Ignora errori
                    }
                }

                if ($eventClass) {
                    // Ottieni direttamente le opzioni dei campi usando una funzione helper
                    $fieldOptions = static::getFilterFieldOptionsForEvent($eventClass);
                    if (isset($fieldOptions[$field])) {
                        $fieldLabel = $fieldOptions[$field];
                    } else {
                        // Fallback: formatta il campo manualmente se non trovato nelle opzioni
                        // Questo può succedere se il campo è stato salvato con un nome diverso
                        $parts = explode('.', $field);
                        if (count($parts) >= 2) {
                            $formattedParts = [];
                            foreach ($parts as $part) {
                                $trimmed = trim($part);
                                // Rimuovi underscore e capitalizza
                                $formatted = str_replace('_', ' ', $trimmed);
                                $formattedParts[] = ucwords($formatted);
                            }
                            $fieldLabel = implode(' → ', $formattedParts);
                        }
                    }
                } else {
                    // Fallback: formatta il campo manualmente se event_class non disponibile
                    $parts = explode('.', $field);
                    if (count($parts) >= 2) {
                        $formattedParts = [];
                        foreach ($parts as $part) {
                            $trimmed = trim($part);
                            $formatted = str_replace('_', ' ', $trimmed);
                            $formattedParts[] = ucwords($formatted);
                        }
                        $fieldLabel = implode(' → ', $formattedParts);
                    }
                }
            } catch (\Throwable $e) {
                // Fallback: formatta il campo manualmente in caso di errore
                $parts = explode('.', $field);
                if (count($parts) >= 2) {
                    $formattedParts = [];
                    foreach ($parts as $part) {
                        $trimmed = trim($part);
                        $formatted = str_replace('_', ' ', $trimmed);
                        $formattedParts[] = ucwords($formatted);
                    }
                    $fieldLabel = implode(' → ', $formattedParts);
                }
            }

            // Tronca il valore se troppo lungo
            $displayValue = (string) $value;
            if (strlen($displayValue) > 30) {
                $displayValue = substr($displayValue, 0, 27) . '...';
            }

            if (blank($field)) {
                return $typeLabel . ($value ? ': ' . $displayValue : '');
            }

            if (blank($value)) {
                return $fieldLabel . ' ' . strtolower($typeLabel);
            }

            // Usa la traduzione del tipo di filtro (già passata come $typeLabel)
            $typeDisplay = strtolower($typeLabel);

            return $fieldLabel . ' ' . $typeDisplay . ' ' . $displayValue;
        };
    }

    /**
     * Ottiene le opzioni dei campi disponibili per un event_class specifico.
     * Include sia i campi del modello principale che le relazioni.
     *
     * @return array<string, string> Array associativo [campo => etichetta]
     */
    public static function getFilterFieldOptionsForEvent(string $eventClass): array
    {
        try {
            $analyzer = app(SignalPayloadFieldAnalyzer::class);
            $analysis = $analyzer->analyzeEvent($eventClass);

            // Ottieni il modello per ottenere i nomi reali delle colonne
            $modelClass = null;
            $realColumnNames = [];
            if (preg_match('/eloquent\.[a-z_]+:\s*(.+)$/i', $eventClass, $matches)) {
                $modelClass = trim($matches[1]);
                if (class_exists($modelClass) && is_subclass_of($modelClass, Model::class)) {
                    try {
                        $model = new $modelClass;
                        $table = $model->getTable();
                        if ($table && DatabaseSchema::hasTable($table)) {
                            $realColumnNames = DatabaseSchema::getColumnListing($table);
                        }
                    } catch (\Throwable $e) {
                        // Ignora errori
                    }
                }
            }

            $options = [];

            // Mostra solo i campi essenziali del modello principale (stessa logica della payload configuration)
            foreach ($analysis['fields'] as $field => $data) {
                if (substr_count($field, '.') > 1) {
                    continue;
                }

                $parts = explode('.', $field);
                if (count($parts) === 2) {
                    $fieldName = $parts[1];
                    if (in_array($fieldName, ['created_at', 'updated_at', 'attachments'])) {
                        continue;
                    }
                }

                // Ottieni il nome reale del campo dal database se disponibile
                $realFieldName = $field;
                if (! empty($realColumnNames) && count($parts) === 2) {
                    $theoreticalFieldName = $parts[1];
                    foreach ($realColumnNames as $realColumn) {
                        if (str_ends_with($theoreticalFieldName, '_id')) {
                            if (str_ends_with($realColumn, '_id') && str_contains($realColumn, str_replace('_id', '', $theoreticalFieldName))) {
                                $realFieldName = $parts[0] . '.' . $realColumn;

                                break;
                            }
                        } elseif ($realColumn === $theoreticalFieldName) {
                            $realFieldName = $field;

                            break;
                        }
                    }
                }

                $label = $data['label'] ?? $field;
                if (str_contains($label, '.')) {
                    $parts = explode('.', $label);
                    $formattedParts = [];
                    foreach ($parts as $part) {
                        $trimmed = trim($part);
                        $formattedParts[] = ucfirst(strtolower($trimmed));
                    }
                    $label = implode(' → ', $formattedParts);
                } elseif (str_contains($label, ' - ')) {
                    $label = str_replace(' - ', ' → ', $label);
                }

                $options[$realFieldName] = $label;
            }

            // Aggiungi anche i campi delle relazioni
            if (! empty($analysis['relations'])) {
                foreach ($analysis['relations'] as $relation) {
                    $fieldOptions = $relation['field_options'] ?? [];
                    if (empty($fieldOptions)) {
                        continue;
                    }

                    $alias = $relation['alias'] ?? 'relation';
                    $parentProperty = $relation['parent_property'] ?? null;
                    $relationName = $relation['relation_name'] ?? $alias;

                    // Ottieni il nome reale della foreign key se disponibile
                    $realForeignKeyName = null;
                    if ($modelClass && class_exists($modelClass) && is_subclass_of($modelClass, Model::class)) {
                        try {
                            $model = new $modelClass;
                            if (method_exists($model, $relationName)) {
                                $relationInstance = $model->{$relationName}();
                                if (method_exists($relationInstance, 'getForeignKeyName')) {
                                    $realForeignKeyName = $relationInstance->getForeignKeyName();
                                } elseif (method_exists($relationInstance, 'getForeignKey')) {
                                    $key = $relationInstance->getForeignKey();
                                    if (is_array($key)) {
                                        $realForeignKeyName = $key[0] ?? null;
                                    } else {
                                        $realForeignKeyName = $key;
                                    }
                                }
                            }
                        } catch (\Throwable $e) {
                            // Ignora errori
                        }
                    }

                    foreach ($fieldOptions as $fieldKey => $fieldLabel) {
                        $formattedLabel = $fieldLabel;
                        if (str_contains($fieldLabel, '.')) {
                            $parts = explode('.', $fieldLabel);
                            $formattedParts = [];
                            foreach ($parts as $part) {
                                $trimmed = trim($part);
                                $formattedParts[] = ucfirst(strtolower($trimmed));
                            }
                            $formattedLabel = implode(' → ', $formattedParts);
                        } elseif (str_contains($fieldLabel, ' - ')) {
                            $formattedLabel = str_replace(' - ', ' → ', $fieldLabel);
                        }

                        if ($parentProperty) {
                            $fieldPath = "{$parentProperty}.{$relationName}.{$fieldKey}";
                        } else {
                            $fieldPath = "{$alias}.{$fieldKey}";
                        }

                        $relationLabel = $relation['label'] ?? $relationName;
                        $options[$fieldPath] = "{$relationLabel} → {$formattedLabel}";
                    }

                    // Aggiungi anche il campo ID della relazione se disponibile
                    if ($realForeignKeyName && $parentProperty) {
                        $idFieldPath = "{$parentProperty}.{$realForeignKeyName}";
                        $relationLabel = $relation['label'] ?? $relationName;
                        if (! isset($options[$idFieldPath])) {
                            $options[$idFieldPath] = "{$relationLabel} → ID";
                        }
                    } elseif (isset($relation['id_field']) && ! isset($options[$relation['id_field']])) {
                        $relationLabel = $relation['label'] ?? $alias;
                        $options[$relation['id_field']] = "{$relationLabel} → ID";
                    }
                }
            }

            asort($options);

            return $options;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Ottiene le opzioni dei campi disponibili per i filtri basati sull'event_class selezionato.
     * Include sia i campi del modello principale che le relazioni.
     *
     * @return array<string, string> Array associativo [campo => etichetta]
     */
    public static function getFilterFieldOptions(Get $get): array
    {
        // Per i Builder blocks, il path per accedere ai campi del form principale è diverso
        // Il Builder block è annidato dentro il form, quindi dobbiamo risalire di più livelli
        // Struttura: form -> filters (Builder) -> block -> field
        // Quindi per risalire al form principale: ../../../../event_class

        $eventClass = null;

        // Prova diversi path in ordine di probabilità
        $paths = [
            '../../../../event_class',  // Builder block: form -> filters -> block -> field -> event_class
            '../../../event_class',     // Alternativa
            '../../event_class',        // Alternativa
            'event_class',              // Path diretto (improbabile)
        ];

        foreach ($paths as $path) {
            try {
                $value = $get($path);
                if ($value && is_string($value) && ! empty($value)) {
                    $eventClass = $value;

                    break;
                }
            } catch (\Throwable $e) {
                // Continua con il prossimo path
                continue;
            }
        }

        // Se non trovato, prova a ottenere dal record corrente (modo edit)
        if (! $eventClass) {
            try {
                $record = $get('record');
                if ($record) {
                    if (method_exists($record, 'getAttribute')) {
                        $eventClass = $record->getAttribute('event_class');
                    } elseif (isset($record->event_class)) {
                        $eventClass = $record->event_class;
                    } elseif (is_object($record) && property_exists($record, 'event_class')) {
                        $eventClass = $record->event_class;
                    }
                }
            } catch (\Throwable $e) {
                // Ignora errori
            }
        }

        // Se ancora non trovato, prova a ottenere dalla richiesta (modo create)
        if (! $eventClass) {
            try {
                // Prova dal form state della richiesta
                $requestData = request()->input('data');
                if (is_array($requestData) && isset($requestData['event_class'])) {
                    $eventClass = $requestData['event_class'];
                }
            } catch (\Throwable $e) {
                // Ignora errori
            }
        }

        if (! $eventClass) {
            return [];
        }

        // Usa la funzione helper per ottenere le opzioni
        return static::getFilterFieldOptionsForEvent($eventClass);
    }

    /**
     * Helper method che replica la logica di getFilterFieldOptions ma senza Get
     * Usato internamente da getFilterFieldOptions
     */
    protected static function getFilterFieldOptionsInternal(string $eventClass): array
    {
        try {
            $analyzer = app(SignalPayloadFieldAnalyzer::class);
            $analysis = $analyzer->analyzeEvent($eventClass);

            // Ottieni il modello per ottenere i nomi reali delle colonne
            $modelClass = null;
            $realColumnNames = [];
            if (preg_match('/eloquent\.[a-z_]+:\s*(.+)$/i', $eventClass, $matches)) {
                $modelClass = trim($matches[1]);
                if (class_exists($modelClass) && is_subclass_of($modelClass, Model::class)) {
                    try {
                        $model = new $modelClass;
                        $table = $model->getTable();
                        if ($table && DatabaseSchema::hasTable($table)) {
                            $realColumnNames = DatabaseSchema::getColumnListing($table);
                        }
                    } catch (\Throwable $e) {
                        // Ignora errori
                    }
                }
            }

            $options = [];

            // Mostra solo i campi essenziali del modello principale (stessa logica della payload configuration)
            // Escludi i campi delle relazioni annidate e i campi tecnici
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

                // Ottieni il nome reale del campo dal database se disponibile
                $realFieldName = $field;
                if (! empty($realColumnNames) && count($parts) === 2) {
                    $theoreticalFieldName = $parts[1];
                    // Cerca il campo reale nel database (potrebbe essere diverso, es: author_id vs blog_author_id)
                    foreach ($realColumnNames as $realColumn) {
                        // Se il campo teorico termina con _id, cerca tutte le colonne che terminano con lo stesso suffisso
                        if (str_ends_with($theoreticalFieldName, '_id')) {
                            if (str_ends_with($realColumn, '_id') && str_contains($realColumn, str_replace('_id', '', $theoreticalFieldName))) {
                                $realFieldName = $parts[0] . '.' . $realColumn;

                                break;
                            }
                        } elseif ($realColumn === $theoreticalFieldName) {
                            // Match esatto
                            $realFieldName = $field; // Già corretto

                            break;
                        }
                    }
                }

                $label = $data['label'] ?? $field;

                // Formatta l'etichetta per renderla più leggibile (come nella payload configuration)
                if (str_contains($label, '.')) {
                    $parts = explode('.', $label);
                    $formattedParts = [];
                    foreach ($parts as $part) {
                        $trimmed = trim($part);
                        $formattedParts[] = ucfirst(strtolower($trimmed));
                    }
                    $label = implode(' → ', $formattedParts);
                } elseif (str_contains($label, ' - ')) {
                    $label = str_replace(' - ', ' → ', $label);
                }

                // Usa il nome reale del campo dal database
                $options[$realFieldName] = $label;
            }

            // Aggiungi anche i campi delle relazioni (come nella payload configuration)
            if (! empty($analysis['relations'])) {
                foreach ($analysis['relations'] as $relation) {
                    $fieldOptions = $relation['field_options'] ?? [];
                    if (empty($fieldOptions)) {
                        continue;
                    }

                    $alias = $relation['alias'] ?? 'relation';
                    $parentProperty = $relation['parent_property'] ?? null;
                    $relationName = $relation['relation_name'] ?? $alias;

                    // Ottieni il nome reale della foreign key se disponibile
                    $realForeignKeyName = null;
                    if ($modelClass && class_exists($modelClass) && is_subclass_of($modelClass, Model::class)) {
                        try {
                            $model = new $modelClass;
                            if (method_exists($model, $relationName)) {
                                $relationInstance = $model->{$relationName}();
                                if (method_exists($relationInstance, 'getForeignKeyName')) {
                                    $realForeignKeyName = $relationInstance->getForeignKeyName();
                                } elseif (method_exists($relationInstance, 'getForeignKey')) {
                                    $key = $relationInstance->getForeignKey();
                                    if (is_array($key)) {
                                        $realForeignKeyName = $key[0] ?? null;
                                    } else {
                                        $realForeignKeyName = $key;
                                    }
                                }
                            }
                        } catch (\Throwable $e) {
                            // Ignora errori
                        }
                    }

                    foreach ($fieldOptions as $fieldKey => $fieldLabel) {
                        // Formatta l'etichetta per renderla più leggibile
                        $formattedLabel = $fieldLabel;

                        if (str_contains($fieldLabel, '.')) {
                            $parts = explode('.', $fieldLabel);
                            $formattedParts = [];
                            foreach ($parts as $part) {
                                $trimmed = trim($part);
                                $formattedParts[] = ucfirst(strtolower($trimmed));
                            }
                            $formattedLabel = implode(' → ', $formattedParts);
                        } elseif (str_contains($fieldLabel, ' - ')) {
                            $formattedLabel = str_replace(' - ', ' → ', $fieldLabel);
                        }

                        // Usa il formato corretto: "parent_property.relation_name.fieldKey"
                        // Es: "blog post.author.name" invece di "author_payload.name"
                        // Questo corrisponde esattamente alla struttura del payload
                        if ($parentProperty) {
                            $fieldPath = "{$parentProperty}.{$relationName}.{$fieldKey}";
                        } else {
                            // Fallback: usa alias se parent_property non è disponibile
                            $fieldPath = "{$alias}.{$fieldKey}";
                        }

                        $relationLabel = $relation['label'] ?? $relationName;
                        $options[$fieldPath] = "{$relationLabel} → {$formattedLabel}";
                    }

                    // Aggiungi anche il campo ID della relazione se disponibile
                    // Usa il nome reale della foreign key se disponibile
                    if ($realForeignKeyName && $parentProperty) {
                        $idFieldPath = "{$parentProperty}.{$realForeignKeyName}";
                        $relationLabel = $relation['label'] ?? $relationName;
                        if (! isset($options[$idFieldPath])) {
                            $options[$idFieldPath] = "{$relationLabel} → ID";
                        }
                    } elseif (isset($relation['id_field']) && ! isset($options[$relation['id_field']])) {
                        $relationLabel = $relation['label'] ?? $alias;
                        $options[$relation['id_field']] = "{$relationLabel} → ID";
                    }
                }
            }

            // Ordina le opzioni per etichetta
            asort($options);

            return $options;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
