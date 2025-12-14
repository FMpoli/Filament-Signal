<?php

namespace Voodflow\Voodflow\Filament\Resources\SignalTriggerResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Voodflow\Voodflow\Filament\Resources\SignalTriggerResource;

class ListSignalTriggers extends ListRecords
{
    protected static string $resource = SignalTriggerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createWithFlow')
                ->label('Create with Flow')
                ->icon('heroicon-o-squares-2x2')
                ->action(function () {
                    $trigger = new \Voodflow\Voodflow\Models\SignalTrigger;
                    $trigger->name = 'New Automation Rule';
                    $trigger->event_class = 'TBD';
                    $trigger->status = \Voodflow\Voodflow\Models\SignalTrigger::STATUS_DRAFT;
                    $trigger->save();

                    return redirect()->to(static::getResource()::getUrl('flow', ['record' => $trigger]));
                }),
            Action::make('create')
                ->label(__('filament-signal::signal.actions.create'))
                ->url(static::getResource()::getUrl('create'))
                ->icon('heroicon-o-plus')
                ->button(),
        ];
    }
}
