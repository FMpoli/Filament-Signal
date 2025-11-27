<?php

namespace Base33\FilamentSignal\Filament\Resources;

use BackedEnum;
use Base33\FilamentSignal\Filament\Resources\SignalModelIntegrationResource\Pages;
use Base33\FilamentSignal\Models\SignalModelIntegration;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid as SchemaGrid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get as SchemaGet;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class SignalModelIntegrationResource extends Resource
{
    protected static ?string $model = SignalModelIntegration::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-link';

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
        return $schema->schema([
            SchemaSection::make(__('filament-signal::signal.model_integrations.sections.details'))
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
                            Forms\Components\TextInput::make('model_class')
                                ->label(__('filament-signal::signal.model_integrations.fields.model_class'))
                                ->required()
                                ->unique(SignalModelIntegration::class, 'model_class', ignoreRecord: true)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set, SchemaGet $get): void {
                                    $set('fields', [
                                        'essential' => [],
                                        'relations' => [],
                                    ]);

                                    if (! $get('model_alias') && is_string($state)) {
                                        $set('model_alias', Str::camel(class_basename($state)));
                                    }
                                }),
                            Forms\Components\TextInput::make('model_alias')
                                ->label(__('filament-signal::signal.model_integrations.fields.model_alias'))
                                ->helperText(__('filament-signal::signal.model_integrations.helpers.model_alias')),
                        ]),
                ]),
            SchemaSection::make(__('filament-signal::signal.model_integrations.sections.fields'))
                ->schema([
                    SchemaGrid::make()
                        ->columns([
                            'default' => 1,
                            '@md' => 2,
                        ])
                        ->schema([
                            Forms\Components\Repeater::make('fields.essential')
                                ->label(__('filament-signal::signal.model_integrations.fields.essential_fields'))
                                ->schema([
                                    Forms\Components\Select::make('field')
                                        ->label(__('filament-signal::signal.model_integrations.fields.field_name'))
                                        ->required()
                                        ->options(fn (SchemaGet $get): array => static::getModelFieldOptions(static::resolveModelClass($get)))
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
                            Forms\Components\Repeater::make('fields.relations')
                                ->label(__('filament-signal::signal.model_integrations.fields.relations'))
                                ->schema([
                                    Forms\Components\Select::make('name')
                                        ->label(__('filament-signal::signal.model_integrations.fields.relation_name'))
                                        ->options(fn (SchemaGet $get): array => static::getModelRelationOptions(static::resolveModelClass($get)))
                                        ->searchable()
                                        ->preload()
                                        ->reactive()
                                        ->live()
                                        ->afterStateHydrated(function ($state, callable $set, SchemaGet $get): void {
                                            if (! $state) {
                                                return;
                                            }

                                            $relatedClass = static::getRelatedModelClass(static::resolveModelClass($get), $state);
                                            $set('related_class', $relatedClass);
                                        })
                                        ->afterStateUpdated(function ($state, callable $set, SchemaGet $get): void {
                                            $relatedClass = static::getRelatedModelClass(static::resolveModelClass($get), $state);
                                            $set('related_class', $relatedClass);
                                            $set('fields', []);
                                            $set('expand', []);
                                        })
                                        ->required(),
                                    Forms\Components\Hidden::make('related_class'),
                                    Forms\Components\Repeater::make('fields')
                                        ->label(__('filament-signal::signal.model_integrations.fields.relation_fields'))
                                        ->schema([
                                            Forms\Components\Select::make('field')
                                                ->label(__('filament-signal::signal.model_integrations.fields.field_name'))
                                                ->options(fn (SchemaGet $get): array => static::getModelFieldOptions($get('../related_class')))
                                                ->required()
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
                                    Forms\Components\Select::make('expand')
                                        ->label(__('filament-signal::signal.model_integrations.fields.expand_relations'))
                                        ->multiple()
                                        ->options(fn (SchemaGet $get): array => static::getModelRelationOptions($get('related_class')))
                                        ->reactive()
                                        ->searchable()
                                        ->preload(),
                                ])
                                ->default([])
                                ->addActionLabel(__('filament-signal::signal.model_integrations.actions.add_relation'))
                                ->reorderable()
                                ->collapsed(),
                        ]),
                ]),
            SchemaSection::make(__('filament-signal::signal.model_integrations.sections.eloquent'))
                ->schema([
                    Forms\Components\CheckboxList::make('eloquent_events')
                        ->label(__('filament-signal::signal.model_integrations.fields.eloquent_events'))
                        ->options(self::eloquentEventOptions())
                        ->columns(2),
                ]),
            SchemaSection::make(__('filament-signal::signal.model_integrations.sections.custom'))
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
                    ->formatStateUsing(fn ($state) => collect($state ?? [])->map(fn ($event) => self::eloquentEventOptions()[$event] ?? $event)->implode(', '))
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

    protected static function getModelRelationOptions(?string $modelClass): array
    {
        $relations = self::analyzeModel($modelClass)['relations'];

        $options = [];

        foreach ($relations as $name => $relatedClass) {
            $label = Str::headline($name);
            if ($relatedClass) {
                $label .= ' (' . class_basename($relatedClass) . ')';
            }

            $options[$name] = $label;
        }

        return $options;
    }

    protected static function getRelatedModelClass(?string $modelClass, ?string $relationName): ?string
    {
        if (! $modelClass || ! $relationName) {
            return null;
        }

        return self::analyzeModel($modelClass)['relations'][$relationName] ?? null;
    }

    /**
     * @return array{fields: array<string, string>, relations: array<string, string>}
     */
    protected static function analyzeModel(?string $modelClass): array
    {
        if (! $modelClass || ! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
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
            ->mapWithKeys(fn ($field) => [$field => Str::headline(str_replace('_', ' ', $field))])
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

    protected static function resolveModelClass(SchemaGet $get): ?string
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
}
