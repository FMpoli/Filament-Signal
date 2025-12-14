<?php

namespace Voodflow\Voodflow\Filament\Resources\SignalExecutionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Voodflow\Voodflow\Filament\Resources\SignalExecutionResource;

class ListSignalExecutions extends ListRecords
{
    protected static string $resource = SignalExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
