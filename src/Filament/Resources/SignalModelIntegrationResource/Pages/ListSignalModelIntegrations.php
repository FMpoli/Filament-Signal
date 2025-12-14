<?php

namespace Voodflow\Voodflow\Filament\Resources\ModelIntegrationResource\Pages;

use Voodflow\Voodflow\Filament\Resources\ModelIntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSignalModelIntegrations extends ListRecords
{
    protected static string $resource = ModelIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->slideOver(),
        ];
    }
}
