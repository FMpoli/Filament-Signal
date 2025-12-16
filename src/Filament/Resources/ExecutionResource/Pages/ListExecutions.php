<?php

namespace Voodflow\Voodflow\Filament\Resources\ExecutionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Voodflow\Voodflow\Filament\Resources\ExecutionResource;

class ListExecutions extends ListRecords
{
    protected static string $resource = ExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
