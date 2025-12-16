<?php

namespace Voodflow\Voodflow\Filament\Resources\TriggerResource\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Builder;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Voodflow\Voodflow\Filament\Resources\TriggerResource;

class FlowTrigger extends EditRecord
{
    protected static string $resource = TriggerResource::class;

    protected string $view = 'voodflow::resources.trigger-resource.pages.flow';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function getView(): string
    {
        return 'voodflow::resources.trigger-resource.pages.flow';
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getHeading(): string
    {
        return $this->record->name ?? __('voodflow::signal.actions.flow_view');
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->editTriggerAction()->extraAttributes(['style' => 'display: none !important']),
            $this->editFiltersAction()->extraAttributes(['style' => 'display: none !important']),
            $this->createActionAction()->extraAttributes(['style' => 'display: none !important']),
            $this->editActionAction()->extraAttributes(['style' => 'display: none !important']),
        ];
    }

    public function editTriggerAction(): Action
    {
        return Action::make('editTrigger')
            ->label(__('voodflow::signal.actions.edit_trigger'))
            ->record($this->record)
            ->fillForm($this->record->toArray())
            ->slideOver()
            ->form([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\Select::make('event_class')
                    ->options(TriggerResource::getEventClassOptions())
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'disabled' => 'Disabled',
                    ])->required(),
                Forms\Components\Textarea::make('description'),
            ])
            ->action(function (array $data) {
                $this->record->update($data);
                $this->redirect(static::getResource()::getUrl('flow', ['record' => $this->record]));
            });
    }

    public function editFiltersAction(): Action
    {
        return Action::make('editFilters')
            ->label(__('voodflow::signal.actions.edit_filters'))
            ->record($this->record)
            ->fillForm($this->record->toArray())
            ->slideOver()
            ->form([
                Forms\Components\Select::make('match_type')
                    ->options([
                        'all' => 'All',
                        'any' => 'Any',
                    ])->default('all'),
                Builder::make('filters')
                    ->blocks(TriggerResource::getFilterBlocks())
                    ->collapsible(),
            ])
            ->action(function (array $data) {
                $this->record->update($data);
                $this->redirect(static::getResource()::getUrl('flow', ['record' => $this->record]));
            });
    }

    public function createActionAction(): Action
    {
        return Action::make('createAction')
            ->label('Add Action')
            ->model(\Voodflow\Voodflow\Models\SignalAction::class)
            ->slideOver()
            ->form(function () {
                return [
                    Forms\Components\Hidden::make('event_class')
                        ->default($this->record->event_class),
                    ...TriggerResource::actionRepeaterSchema(),
                ];
            })
            ->action(function (array $data) {
                // configuration is inside data, but repeater schema returns a Grid.
                // The structure of data depends on the schema.
                // ActionRepeaterSchema returns components that write to: name, execution_order, action_type, configuration.
                $action = new \Voodflow\Voodflow\Models\SignalAction($data);
                $action->trigger_id = $this->record->id;
                $action->save();
                $this->redirect(static::getResource()::getUrl('flow', ['record' => $this->record]));
            });
    }

    public function editActionAction(): Action
    {
        return Action::make('editAction')
            ->label('Edit Action')
            ->slideOver()
            ->mountUsing(function ($form, array $arguments) {
                $action = \Voodflow\Voodflow\Models\SignalAction::find($arguments['actionId'] ?? null);
                if ($action) {
                    $data = $action->toArray();
                    $data['event_class'] = $this->record->event_class;
                    $form->fill($data);
                }
            })
            ->form(function () {
                return [
                    Forms\Components\Hidden::make('event_class')
                        ->default($this->record->event_class),
                    ...TriggerResource::actionRepeaterSchema(),
                ];
            })
            ->action(function (array $data, array $arguments) {
                $action = \Voodflow\Voodflow\Models\SignalAction::find($arguments['actionId'] ?? null);
                if ($action) {
                    $action->update($data);
                }
                $this->dispatch('flow-refresh');
            });
    }

    public function saveFlowData(array $flowData): void
    {
        $nodes = $flowData['nodes'] ?? [];

        // Update Action positions in their specific table
        foreach ($nodes as $node) {
            if (($node['type'] ?? '') === 'action') {
                $actionId = $node['data']['actionId'] ?? null;
                if ($actionId) {
                    $action = \Voodflow\Voodflow\Models\SignalAction::find($actionId);
                    if ($action) {
                        $meta = $action->metadata ?? [];
                        $meta['position'] = $node['position'] ?? ['x' => 0, 'y' => 0];
                        $action->metadata = $meta;
                        $action->save();
                    }
                }
            }
        }

        $metadata = $this->record->metadata ?? [];
        $metadata['flow'] = $flowData;

        $this->record->update([
            'metadata' => $metadata,
        ]);
    }

    public function deleteAction(int $actionId): void
    {
        $action = \Voodflow\Voodflow\Models\SignalAction::find($actionId);
        if ($action) {
            $action->delete();
            $this->dispatch('flow-refresh');
        }
    }

    public function deleteFilters(): void
    {
        $this->record->update([
            'filters' => null,
            'match_type' => 'all',
        ]);
        $this->dispatch('flow-refresh');
    }
}
