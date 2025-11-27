<?php

namespace Base33\FilamentSignal\Filament\Resources\SignalModelIntegrationResource\Pages;

use Base33\FilamentSignal\Filament\Resources\SignalModelIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSignalModelIntegrations extends ListRecords
{
    protected static string $resource = SignalModelIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->slideOver(),
        ];
    }
}


