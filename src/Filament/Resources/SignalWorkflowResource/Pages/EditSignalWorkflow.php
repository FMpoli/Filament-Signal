<?php

namespace Voodflow\Voodflow\Filament\Resources\SignalWorkflowResource\Pages;

use Voodflow\Voodflow\Filament\Resources\SignalWorkflowResource;
use Filament\Resources\Pages\EditRecord;

class EditSignalWorkflow extends EditRecord
{
    protected static string $resource = SignalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
