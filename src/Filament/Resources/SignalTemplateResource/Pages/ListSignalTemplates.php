<?php

namespace Voodflow\Voodflow\Filament\Resources\SignalTemplateResource\Pages;

use Voodflow\Voodflow\Filament\Resources\SignalTemplateResource;
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
