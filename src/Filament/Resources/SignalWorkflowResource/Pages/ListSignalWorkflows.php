<?php

namespace Base33\FilamentSignal\Filament\Resources\SignalWorkflowResource\Pages;

use Base33\FilamentSignal\Filament\Resources\SignalWorkflowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSignalWorkflows extends ListRecords
{
    protected static string $resource = SignalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
