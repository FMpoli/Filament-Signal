<?php

namespace Voodflow\Voodflow\Filament\Resources\TriggerResource\Pages;

use Voodflow\Voodflow\Filament\Resources\TriggerResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListSignalTriggers extends ListRecords
{
    protected static string $resource = TriggerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createWithFlow')
                ->label('Create with Flow')
                ->icon('heroicon-o-squares-2x2')
                ->action(function () {
                    $trigger = new \Base33\FilamentSignal\Models\SignalTrigger;
                    $trigger->name = 'New Automation Rule';
                    $trigger->event_class = 'TBD';
                    $trigger->status = \Base33\FilamentSignal\Models\SignalTrigger::STATUS_DRAFT;
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
