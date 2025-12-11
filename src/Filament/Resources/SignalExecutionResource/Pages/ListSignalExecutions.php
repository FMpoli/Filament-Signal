<?php

namespace Base33\FilamentSignal\Filament\Resources\SignalExecutionResource\Pages;

use Base33\FilamentSignal\Filament\Resources\SignalExecutionResource;
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
