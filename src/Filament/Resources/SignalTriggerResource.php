<?php

namespace Base33\FilamentSignal\Filament\Resources;

use Base33\FilamentSignal\Filament\Resources\SignalTriggerResource\Pages;
use Base33\FilamentSignal\Models\SignalTrigger;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SignalTriggerResource extends Resource
{
    protected static ?string $model = SignalTrigger::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    public static function getNavigationGroup(): ?string
    {
        return __('filament-signal::signal.plugin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-signal::signal.plugin.navigation.rules');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('filament-signal::signal.sections.trigger_details'))
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
                    Forms\Components\TextInput::make('event_class')
                        ->label(__('filament-signal::signal.fields.event_class'))
                        ->required()
                        ->helperText('Provide the fully qualified event class name.'),
                    Forms\Components\Textarea::make('description')
                        ->label(__('filament-signal::signal.fields.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Fieldset::make(__('filament-signal::signal.sections.trigger_conditions'))
                ->schema([
                    Forms\Components\Select::make('match_type')
                        ->label(__('filament-signal::signal.fields.match_type'))
                        ->options([
                            SignalTrigger::MATCH_ALL => __('filament-signal::signal.options.match_type.all'),
                            SignalTrigger::MATCH_ANY => __('filament-signal::signal.options.match_type.any'),
                        ])
                        ->default(SignalTrigger::MATCH_ALL),
                    Builder::make('filters')
                        ->label(__('filament-signal::signal.fields.filters'))
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
                                ]),
                            Block::make('contains')
                                ->label('Contains')
                                ->schema([
                                    Forms\Components\TextInput::make('field')
                                        ->label('Field')
                                        ->required(),
                                    Forms\Components\TextInput::make('value')
                                        ->label('Value')
                                        ->required(),
                                ]),
                        ])
                        ->collapsible(),
                ]),
            Section::make(__('filament-signal::signal.sections.trigger_actions'))
                ->schema([
                    Repeater::make('actions')
                        ->relationship('actions')
                        ->label(__('filament-signal::signal.fields.actions'))
                        ->orderColumn('execution_order')
                        ->schema(static::actionRepeaterSchema())
                        ->collapsible()
                        ->addActionLabel(__('filament-signal::signal.actions.add_action')),
                ])
                ->columnSpanFull(),
        ]);
    }

    protected static function actionRepeaterSchema(): array
    {
        return [
            Forms\Components\Grid::make()
                ->schema([
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
                ])
                ->columns(4),
            Forms\Components\Select::make('template_id')
                ->label(__('filament-signal::signal.fields.template'))
                ->relationship('template', 'name')
                ->searchable()
                ->visible(fn (Get $get): bool => $get('action_type') === 'email')
                ->required(fn (Get $get): bool => $get('action_type') === 'email'),
            Fieldset::make(__('filament-signal::signal.sections.email_configuration'))
                ->visible(fn (Get $get): bool => $get('action_type') === 'email')
                ->schema([
                    Forms\Components\TextInput::make('configuration.subject_override')
                        ->label(__('filament-signal::signal.fields.subject'))
                        ->placeholder('Leave empty to use template subject'),
                    Forms\Components\KeyValue::make('configuration.recipients.to')
                        ->label('To recipients')
                        ->keyLabel('Email')
                        ->valueLabel('Name')
                        ->addButtonLabel('Add recipient'),
                    Forms\Components\KeyValue::make('configuration.recipients.cc')
                        ->label('CC recipients')
                        ->keyLabel('Email')
                        ->valueLabel('Name')
                        ->addButtonLabel('Add recipient'),
                    Forms\Components\KeyValue::make('configuration.recipients.bcc')
                        ->label('BCC recipients')
                        ->keyLabel('Email')
                        ->valueLabel('Name')
                        ->addButtonLabel('Add recipient'),
                ]),
            Fieldset::make(__('filament-signal::signal.sections.webhook_configuration'))
                ->visible(fn (Get $get): bool => $get('action_type') === 'webhook')
                ->schema([
                    Forms\Components\TextInput::make('configuration.url')
                        ->label('Endpoint URL')
                        ->url()
                        ->required(fn (Get $get): bool => $get('action_type') === 'webhook'),
                    Forms\Components\Select::make('configuration.method')
                        ->label('HTTP Method')
                        ->options([
                            'POST' => 'POST',
                            'PUT' => 'PUT',
                            'PATCH' => 'PATCH',
                            'DELETE' => 'DELETE',
                        ])
                        ->default('POST'),
                    Forms\Components\KeyValue::make('configuration.headers')
                        ->label('Headers')
                        ->keyLabel('Header')
                        ->valueLabel('Value')
                        ->addButtonLabel('Add header'),
                    Forms\Components\Select::make('configuration.body')
                        ->label('Payload mode')
                        ->options([
                            'payload' => 'Event payload',
                            'event' => 'Envelope (class + payload)',
                        ])
                        ->default('payload'),
                ]),
        ];
    }

    protected static function getActionTypeOptions(): array
    {
        return collect(config('signal.action_handlers', []))
            ->keys()
            ->mapWithKeys(fn (string $type) => [$type => ucfirst($type)])
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
                ViewAction::make()->slideOver(),
                EditAction::make()->slideOver(),
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
        ];
    }
}
