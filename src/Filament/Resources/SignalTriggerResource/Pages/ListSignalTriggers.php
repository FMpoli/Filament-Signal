<?php

namespace Base33\FilamentSignal\Filament\Resources\SignalTriggerResource\Pages;

use Base33\FilamentSignal\Filament\Resources\SignalTriggerResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListSignalTriggers extends ListRecords
{
    protected static string $resource = SignalTriggerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('filament-signal::signal.actions.create'))
                ->url(static::getResource()::getUrl('create'))
                ->icon('heroicon-o-plus')
                ->button(),
        ];
    }
}
