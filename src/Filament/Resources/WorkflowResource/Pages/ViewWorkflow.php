<?php

namespace Voodflow\Voodflow\Filament\Resources\WorkflowResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Voodflow\Voodflow\Filament\Resources\WorkflowResource;

class ViewWorkflow extends ViewRecord
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }
}
