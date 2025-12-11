<?php

namespace Base33\FilamentSignal\Filament\Resources\SignalWorkflowResource\Pages;

use Base33\FilamentSignal\Filament\Resources\SignalWorkflowResource;
use Base33\FilamentSignal\Models\SignalWorkflow;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Str;

class FlowSignalWorkflow extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $resource = SignalWorkflowResource::class;

    protected string $view = 'filament-signal::resources.signal-trigger-resource.pages.flow';

    public SignalWorkflow $record;

    public function mount(SignalWorkflow $record): void
    {
        $this->record = $record;
    }

    public function saveFlowData(array $data): void
    {
        // Save flow data to nodes and edges
        $this->record->nodes()->delete();
        $this->record->edges()->delete();

        // Create nodes from flow data
        foreach ($data['nodes'] ?? [] as $nodeData) {
            $this->record->nodes()->create([
                'node_id' => $nodeData['id'],
                'type' => $nodeData['type'],
                'name' => $nodeData['data']['label'] ?? null,
                'config' => $nodeData['data'] ?? [],
                'position' => $nodeData['position'] ?? ['x' => 0, 'y' => 0],
            ]);
        }

        // Create edges from flow data
        foreach ($data['edges'] ?? [] as $edgeData) {
            $this->record->edges()->create([
                'edge_id' => $edgeData['id'],
                'source_node_id' => $edgeData['source'],
                'target_node_id' => $edgeData['target'],
                'source_handle' => $edgeData['sourceHandle'] ?? null,
                'target_handle' => $edgeData['targetHandle'] ?? null,
            ]);
        }
    }

    public function deleteTrigger(): void
    {
        // Delete all trigger nodes (there should only be one)
        $this->record->nodes()->where('type', 'trigger')->delete();

        // Also delete all related edges
        $this->record->edges()->delete();

        // Also delete all other nodes (actions, filters)
        $this->record->nodes()->delete();

        // Refresh the page
        $this->dispatch('flow-refresh');
    }

    public function deleteFilters(): void
    {
        // Delete all filter nodes
        $this->record->nodes()->where('type', 'filter')->delete();

        // Refresh the page
        $this->dispatch('flow-refresh');
    }

    public function deleteAction(string $nodeId): void
    {
        // Delete specific action node by node_id
        $this->record->nodes()->where('node_id', $nodeId)->delete();

        // Refresh the page
        $this->dispatch('flow-refresh');
    }

    // Store current editing node
    public ?string $editingNodeId = null;

    // Public method for JavaScript to call when editing existing trigger
    public function editExistingTrigger(string $nodeId): void
    {
        $this->editingNodeId = $nodeId;
        $this->mountAction('editTrigger');
    }

    // Public method for JavaScript to call when creating new trigger
    public function createNewTrigger(): void
    {
        $this->editingNodeId = null;
        $this->mountAction('editTrigger');
    }

    // Define Actions
    public function editTriggerAction(): Action
    {
        return Action::make('editTrigger')
            ->modalHeading(fn () => $this->editingNodeId ? 'Edit Trigger' : 'Create Trigger')
            ->modalWidth('2xl')
            ->fillForm(function (): array {
                // If editing existing node, load its data
                if ($this->editingNodeId) {
                    $node = $this->record->nodes()->where('node_id', $this->editingNodeId)->first();
                    if ($node) {
                        $config = $node->config ?? [];

                        \Log::info('Loading trigger for editing', [
                            'node_id' => $node->node_id,
                            'config' => $config,
                        ]);

                        return [
                            'name' => $node->name ?? $this->record->name,
                            'description' => $config['description'] ?? '',
                            'event_class' => $config['eventClass'] ?? null,
                        ];
                    }
                }

                // Default values for new trigger
                return [
                    'name' => $this->record->name,
                    'description' => $this->record->description,
                    'event_class' => null,
                ];
            })
            ->form([
                TextInput::make('name')
                    ->label('Trigger Name')
                    ->required(),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3),
                Select::make('event_class')
                    ->label('Event Class')
                    ->options(\Base33\FilamentSignal\Filament\Resources\SignalTriggerResource::getEventClassOptions())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->helperText('Select an event from plugins (HasSignal) or Model Integrations')
                    ->getSearchResultsUsing(function (string $search): array {
                        $options = \Base33\FilamentSignal\Filament\Resources\SignalTriggerResource::getEventClassOptions();
                        $results = [];

                        foreach ($options as $class => $name) {
                            // Search in name or class
                            if (
                                stripos($name, $search) !== false ||
                                stripos($class, $search) !== false
                            ) {
                                $results[$class] = $name;
                            }
                        }

                        return $results;
                    })
                    ->getOptionLabelUsing(function (?string $value): ?string {
                        if (! $value) {
                            return null;
                        }

                        $options = \Base33\FilamentSignal\Filament\Resources\SignalTriggerResource::getEventClassOptions();

                        return $options[$value] ?? class_basename($value);
                    }),
            ])
            ->action(function (array $data): void {
                // Check if we're editing an existing node
                if ($this->editingNodeId) {
                    $node = $this->record->nodes()->where('node_id', $this->editingNodeId)->first();
                    if ($node) {
                        // Update existing node
                        $node->update([
                            'name' => $data['name'],
                            'config' => [
                                'label' => $data['name'],
                                'description' => $data['description'] ?? '',
                                'eventClass' => $data['event_class'],
                                'status' => $this->record->status,
                            ],
                        ]);
                    }
                } else {
                    // Create new trigger node
                    $triggerId = 'trigger-' . Str::uuid();

                    $this->record->nodes()->create([
                        'node_id' => $triggerId,
                        'type' => 'trigger',
                        'name' => $data['name'],
                        'config' => [
                            'label' => $data['name'],
                            'description' => $data['description'] ?? '',
                            'eventClass' => $data['event_class'],
                            'status' => $this->record->status,
                        ],
                        'position' => ['x' => 100, 'y' => 100],
                    ]);
                }

                // Clear editing state
                $this->editingNodeId = null;

                // Refresh the page
                $this->dispatch('flow-refresh');
            });
    }

    public function editFiltersAction(): Action
    {
        return Action::make('editFilters')
            ->modalHeading('Configure Filters')
            ->modalWidth('2xl')
            ->form([
                Select::make('match_type')
                    ->label('Match Type')
                    ->options([
                        'all' => 'All conditions must match (AND)',
                        'any' => 'Any condition must match (OR)',
                    ])
                    ->default('all')
                    ->required(),
                // TODO: Add repeater for conditions
            ])
            ->action(function (array $data): void {
                // Create filter node
                $filterId = 'filter-' . Str::uuid();

                $this->record->nodes()->create([
                    'node_id' => $filterId,
                    'type' => 'filter',
                    'name' => 'Filters',
                    'config' => [
                        'label' => 'Filters',
                        'matchType' => $data['match_type'],
                        'filters' => [],
                    ],
                    'position' => ['x' => 400, 'y' => 100],
                ]);

                // Refresh the page
                $this->dispatch('flow-refresh');
            });
    }

    public function createActionAction(): Action
    {
        return Action::make('createAction')
            ->modalHeading('Create Action')
            ->modalWidth('2xl')
            ->form([
                TextInput::make('name')
                    ->label('Action Name')
                    ->required(),
                Select::make('action_type')
                    ->label('Action Type')
                    ->options([
                        'webhook' => 'Webhook',
                        'log' => 'Log',
                        'email' => 'Email',
                    ])
                    ->required()
                    ->default('log'),
            ])
            ->action(function (array $data): void {
                // Create action node
                $actionId = 'action-' . Str::uuid();

                $this->record->nodes()->create([
                    'node_id' => $actionId,
                    'type' => 'action',
                    'name' => $data['name'],
                    'config' => [
                        'label' => $data['name'],
                        'actionType' => $data['action_type'],
                    ],
                    'position' => ['x' => 700, 'y' => 100],
                ]);

                // Refresh the page
                $this->dispatch('flow-refresh');
            });
    }
}
