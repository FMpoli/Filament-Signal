<?php

namespace Voodflow\Voodflow\Filament\Resources\SignalWorkflowResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Voodflow\Voodflow\Filament\Resources\SignalWorkflowResource;

class ViewSignalWorkflow extends ViewRecord
{
    protected static string $resource = SignalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }
}
