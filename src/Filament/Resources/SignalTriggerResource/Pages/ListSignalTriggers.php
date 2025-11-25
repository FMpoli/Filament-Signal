<?php

namespace Base33\FilamentSignal\Filament\Resources\SignalTriggerResource\Pages;

use Base33\FilamentSignal\Filament\Resources\SignalTriggerResource;
use Filament\Resources\Pages\ListRecords;

class ListSignalTriggers extends ListRecords
{
    protected static string $resource = SignalTriggerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->slideOver(),
        ];
    }
}
