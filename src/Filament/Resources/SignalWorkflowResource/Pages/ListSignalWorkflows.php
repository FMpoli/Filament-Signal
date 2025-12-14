<?php

namespace Voodflow\Voodflow\Filament\Resources\SignalWorkflowResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Voodflow\Voodflow\Filament\Resources\SignalWorkflowResource;

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
