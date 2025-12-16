<?php

namespace Voodflow\Voodflow\Filament\Resources\ModelIntegrationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Voodflow\Voodflow\Filament\Resources\ModelIntegrationResource;

class EditModelIntegration extends EditRecord
{
    protected static string $resource = ModelIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
