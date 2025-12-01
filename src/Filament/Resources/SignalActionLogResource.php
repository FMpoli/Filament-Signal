<?php

namespace Base33\FilamentSignal\Filament\Resources;

use BackedEnum;
use Base33\FilamentSignal\Filament\Resources\SignalActionLogResource\Pages;
use Base33\FilamentSignal\Models\SignalActionLog;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Tables;
use Filament\Tables\Table;

class SignalActionLogResource extends Resource
{
    protected static ?string $model = SignalActionLog::class;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-clipboard-document-list';

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
                    ->modalHeading(__('filament-signal::signal.actions.view_log'))
                    ->modalWidth('3xl')
                    ->form(static::logFormSchema()),
            ])
            ->defaultSort('executed_at', 'desc');
    }

    protected static function logFormSchema(): array
    {
        return [
            SchemaSection::make(__('filament-signal::signal.sections.log_details'))
                ->schema([
                    Forms\Components\Placeholder::make('trigger_name')
                        ->label(__('filament-signal::signal.fields.name'))
                        ->content(fn (SignalActionLog $record): ?string => $record->trigger?->name),
                    Forms\Components\Placeholder::make('action_name')
                        ->label(__('filament-signal::signal.fields.actions'))
                        ->content(fn (SignalActionLog $record): ?string => $record->action?->name),
                    Forms\Components\Placeholder::make('event_class')
                        ->label(__('filament-signal::signal.fields.event_class'))
                        ->content(fn (SignalActionLog $record): string => $record->event_class),
                    Forms\Components\Placeholder::make('status')
                        ->label(__('filament-signal::signal.fields.status'))
                        ->content(fn (SignalActionLog $record): string => ucfirst($record->status)),
                    Forms\Components\Placeholder::make('message')
                        ->label(__('filament-signal::signal.fields.status_message'))
                        ->content(fn (SignalActionLog $record): ?string => $record->message),
                    Forms\Components\Placeholder::make('executed_at')
                        ->label(__('filament-signal::signal.fields.executed_at'))
                        ->content(fn (SignalActionLog $record): ?string => optional($record->executed_at)->toDateTimeString()),
                ])
                ->columns(2),
            SchemaSection::make(__('filament-signal::signal.sections.payload'))
                ->schema([
                    Forms\Components\Placeholder::make('payload')
                        ->label(__('filament-signal::signal.fields.payload_preview'))
                        ->content(function (SignalActionLog $record) {
                            $payload = $record->payload;
                            if (blank($payload)) {
                                return '—';
                            }

                            // Se è già una stringa JSON, decodificala prima
                            if (is_string($payload)) {
                                $decoded = json_decode($payload, true);
                                $payload = $decoded !== null ? $decoded : $payload;
                            }

                            // IMPORTANTE: Il payload salvato è già quello configurato e inviato al webhook
                            // Non riconfigurarlo, altrimenti si perdono le relazioni inverse già presenti
                            // Mostra direttamente il payload salvato

                            // Formatta come JSON leggibile
                            $formatted = is_array($payload) || is_object($payload)
                                ? json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                                : $payload;

                            return new \Illuminate\Support\HtmlString(
                                '<pre class="max-h-64 overflow-auto rounded-lg bg-gray-950/5 p-3 text-xs font-mono text-gray-900 dark:bg-white/5 dark:text-gray-100 whitespace-pre-wrap">' .
                                htmlspecialchars($formatted, ENT_QUOTES, 'UTF-8') .
                                '</pre>'
                            );
                        })
                        ->columnSpanFull(),
                ]),
            SchemaSection::make(__('filament-signal::signal.sections.response'))
                ->schema([
                    Forms\Components\Placeholder::make('response')
                        ->label(__('filament-signal::signal.fields.response_preview'))
                        ->content(function (SignalActionLog $record) {
                            $response = $record->response;
                            if (blank($response)) {
                                return '—';
                            }

                            // Se è già una stringa JSON, decodificala prima
                            if (is_string($response)) {
                                $decoded = json_decode($response, true);
                                $response = $decoded !== null ? $decoded : $response;
                            }

                            // Formatta come JSON leggibile
                            $formatted = is_array($response) || is_object($response)
                                ? json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                                : $response;

                            return new \Illuminate\Support\HtmlString(
                                '<pre class="max-h-64 overflow-auto rounded-lg bg-gray-950/5 p-3 text-xs font-mono text-gray-900 dark:bg-white/5 dark:text-gray-100 whitespace-pre-wrap">' .
                                htmlspecialchars($formatted, ENT_QUOTES, 'UTF-8') .
                                '</pre>'
                            );
                        })
                        ->columnSpanFull(),
                ]),
        ];
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
