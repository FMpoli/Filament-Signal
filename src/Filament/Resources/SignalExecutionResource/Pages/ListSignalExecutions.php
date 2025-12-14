<?php

namespace Voodflow\Voodflow\Filament\Resources\ExecutionResource\Pages;

use Voodflow\Voodflow\Filament\Resources\ExecutionResource;
use Filament\Resources\Pages\ListRecords;

class ListExecutions extends ListRecords
{
    protected static string $resource = ExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
