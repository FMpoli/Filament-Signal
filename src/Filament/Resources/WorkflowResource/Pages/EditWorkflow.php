<?php

namespace Voodflow\Voodflow\Filament\Resources\WorkflowResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Voodflow\Voodflow\Filament\Resources\WorkflowResource;

class EditWorkflow extends EditRecord
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
