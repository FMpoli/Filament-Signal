<?php

namespace Base33\FilamentSignal\Filament\Resources\SignalTriggerResource\Pages;

use Base33\FilamentSignal\Filament\Resources\SignalTriggerResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class FlowSignalTrigger extends EditRecord
{
    protected static string $resource = SignalTriggerResource::class;

    public ?string $heading = null;

    public function getView(): string
    {
        return 'filament-signal::resources.signal-trigger-resource.pages.flow';
    }

    public function getTitle(): string
    {
        return $this->record->name ?? __('filament-signal::signal.fields.flow_view');
    }

    public function getHeading(): string
    {
        return $this->record->name ?? __('filament-signal::signal.fields.flow_view');
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label(__('filament-signal::signal.actions.edit'))
                ->url(fn () => static::getResource()::getUrl('edit', ['record' => $this->record]))
                ->icon('heroicon-o-pencil-square'),
            Action::make('view')
                ->label(__('filament-signal::signal.actions.view'))
                ->url(fn () => static::getResource()::getUrl('view', ['record' => $this->record]))
                ->icon('heroicon-o-eye'),
        ];
    }

    /**
     * Salva i dati del flow tramite Livewire
     */
    public function saveFlowData(array $flowData): void
    {
        $metadata = $this->record->metadata ?? [];
        $metadata['flow'] = $flowData;

        $this->record->update([
            'metadata' => $metadata,
        ]);
    }
}
