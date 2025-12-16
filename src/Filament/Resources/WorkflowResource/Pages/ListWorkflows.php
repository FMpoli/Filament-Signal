<?php

namespace Voodflow\Voodflow\Filament\Resources\WorkflowResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Voodflow\Voodflow\Filament\Resources\WorkflowResource;

class ListWorkflows extends ListRecords
{
    protected static string $resource = WorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
