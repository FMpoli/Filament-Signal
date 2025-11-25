<?php

namespace Base33\FilamentSignal\Filament\Resources;

use Base33\FilamentSignal\Filament\Resources\SignalActionLogResource\Pages;
use Base33\FilamentSignal\Models\SignalActionLog;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class SignalActionLogResource extends Resource
{
    protected static ?string $model = SignalActionLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getNavigationGroup(): ?string
    {
        return __('filament-signal::signal.plugin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-signal::signal.plugin.navigation.logs');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([
                Tables\Columns\TextColumn::make('trigger.name')
                    ->label(__('filament-signal::signal.fields.name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('action.name')
                    ->label(__('filament-signal::signal.fields.actions'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('event_class')
                    ->label(__('filament-signal::signal.fields.event_class'))
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('filament-signal::signal.fields.status'))
                    ->colors([
                        'gray' => 'pending',
                        'success' => 'success',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('executed_at')
                    ->label(__('filament-signal::signal.fields.executed_at'))
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('filament-signal::signal.fields.status'))
                    ->options([
                        'pending' => __('filament-signal::signal.options.action_status.pending'),
                        'success' => __('filament-signal::signal.options.action_status.success'),
                        'failed' => __('filament-signal::signal.options.action_status.failed'),
                    ]),
            ])
            ->actions([
                Action::make('viewLog')
                    ->label(__('filament-signal::signal.actions.view_log'))
                    ->icon('heroicon-o-eye')
                    ->modalWidth('3xl')
                    ->infolist(fn (SignalActionLog $record, Infolist $infolist): Infolist => static::infolist($infolist->record($record))),
            ])
            ->defaultSort('executed_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make(__('filament-signal::signal.sections.log_details'))
                    ->schema([
                        TextEntry::make('trigger.name')->label(__('filament-signal::signal.fields.name')),
                        TextEntry::make('action.name')->label(__('filament-signal::signal.fields.actions')),
                        TextEntry::make('event_class')->label(__('filament-signal::signal.fields.event_class')),
                        TextEntry::make('status')->label(__('filament-signal::signal.fields.status')),
                        TextEntry::make('message')->label(__('filament-signal::signal.fields.status_message')),
                        TextEntry::make('executed_at')->dateTime()->label(__('filament-signal::signal.fields.executed_at')),
                    ])->columns(2),
                InfoSection::make(__('filament-signal::signal.sections.payload'))
                    ->schema([
                        ViewEntry::make('payload')
                            ->view('filament-signal::infolists.json-preview', [
                                'title' => __('filament-signal::signal.fields.payload_preview'),
                            ]),
                    ]),
                InfoSection::make(__('filament-signal::signal.sections.response'))
                    ->schema([
                        ViewEntry::make('response')
                            ->view('filament-signal::infolists.json-preview', [
                                'title' => __('filament-signal::signal.fields.response_preview'),
                            ]),
                    ]),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSignalActionLogs::route('/'),
        ];
    }
}
