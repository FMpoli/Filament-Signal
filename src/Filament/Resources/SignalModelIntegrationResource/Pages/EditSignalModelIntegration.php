<?php

namespace Voodflow\Voodflow\Filament\Resources\SignalModelIntegrationResource\Pages;

use Voodflow\Voodflow\Filament\Resources\SignalModelIntegrationResource;
use Filament\Resources\Pages\EditRecord;

class EditSignalModelIntegration extends EditRecord
{
    protected static string $resource = SignalModelIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
