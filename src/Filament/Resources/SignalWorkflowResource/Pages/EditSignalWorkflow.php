<?php

namespace Base33\FilamentSignal\Filament\Resources\SignalWorkflowResource\Pages;

use Base33\FilamentSignal\Filament\Resources\SignalWorkflowResource;
use Filament\Resources\Pages\EditRecord;

class EditSignalWorkflow extends EditRecord
{
    protected static string $resource = SignalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
