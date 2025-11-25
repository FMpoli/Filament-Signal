<?php

namespace Base33\FilamentSignal\Filament\Resources\SignalTemplateResource\Pages;

use Base33\FilamentSignal\Filament\Resources\SignalTemplateResource;
use Filament\Resources\Pages\ListRecords;

class ListSignalTemplates extends ListRecords
{
    protected static string $resource = SignalTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->slideOver(),
        ];
    }
}

