<?php

namespace Voodflow\Voodflow\Filament\Resources\ModelIntegrationResource\Pages;

use Voodflow\Voodflow\Filament\Resources\ModelIntegrationResource;
use Filament\Resources\Pages\EditRecord;

class EditSignalModelIntegration extends EditRecord
{
    protected static string $resource = ModelIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
