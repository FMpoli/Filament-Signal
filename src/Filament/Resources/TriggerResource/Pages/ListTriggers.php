<?php

namespace Voodflow\Voodflow\Filament\Resources\TriggerResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Voodflow\Voodflow\Filament\Resources\TriggerResource;

class ListTriggers extends ListRecords
{
    protected static string $resource = TriggerResource::class;

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
                ->label(__('voodflow::signal.actions.create'))
                ->url(static::getResource()::getUrl('create'))
                ->icon('heroicon-o-plus')
                ->button(),
        ];
    }
}
