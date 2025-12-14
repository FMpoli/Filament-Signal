<?php

namespace Voodflow\Voodflow\Filament\Resources\SignalModelIntegrationResource\Pages;

use Voodflow\Voodflow\Filament\Resources\SignalModelIntegrationResource;
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
