<?php

namespace Voodflow\Voodflow\Filament\Resources\SignalModelIntegrationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Voodflow\Voodflow\Filament\Resources\SignalModelIntegrationResource;

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
