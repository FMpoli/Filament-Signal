<?php

namespace Voodflow\Voodflow\Filament\Resources\SignalExecutionResource\Pages;

use Voodflow\Voodflow\Filament\Resources\SignalExecutionResource;
use Filament\Resources\Pages\ListRecords;

class ListSignalExecutions extends ListRecords
{
    protected static string $resource = SignalExecutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
