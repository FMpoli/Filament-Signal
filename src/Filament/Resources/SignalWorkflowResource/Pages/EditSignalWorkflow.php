<?php

namespace Voodflow\Voodflow\Filament\Resources\WorkflowResource\Pages;

use Voodflow\Voodflow\Filament\Resources\WorkflowResource;
use Filament\Resources\Pages\EditRecord;

class EditSignalWorkflow extends EditRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
