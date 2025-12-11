<?php

namespace Base33\FilamentSignal\Filament\Resources;

use BackedEnum;
use Base33\FilamentSignal\Filament\Resources\SignalWorkflowResource\Pages;
use Base33\FilamentSignal\Models\SignalWorkflow;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SignalWorkflowResource extends Resource
{
    protected static ?string $model = SignalWorkflow::class;

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
                        ->heading(fn (SignalWorkflow $record) => $record->name)
                        ->schema([
                            TextEntry::make('description')
                                ->label(__('filament-signal::signal.fields.description'))
                                ->placeholder('—')
                                ->visible(fn (SignalWorkflow $record) => ! empty($record->description))
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpan(12),
            ]);
    }

    public static function getNavigationLabel(): string
    {
        return 'Workflows'; // Temporary label
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Group::make([
                    Section::make('Workflow Details')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required(),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'active' => 'Active',
                                    'disabled' => 'Disabled',
                                ])
                                ->default('draft')
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(12),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'active',
                        'danger' => 'disabled',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('flow')
                    ->label('Editor')
                    ->icon('heroicon-o-cpu-chip')
                    ->url(fn (SignalWorkflow $record) => static::getUrl('flow', ['record' => $record])),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSignalWorkflows::route('/'),
            'create' => Pages\CreateSignalWorkflow::route('/create'),
            'view' => Pages\ViewSignalWorkflow::route('/{record}'),
            'edit' => Pages\EditSignalWorkflow::route('/{record}/edit'),
            'flow' => Pages\FlowSignalWorkflow::route('/{record}/flow'),
        ];
    }
}
