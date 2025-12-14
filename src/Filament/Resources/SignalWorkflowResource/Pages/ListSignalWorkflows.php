<?php

namespace Voodflow\Voodflow\Filament\Resources\WorkflowResource\Pages;

use Voodflow\Voodflow\Filament\Resources\WorkflowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSignalWorkflows extends ListRecords
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
