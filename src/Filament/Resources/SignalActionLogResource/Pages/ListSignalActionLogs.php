<?php

namespace Base33\FilamentSignal\Filament\Resources\SignalActionLogResource\Pages;

use Base33\FilamentSignal\Filament\Resources\SignalActionLogResource;
use Filament\Resources\Pages\ListRecords;

class ListSignalActionLogs extends ListRecords
{
    protected static string $resource = SignalActionLogResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            url()->current() => __('filament-signal::signal.plugin.navigation.logs'),
        ];
    }
}
