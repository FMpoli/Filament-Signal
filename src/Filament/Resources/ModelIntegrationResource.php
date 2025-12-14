<?php

namespace Voodflow\Voodflow\Filament\Resources;

use BackedEnum;
use Voodflow\Voodflow\Filament\Resources\ModelIntegrationResource\Pages;
use Voodflow\Voodflow\Models\ModelIntegration;
use Voodflow\Voodflow\Support\ReverseRelationRegistry;
use Voodflow\Voodflow\Support\ModelRegistry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class ModelIntegrationResource extends Resource
{
    protected static ?string $model = ModelIntegration::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-link';

    protected static array $modelMetadataCache = [];

    public static function getNavigationGroup(): ?string
    {
        return __('filament-signal::signal.plugin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-signal::signal.plugin.navigation.integrations');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Group::make([
                    Section::make(__('filament-signal::signal.model_integrations.sections.details'))
                        ->icon('heroicon-o-cube')
                        ->compact()
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(__('filament-signal::signal.fields.name'))
                                ->required(),
                            Forms\Components\Select::make('model_class')
                                ->label(__('filament-signal::signal.model_integrations.fields.model_class'))
                                ->required()
                                ->unique(ModelIntegration::class, 'model_class', ignoreRecord: true)
                                ->searchable()
                                ->preload()
                                ->options(fn() => static::getAvailableModelOptions())
                                ->getSearchResultsUsing(fn(string $search): array => static::getAvailableModelOptions($search))
                                ->getOptionLabelUsing(fn(?string $value): ?string => $value ? class_basename($value) : null)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set, Get $get): void {
                                    $set('fields', [
                                        'essential' => [],
                                        'relations' => [],
                                    ]);

                                    if (!$get('model_alias') && is_string($state)) {
                                        $set('model_alias', Str::camel(class_basename($state)));
                                    }
                                }),
                            Forms\Components\TextInput::make('model_alias')
                                ->label(__('filament-signal::signal.model_integrations.fields.model_alias'))
                                ->helperText(__('filament-signal::signal.model_integrations.helpers.model_alias')),
                        ])->columns(1),
                    Section::make(__('filament-signal::signal.model_integrations.sections.eloquent'))
                        ->icon('heroicon-o-heart')
                        ->compact()
                        ->schema([
                            Forms\Components\CheckboxList::make('eloquent_events')
                                ->options(self::eloquentEventOptions())
                                ->columns(2),
                        ]),
                ])->columnSpan(4),
                Group::make([
                    Tabs::make('fields_tabs')

                        ->tabs([
                            Tab::make('essential_fields')
                                ->label(__('filament-signal::signal.model_integrations.fields.essential_fields'))
                                ->icon('heroicon-o-list-bullet')
                                ->schema([
                                    Forms\Components\Repeater::make('fields.essential')
                                        ->compact()
                                        ->label(__('filament-signal::signal.model_integrations.fields.essential_fields'))
                                        ->itemLabel(fn(array $state): ?string => ($state['field'] ?? ''))
                                        ->schema([
                                            Forms\Components\Select::make('field')
                                                ->label(__('filament-signal::signal.model_integrations.fields.field_name'))
                                                ->required()
                                                ->options(fn(Get $get): array => static::getModelFieldOptions(static::resolveModelClass($get)))
                                                ->reactive()
                                                ->searchable()
                                                ->preload(),
                                            Forms\Components\TextInput::make('label')
                                                ->label(__('filament-signal::signal.model_integrations.fields.field_label')),
                                        ])
                                        ->default([])
                                        ->addActionLabel(__('filament-signal::signal.model_integrations.actions.add_field'))
                                        ->reorderable()
                                        ->collapsed(),
                                ]),
                            Tab::make('relations')
                                ->label(__('filament-signal::signal.model_integrations.fields.relations'))
                                ->icon('heroicon-o-circle-stack')
                                ->schema([
                                    Forms\Components\Repeater::make('fields.relations')
                                        ->label(__('filament-signal::signal.model_integrations.fields.relations'))
                                        ->itemLabel(fn(array $state): ?string => ($state['name'] ?? ''))
                                        ->schema([
                                            Forms\Components\Select::make('name')
                                                ->label(__('filament-signal::signal.model_integrations.fields.relation_name'))
                                                ->options(fn(Get $get): array => static::getRelationOptions(static::resolveModelClass($get)))
                                                ->searchable()
                                                ->preload()
                                                ->reactive()
                                                ->live()
                                                ->afterStateHydrated(function ($state, callable $set, Get $get): void {
                                                    if (!$state) {
                                                        return;
                                                    }

                                                    static::syncRelationMetadata($state, $set, $get);
                                                })
                                                ->afterStateUpdated(function ($state, callable $set, Get $get): void {
                                                    static::syncRelationMetadata($state, $set, $get);
                                                    $set('fields', []);
                                                    $set('expand', []);
                                                })
                                                ->required(),
                                            Forms\Components\Hidden::make('related_class'),
                                            Forms\Components\Hidden::make('relation_mode')->default('direct'),
                                            Forms\Components\Hidden::make('relation_descriptor'),
                                            Forms\Components\TextInput::make('alias')
                                                ->label(__('filament-signal::signal.model_integrations.fields.relation_alias'))
                                                ->placeholder('loans_sent')
                                                ->helperText(__('filament-signal::signal.model_integrations.helpers.relation_alias')),
                                            Forms\Components\Repeater::make('fields')
                                                ->label(__('filament-signal::signal.model_integrations.fields.relation_fields'))
                                                ->itemLabel(fn(array $state): ?string => ($state['field'] ?? ''))
                                                ->schema([
                                                    Forms\Components\Select::make('field')
                                                        ->label(__('filament-signal::signal.model_integrations.fields.field_name'))
                                                        ->options(fn(Get $get): array => static::getRelationFieldOptions($get))
                                                        ->required()
                                                        ->live()
                                                        ->reactive()
                                                        ->searchable()
                                                        ->preload()
                                                        ->afterStateHydrated(function (Get $get, callable $set): void {
                                                            // Force refresh when relation changes
                                                            $get('../../name');
                                                        }),
                                                    Forms\Components\TextInput::make('label')
                                                        ->label(__('filament-signal::signal.model_integrations.fields.field_label')),
                                                ])
                                                ->default([])
                                                ->addActionLabel(__('filament-signal::signal.model_integrations.actions.add_field'))

                                                ->reorderable()
                                                ->columnSpan(2)
                                                ->collapsed(),
                                            Forms\Components\Select::make('expand')
                                                ->label(__('filament-signal::signal.model_integrations.fields.expand_relations'))
                                                ->multiple()
                                                ->options(fn(Get $get): array => static::getRelationExpandOptions($get))
                                                ->reactive()
                                                ->searchable()
                                                ->hidden()
                                                ->preload(),
                                        ])
                                        ->columns(2)
                                        ->default([])
                                        ->addActionLabel(__('filament-signal::signal.model_integrations.actions.add_relation'))
                                        ->reorderable()
                                        ->collapsed(),
                                ]),
                            Tab::make('custom_events')
                                ->label(__('filament-signal::signal.model_integrations.fields.custom_events'))
                                ->icon('heroicon-o-bolt')
                                ->schema([
                                    Forms\Components\Repeater::make('custom_events')
                                        ->label(__('filament-signal::signal.model_integrations.fields.custom_events'))
                                        ->schema([
                                            Forms\Components\TextInput::make('class')
                                                ->label(__('filament-signal::signal.model_integrations.fields.event_class'))
                                                ->required(),
                                            Forms\Components\TextInput::make('label')
                                                ->label(__('filament-signal::signal.fields.name')),
                                            Forms\Components\TextInput::make('group')
                                                ->label(__('filament-signal::signal.model_integrations.fields.event_group')),
                                            Forms\Components\Textarea::make('description')
                                                ->label(__('filament-signal::signal.model_integrations.fields.event_description'))
                                                ->rows(2),
                                        ])
                                        ->default([])
                                        ->addActionLabel(__('filament-signal::signal.model_integrations.actions.add_event'))
                                        ->collapsed(),
                                ]),
                        ]),
                ])
                    ->columnSpan(8),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-signal::signal.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model_class')
                    ->label(__('filament-signal::signal.model_integrations.fields.model_class'))
                    ->copyable()
                    ->copyMessage(__('filament::components/copyable.messages.copied'))
                    ->copyMessageDuration(1500)
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model_alias')
                    ->label(__('filament-signal::signal.model_integrations.fields.model_alias'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('eloquent_events')
                    ->label(__('filament-signal::signal.model_integrations.fields.eloquent_events'))
                    ->formatStateUsing(fn($state) => collect($state ?? [])->map(fn($event) => self::eloquentEventOptions()[$event] ?? $event)->implode(', '))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('filament-signal::signal.fields.updated_at'))
                    ->dateTime(),
            ])
            ->actions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
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
            'index' => Pages\ListSignalModelIntegrations::route('/'),
            'create' => Pages\CreateSignalModelIntegration::route('/create'),
            'edit' => Pages\EditSignalModelIntegration::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function eloquentEventOptions(): array
    {
        return [
            'created' => __('filament-signal::signal.model_integrations.operations.created'),
            'updated' => __('filament-signal::signal.model_integrations.operations.updated'),
            'deleted' => __('filament-signal::signal.model_integrations.operations.deleted'),
            'restored' => __('filament-signal::signal.model_integrations.operations.restored'),
        ];
    }

    protected static function getModelFieldOptions(?string $modelClass): array
    {
        return self::analyzeModel($modelClass)['fields'];
    }

    protected static function getRelationOptions(?string $modelClass): array
    {
        $options = [];

        foreach (self::analyzeModel($modelClass)['relations'] as $name => $relatedClass) {
            $label = Str::headline($name);
            if ($relatedClass) {
                $label .= ' (' . class_basename($relatedClass) . ')';
            }

            $options[$name] = $label;
        }

        foreach (app(ReverseRelationRegistry::class)->for($modelClass) as $descriptor) {
            $key = 'reverse::' . $descriptor['key'];
            $options[$key] = $descriptor['label'] . ' · ' . __('filament-signal::signal.model_integrations.labels.reverse');
        }

        return $options;
    }

    protected static function getRelatedModelClass(?string $modelClass, ?string $relationName): ?string
    {
        if (!$modelClass || !$relationName) {
            return null;
        }

        return self::analyzeModel($modelClass)['relations'][$relationName] ?? null;
    }

    /**
     * @return array{fields: array<string, string>, relations: array<string, string>}
     */
    protected static function analyzeModel(?string $modelClass): array
    {
        if (!$modelClass || !class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            return [
                'fields' => [],
                'relations' => [],
            ];
        }

        if (isset(self::$modelMetadataCache[$modelClass])) {
            return self::$modelMetadataCache[$modelClass];
        }

        try {
            $model = app($modelClass);
        } catch (\Throwable $exception) {
            return [
                'fields' => [],
                'relations' => [],
            ];
        }

        $fields = $model->getFillable();

        if (empty($fields)) {
            try {
                $table = $model->getTable();
                if ($table && DatabaseSchema::hasTable($table)) {
                    $fields = DatabaseSchema::getColumnListing($table);
                }
            } catch (\Throwable $exception) {
                // ignore
            }
        }

        $fieldOptions = collect($fields ?? [])
            ->merge(['id', 'created_at', 'updated_at'])
            ->unique()
            ->mapWithKeys(fn($field) => [$field => Str::headline(str_replace('_', ' ', $field))])
            ->toArray();

        $relations = [];

        try {
            $reflection = new ReflectionClass($modelClass);

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (
                    $method->isStatic()
                    || $method->isAbstract()
                    || $method->getName() === '__construct'
                    || $method->getNumberOfParameters() > 0
                ) {
                    continue;
                }

                try {
                    $relation = $model->{$method->getName()}();

                    if ($relation instanceof Relation) {
                        $relations[$method->getName()] = get_class($relation->getRelated());
                    }
                } catch (\Throwable $exception) {
                    continue;
                }
            }
        } catch (\Throwable $exception) {
            // ignore
        }

        return self::$modelMetadataCache[$modelClass] = [
            'fields' => $fieldOptions,
            'relations' => $relations,
        ];
    }

    protected static function syncRelationMetadata(string $state, callable $set, Get $get): void
    {
        if (str_starts_with($state, 'reverse::')) {
            $descriptorKey = substr($state, 9);
            $descriptor = app(ReverseRelationRegistry::class)->find($descriptorKey);

            $set('relation_mode', 'reverse');
            $set('relation_descriptor', $descriptorKey);
            $set('related_class', $descriptor['source_model'] ?? null);

            if (blank($get('alias'))) {
                $set('alias', static::defaultReverseAlias($descriptor));
            }

            return;
        }

        $relatedClass = static::getRelatedModelClass(static::resolveModelClass($get), $state);
        $set('relation_mode', 'direct');
        $set('relation_descriptor', null);
        $set('related_class', $relatedClass);

        if (blank($get('alias'))) {
            $set('alias', static::defaultDirectAlias($state));
        }
    }

    protected static function getRelationFieldOptions(Get $get): array
    {
        $mode = static::resolveRelationMode($get);

        if ($mode === 'reverse') {
            $descriptorKey = static::resolveRelationDescriptorKey($get);
            if (!$descriptorKey) {
                return [];
            }

            $descriptor = app(ReverseRelationRegistry::class)->find($descriptorKey);
            if (!$descriptor) {
                return [];
            }

            // Per le relazioni inverse, usa i campi essenziali del modello sorgente (es: EquipmentLoan)
            // e include anche i campi delle relazioni annidate (es: unit.inventory_code, unit.model.name)
            $sourceModel = $descriptor['source_model'] ?? null;
            if ($sourceModel && class_exists($sourceModel)) {
                $registry = app(ModelRegistry::class);
                $modelFields = $registry->getFields($sourceModel);

                $allFieldOptions = [];

                // Aggiungi i campi essenziali
                if ($modelFields && isset($modelFields['essential'])) {
                    $essentialFields = $modelFields['essential'];
                    $fieldNames = [];
                    foreach ($essentialFields as $key => $value) {
                        if (is_int($key)) {
                            $fieldNames[] = $value;
                        } else {
                            $fieldNames[] = $key;
                        }
                    }
                    $allFieldOptions = array_merge($allFieldOptions, static::formatFieldOptions($fieldNames, $sourceModel));
                }

                // Aggiungi i campi delle relazioni annidate (ricorsivamente)
                if ($modelFields && isset($modelFields['relations'])) {
                    static::collectNestedRelationFields(
                        $modelFields['relations'],
                        $sourceModel,
                        $registry,
                        $allFieldOptions,
                        ''
                    );
                }

                if (!empty($allFieldOptions)) {
                    return $allFieldOptions;
                }
            }

            // Fallback: usa i campi configurati nella relazione (se esistono)
            $fields = $descriptor['model_fields']['fields'] ?? [];

            return static::formatFieldOptions($fields, $sourceModel);
        }

        $relatedClass = static::resolveRelatedClass($get);
        if (!$relatedClass) {
            return [];
        }

        return static::getModelFieldOptions($relatedClass);
    }

    protected static function getRelationExpandOptions(Get $get): array
    {
        $mode = static::resolveRelationMode($get);

        if ($mode === 'reverse') {
            $descriptorKey = static::resolveRelationDescriptorKey($get);
            if (!$descriptorKey) {
                return [];
            }

            $descriptor = app(ReverseRelationRegistry::class)->find($descriptorKey);
            if (!$descriptor) {
                return [];
            }

            $expand = $descriptor['model_fields']['expand'] ?? [];

            return array_combine($expand, $expand);
        }

        $relatedClass = static::resolveRelatedClass($get);
        if (!$relatedClass) {
            return [];
        }

        $relations = self::analyzeModel($relatedClass)['relations'];

        $options = [];
        foreach ($relations as $name => $class) {
            $options[$name] = Str::headline($name);
        }

        return $options;
    }

    protected static function defaultDirectAlias(string $relationName): string
    {
        return Str::camel($relationName);
    }

    protected static function defaultReverseAlias(?array $descriptor): string
    {
        if (!$descriptor) {
            return Str::camel('reverse_relation');
        }

        return Str::camel(class_basename($descriptor['source_model'] ?? 'relation') . '_' . ($descriptor['relation_name'] ?? 'related'));
    }

    protected static function resolveRelationMode(Get $get): string
    {
        $paths = [
            'relation_mode',
            '../relation_mode',
            '../../relation_mode',
            '../../../relation_mode',
        ];

        foreach ($paths as $path) {
            $value = $get($path);
            if ($value) {
                return $value;
            }
        }

        return 'direct';
    }

    protected static function resolveRelatedClass(Get $get): ?string
    {
        $paths = [
            'related_class',
            '../related_class',
            '../../related_class',
            '../../../related_class',
        ];

        foreach ($paths as $path) {
            $value = $get($path);
            if ($value) {
                return $value;
            }
        }

        return null;
    }

    protected static function resolveRelationDescriptorKey(Get $get): ?string
    {
        $paths = [
            'relation_descriptor',
            '../relation_descriptor',
            '../../relation_descriptor',
            '../../../relation_descriptor',
        ];

        foreach ($paths as $path) {
            $value = $get($path);
            if ($value) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Ottiene la classe del modello correlato da una relazione usando la reflection.
     */
    protected static function getRelatedModelClassFromRelation(string $modelClass, string $relationName): ?string
    {
        if (!class_exists($modelClass)) {
            return null;
        }

        try {
            $model = new $modelClass;

            // Verifica se il metodo di relazione esiste
            if (!method_exists($model, $relationName)) {
                return null;
            }

            // Chiama il metodo di relazione per ottenere l'oggetto relazione
            $relation = $model->{$relationName}();

            // Se è una relazione Eloquent, ottieni il modello correlato
            if (method_exists($relation, 'getRelated')) {
                $relatedModel = $relation->getRelated();

                return get_class($relatedModel);
            }

            // Fallback: usa reflection per ottenere il tipo di ritorno del metodo
            $reflection = new ReflectionClass($modelClass);
            if ($reflection->hasMethod($relationName)) {
                $method = $reflection->getMethod($relationName);
                $returnType = $method->getReturnType();

                if ($returnType instanceof \ReflectionNamedType) {
                    $returnTypeClass = $returnType->getName();
                    // Se è una classe di relazione Eloquent, prova a ottenere il modello correlato
                    if (class_exists($returnTypeClass)) {
                        if (is_subclass_of($returnTypeClass, Model::class)) {
                            return $returnTypeClass;
                        }
                    }
                }
            }

            // Fallback: prova a indovinare dal nome della relazione
            $guessedClass = static::guessModelClassFromRelationName($relationName, $modelClass);
            if ($guessedClass && class_exists($guessedClass)) {
                return $guessedClass;
            }
        } catch (\Throwable $e) {
            // Ignora errori
        }

        return null;
    }

    /**
     * Tenta di indovinare la classe del modello dal nome della relazione.
     */
    protected static function guessModelClassFromRelationName(string $relationName, string $currentModelClass): ?string
    {
        // Nomi comuni che puntano a User
        $userRelations = ['borrower', 'loaner', 'author', 'creator', 'owner', 'created_by', 'updated_by', 'user'];

        if (in_array($relationName, $userRelations)) {
            return \App\Models\User::class;
        }

        // Prova a derivare dal nome della relazione (es: unit -> Unit)
        $className = Str::studly($relationName);
        $namespace = substr($currentModelClass, 0, strrpos($currentModelClass, '\\'));

        // Prova vari namespace comuni
        $possibleClasses = [
            $namespace . '\\' . $className,
            'App\\Models\\' . $className,
            // 'Detit\\FilamentLabOps\\Models\\' . $className,
        ];

        foreach ($possibleClasses as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Raccoglie ricorsivamente tutti i campi delle relazioni annidate.
     *
     * @param  array<string, mixed>  $relations  Configurazione delle relazioni
     * @param  string  $modelClass  Classe del modello corrente
     * @param  ModelRegistry  $registry  Registry per ottenere i campi dei modelli
     * @param  array<string, string>  &$allFieldOptions  Array di output per le opzioni dei campi
     * @param  string  $basePath  Path base corrente (es: 'unit' o 'unit.model')
     */
    protected static function collectNestedRelationFields(
        array $relations,
        string $modelClass,
        ModelRegistry $registry,
        array &$allFieldOptions,
        string $basePath = ''
    ): void {
        foreach ($relations as $relationName => $relationConfig) {
            $relationExpand = $relationConfig['expand'] ?? [];
            $relationFields = $relationConfig['fields'] ?? [];
            $relatedModelClass = static::getRelatedModelClassFromRelation($modelClass, $relationName);

            if (!$relatedModelClass) {
                continue;
            }

            $currentPath = $basePath === '' ? $relationName : "{$basePath}.{$relationName}";

            // Aggiungi i campi della relazione principale (es: unit.inventory_code o unit.model.name)
            if (!empty($relationFields)) {
                $nestedFieldOptions = static::formatFieldOptions($relationFields, $relatedModelClass);
                foreach ($nestedFieldOptions as $fieldKey => $fieldLabel) {
                    $fullKey = "{$currentPath}.{$fieldKey}";
                    $labelPath = str_replace('.', ' → ', $currentPath);
                    $allFieldOptions[$fullKey] = "{$labelPath} → {$fieldLabel}";
                }
            }

            // Se ci sono relazioni annidate da espandere, processale ricorsivamente
            if (!empty($relationExpand)) {
                $relatedModelFields = $registry->getFields($relatedModelClass);

                // Se il modello correlato ha relazioni configurate, processale
                if ($relatedModelFields && isset($relatedModelFields['relations'])) {
                    // Filtra solo le relazioni che sono in expand
                    $nestedRelations = [];
                    foreach ($relationExpand as $nestedRelationName) {
                        if (isset($relatedModelFields['relations'][$nestedRelationName])) {
                            $nestedRelations[$nestedRelationName] = $relatedModelFields['relations'][$nestedRelationName];
                        } else {
                            // Se la relazione non è configurata, crea una entry di base per permettere la selezione
                            // Verifica che la relazione esista nel modello
                            $nestedRelatedModelClass = static::getRelatedModelClassFromRelation($relatedModelClass, $nestedRelationName);
                            if ($nestedRelatedModelClass) {
                                // Crea una configurazione di base con campi essenziali comuni
                                $nestedRelations[$nestedRelationName] = [
                                    'fields' => ['id', 'name'], // Campi essenziali comuni
                                    'expand' => [],
                                ];
                            }
                        }
                    }

                    // Processa ricorsivamente
                    if (!empty($nestedRelations)) {
                        static::collectNestedRelationFields(
                            $nestedRelations,
                            $relatedModelClass,
                            $registry,
                            $allFieldOptions,
                            $currentPath
                        );
                    }
                } else {
                    // Se il modello correlato non ha getSignalFields configurato,
                    // aggiungi comunque i campi essenziali comuni per le relazioni in expand
                    foreach ($relationExpand as $nestedRelationName) {
                        $nestedRelatedModelClass = static::getRelatedModelClassFromRelation($relatedModelClass, $nestedRelationName);
                        if ($nestedRelatedModelClass) {
                            // Aggiungi campi essenziali comuni
                            $commonFields = ['id', 'name'];
                            $nestedFieldOptions = static::formatFieldOptions($commonFields, $nestedRelatedModelClass);
                            foreach ($nestedFieldOptions as $fieldKey => $fieldLabel) {
                                $fullKey = "{$currentPath}.{$nestedRelationName}.{$fieldKey}";
                                $labelPath = str_replace('.', ' → ', $currentPath);
                                $allFieldOptions[$fullKey] = "{$labelPath} → {$nestedRelationName} → {$fieldLabel}";
                            }
                        }
                    }
                }
            }
        }
    }

    protected static function formatFieldOptions(array $fields, ?string $modelClass = null): array
    {
        $options = [];

        foreach ($fields as $key => $value) {
            if (is_int($key)) {
                $fieldKey = $value;
                // Prova a ottenere la traduzione se disponibile
                $label = static::getTranslatedFieldLabel($fieldKey, $modelClass);
                if (!$label) {
                    $label = Str::headline(str_replace('_', ' ', $fieldKey));
                }
            } else {
                $fieldKey = $key;
                $label = $value;
            }

            $options[$fieldKey] = $label;
        }

        return $options;
    }

    /**
     * Ottiene l'etichetta tradotta per un campo.
     */
    protected static function getTranslatedFieldLabel(string $fieldKey, ?string $modelClass = null): ?string
    {
        if (!$modelClass) {
            return null;
        }

        // Prova a ottenere la traduzione dal package filament-signal
        $translationKey = "signal.fields.{$fieldKey}";
        $translated = trans($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        // Prova con il nome del modello
        $modelName = class_basename($modelClass);
        $translationKey = "signal.models.{$modelName}.fields.{$fieldKey}";
        $translated = trans($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        return null;
    }

    protected static function resolveModelClass(Get $get): ?string
    {
        $paths = [
            'model_class',
            '../model_class',
            '../../model_class',
            '../../../model_class',
            '../../../../model_class',
            '../../../../../model_class',
        ];

        foreach ($paths as $path) {
            $value = $get($path);
            if ($value) {
                return $value;
            }
        }

        if ($record = $get('record')) {
            return $record->model_class ?? null;
        }

        $requestData = request()->input('data');

        if (is_array($requestData) && isset($requestData['model_class'])) {
            return $requestData['model_class'];
        }

        return null;
    }

    /**
     * Ottiene tutti i modelli Eloquent disponibili nel sistema e dai plugin Filament registrati.
     *
     * @param  string|null  $search  Termine di ricerca opzionale
     * @return array<string, string> Array associativo [classe_completa => nome_visualizzato]
     */
    protected static function getAvailableModelOptions(?string $search = null): array
    {
        $excludedModels = config('signal.excluded_models', []);
        $models = [];

        // 1. Trova modelli dai Resource Filament registrati nel pannello corrente
        try {
            $panel = Filament::getCurrentPanel();
            if ($panel) {
                $resources = $panel->getResources();
                foreach ($resources as $resourceClass) {
                    if (!class_exists($resourceClass)) {
                        continue;
                    }

                    try {
                        $reflection = new ReflectionClass($resourceClass);
                        if (!$reflection->hasProperty('model')) {
                            continue;
                        }

                        $modelProperty = $reflection->getStaticPropertyValue('model');
                        if ($modelProperty && is_string($modelProperty) && class_exists($modelProperty)) {
                            if (is_subclass_of($modelProperty, Model::class)) {
                                if (!in_array($modelProperty, $excludedModels)) {
                                    $models[$modelProperty] = $modelProperty;
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        // Ignora errori di reflection
                        continue;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Se non c'è un pannello corrente, continua con altri metodi
        }

        // 2. Cerca modelli nelle directory standard di Laravel
        $modelPaths = [
            app_path('Models'),
        ];

        foreach ($modelPaths as $path) {
            if (!File::exists($path)) {
                continue;
            }

            $files = File::allFiles($path);
            foreach ($files as $file) {
                $className = 'App\\Models\\' . str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
                $className = str_replace('App\\Models\\' . basename($path) . '\\', 'App\\Models\\', $className);

                if (class_exists($className) && is_subclass_of($className, Model::class)) {
                    if (!in_array($className, $excludedModels)) {
                        $models[$className] = $className;
                    }
                }
            }
        }

        // 3. Cerca modelli nei vendor/package (plugin Filament)
        $vendorPath = base_path('vendor');
        if (File::exists($vendorPath)) {
            $vendorDirs = File::directories($vendorPath);
            foreach ($vendorDirs as $vendorDir) {
                $packageDirs = File::directories($vendorDir);
                foreach ($packageDirs as $packageDir) {
                    $modelsPath = $packageDir . '/src/Models';
                    if (File::exists($modelsPath)) {
                        $files = File::allFiles($modelsPath);
                        foreach ($files as $file) {
                            $relativePath = str_replace([$packageDir . '/src/', '.php'], ['', ''], $file->getPathname());
                            $className = str_replace('/', '\\', $relativePath);

                            // Prova a determinare il namespace dal composer.json o dalla struttura
                            $composerPath = $packageDir . '/composer.json';
                            if (File::exists($composerPath)) {
                                $composer = json_decode(File::get($composerPath), true);
                                if (isset($composer['autoload']['psr-4'])) {
                                    foreach ($composer['autoload']['psr-4'] as $namespace => $path) {
                                        if (strpos($relativePath, $path) === 0 || $path === 'src/') {
                                            $className = rtrim($namespace, '\\') . '\\' . str_replace(['src/', '/'], ['', '\\'], $relativePath);

                                            break;
                                        }
                                    }
                                }
                            }

                            if (class_exists($className) && is_subclass_of($className, Model::class)) {
                                if (!in_array($className, $excludedModels)) {
                                    $models[$className] = $className;
                                }
                            }
                        }
                    }
                }
            }
        }

        // 4. Cerca modelli nei package locali (app/packages)
        $packagesPath = app_path('packages');
        if (File::exists($packagesPath)) {
            $vendorDirs = File::directories($packagesPath);
            foreach ($vendorDirs as $vendorDir) {
                $packageDirs = File::directories($vendorDir);
                foreach ($packageDirs as $packageDir) {
                    $modelsPath = $packageDir . '/src/Models';
                    if (File::exists($modelsPath)) {
                        $files = File::allFiles($modelsPath);
                        foreach ($files as $file) {
                            $relativePath = str_replace([$packageDir . '/src/Models/', '.php'], ['', ''], $file->getRelativePathname());

                            // Determina il namespace dalla struttura delle directory
                            $vendorName = basename($vendorDir);
                            $packageName = basename($packageDir);

                            // Costruisci il namespace completo
                            $namespaceParts = [];
                            if ($relativePath) {
                                $pathParts = explode('/', $relativePath);
                                $namespaceParts = array_map(fn($part) => str_replace(' ', '', ucwords(str_replace('_', ' ', $part))), $pathParts);
                            }

                            $modelName = !empty($namespaceParts) ? end($namespaceParts) : '';
                            $subNamespace = !empty($namespaceParts) ? '\\' . implode('\\', array_slice($namespaceParts, 0, -1)) : '';

                            $className = "{$vendorName}\\{$packageName}\\Models{$subNamespace}\\{$modelName}";

                            if (class_exists($className) && is_subclass_of($className, Model::class)) {
                                if (!in_array($className, $excludedModels)) {
                                    $models[$className] = $className;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Filtra per ricerca se fornita
        if ($search) {
            $models = array_filter($models, function ($modelClass) use ($search) {
                return stripos($modelClass, $search) !== false ||
                    stripos(class_basename($modelClass), $search) !== false;
            });
        }

        // Ordina e formatta per la visualizzazione
        ksort($models);
        $options = [];
        foreach ($models as $modelClass) {
            $basename = class_basename($modelClass);
            $namespace = str_replace('\\' . $basename, '', $modelClass);

            // Crea un'etichetta più leggibile
            $label = $basename;
            if ($namespace && $namespace !== 'App\\Models') {
                // Estrai il nome del package/plugin dal namespace
                $namespaceParts = explode('\\', $namespace);

                // Rimuovi "Models" se presente
                $namespaceParts = array_filter($namespaceParts, fn($part) => $part !== 'Models');

                // Prendi solo il nome del package (ultima parte significativa)
                if (count($namespaceParts) >= 2) {
                    // Prendi solo il nome del package (es: FilamentSignal da Base33\FilamentSignal)
                    $packageName = end($namespaceParts);

                    // Rimuovi "Filament" se presente e capitalizza la prima lettera
                    $packageName = str_replace('Filament', '', $packageName);
                    $packageName = ucfirst($packageName);

                    if ($packageName) {
                        $label .= ' (' . $packageName . ')';
                    }
                } elseif (count($namespaceParts) > 0) {
                    // Se c'è solo una parte, usala dopo aver rimosso "Filament"
                    $lastPart = end($namespaceParts);
                    if ($lastPart && $lastPart !== 'App') {
                        $lastPart = str_replace('Filament', '', $lastPart);
                        $lastPart = ucfirst($lastPart);
                        if ($lastPart) {
                            $label .= ' (' . $lastPart . ')';
                        }
                    }
                }
            }

            $options[$modelClass] = $label;
        }

        return $options;
    }
}
