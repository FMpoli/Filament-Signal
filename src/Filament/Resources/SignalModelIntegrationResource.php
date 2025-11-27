<?php

namespace Base33\FilamentSignal\Filament\Resources;

use BackedEnum;
use Base33\FilamentSignal\Filament\Resources\SignalModelIntegrationResource\Pages;
use Base33\FilamentSignal\Models\SignalModelIntegration;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid as SchemaGrid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SignalModelIntegrationResource extends Resource
{
    protected static ?string $model = SignalModelIntegration::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-link';

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
                                ->unique(SignalModelIntegration::class, 'model_class', ignoreRecord: true),
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
                                    Forms\Components\TextInput::make('field')
                                        ->label(__('filament-signal::signal.model_integrations.fields.field_name'))
                                        ->required(),
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
                                    Forms\Components\TextInput::make('name')
                                        ->label(__('filament-signal::signal.model_integrations.fields.relation_name'))
                                        ->required(),
                                    Forms\Components\Repeater::make('fields')
                                        ->label(__('filament-signal::signal.model_integrations.fields.relation_fields'))
                                        ->schema([
                                            Forms\Components\TextInput::make('field')
                                                ->label(__('filament-signal::signal.model_integrations.fields.field_name'))
                                                ->required(),
                                            Forms\Components\TextInput::make('label')
                                                ->label(__('filament-signal::signal.model_integrations.fields.field_label')),
                                        ])
                                        ->default([])
                                        ->addActionLabel(__('filament-signal::signal.model_integrations.actions.add_field'))
                                        ->reorderable()
                                        ->collapsed(),
                                    Forms\Components\TagsInput::make('expand')
                                        ->label(__('filament-signal::signal.model_integrations.fields.expand_relations'))
                                        ->placeholder('relation_name'),
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
                Tables\Actions\EditAction::make()->slideOver(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
}
