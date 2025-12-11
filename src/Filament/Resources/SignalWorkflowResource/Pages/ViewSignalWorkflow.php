<?php

namespace Base33\FilamentSignal\Filament\Resources\SignalWorkflowResource\Pages;

use Base33\FilamentSignal\Filament\Resources\SignalWorkflowResource;
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
