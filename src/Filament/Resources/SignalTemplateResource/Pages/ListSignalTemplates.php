<?php

namespace Voodflow\Voodflow\Filament\Resources\TemplateResource\Pages;

use Voodflow\Voodflow\Filament\Resources\TemplateResource;
use Filament\Resources\Pages\ListRecords;

class ListSignalTemplates extends ListRecords
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->slideOver(),
        ];
    }
}
