<?php

namespace Voodflow\Voodflow\Filament\Resources\WorkflowResource\Pages;

use Voodflow\Voodflow\Filament\Resources\WorkflowResource;
use Filament\Resources\Pages\ViewRecord;

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
