<?php

namespace Voodflow\Voodflow\Filament\Resources\SignalWorkflowResource\Pages;

use Voodflow\Voodflow\Filament\Resources\SignalWorkflowResource;
use Filament\Resources\Pages\ViewRecord;

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
