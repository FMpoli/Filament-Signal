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

    /**
     * Get available nodes for the frontend
     */
    public function getAvailableNodesProperty(): array
    {
        return \Base33\FilamentSignal\Nodes\NodeRegistry::getMetadataMap();
    }

    /**
     * Generic create node method
     */
    public function getFilterFieldsMapProperty(): array
    {
        $map = [];
        $events = \Base33\FilamentSignal\Filament\Resources\SignalTriggerResource::getEventClassOptions();

        foreach (array_keys($events) as $eventClass) {
            $map[$eventClass] = \Base33\FilamentSignal\Filament\Resources\SignalTriggerResource::getFilterFieldOptionsWithTypesForEvent($eventClass);
        }

        return $map;
    }

    public function createGenericNode(array $data): void
    {
        $type = $data['type'] ?? null;
        $class = \Base33\FilamentSignal\Nodes\NodeRegistry::get($type);

        if (! $class) {
            return;
        }

        $sourceNodeId = $data['sourceNodeId'] ?? null;
        $sourceHandle = $data['sourceHandle'] ?? null;

        $nodeId = $type . '-' . \Illuminate\Support\Str::uuid();
        $nodeId = $type . '-' . \Illuminate\Support\Str::uuid();
        $position = $data['position'] ?? $this->calculateNewNodePosition($sourceNodeId, $sourceHandle);

        $defaultConfig = $class::defaultConfig();
        // Merge provided config if any
        $config = array_merge($defaultConfig, $data['config'] ?? []);
        // Set isNew flag
        $config['isNew'] = true;

        // Add special options if needed
        if ($type === 'trigger' || $class::type() === 'trigger') {
            $config['eventOptions'] = \Base33\FilamentSignal\Filament\Resources\SignalTriggerResource::getEventClassOptions();
        }

        $this->record->nodes()->create([
            'node_id' => $nodeId,
            'type' => $class::type(),
            'name' => $config['label'] ?? $class::name(),
            'config' => $config,
            'position' => $position,
        ]);

        $this->dispatch('node-added', [
            'id' => $nodeId,
            'type' => $class::type(),
            'position' => $position,
            'data' => array_merge($config, ['livewireId' => $this->getId()]),
        ]);

        if ($sourceNodeId) {
            $this->createEdge($sourceNodeId, $nodeId, $sourceHandle);
        }
    }

    public function saveFlowData(array $data): void
    {
        // Save flow data to nodes and edges
        $this->record->nodes()->delete();
        $this->record->edges()->delete();

        // Create nodes from flow data
        foreach ($data['nodes'] ?? [] as $nodeData) {
            $config = $nodeData['data'] ?? [];

            // Remove transient data that shouldn't be saved to DB
            unset($config['eventOptions'], $config['livewireId'], $config['filterFieldsMap'], $config['availableNodes']);

            $this->record->nodes()->create([
                'node_id' => $nodeData['id'],
                'type' => $nodeData['type'],
                'name' => $config['label'] ?? null,
                'config' => $config,
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

        // Save viewport to metadata
        if (isset($data['viewport'])) {
            $this->record->update([
                'metadata' => [
                    'viewport' => $data['viewport'],
                ],
            ]);
        }
    }

    public function deleteTrigger(?string $nodeId = null): void
    {
        // If nodeId is provided, delete only that node (and connected edges)
        if ($nodeId) {
            $this->deleteNode($nodeId);

            return;
        }

        // Legacy behavior: Delete all trigger nodes and everything else
        // Get all node IDs before deleting (for frontend notification)
        $nodeIds = $this->record->nodes()->pluck('node_id')->toArray();

        // Delete all trigger nodes (there should only be one)
        $this->record->nodes()->where('type', 'trigger')->delete();

        // Also delete all related edges
        $this->record->edges()->delete();

        // Also delete all other nodes (actions, filters)
        $this->record->nodes()->delete();

        // Notify frontend about each removed node
        foreach ($nodeIds as $nodeId) {
            $this->dispatch('node-removed', $nodeId);
        }
    }

    public function deleteFilters(): void
    {
        // Get filter node IDs before deleting
        $filterNodeIds = $this->record->nodes()->where('type', 'filter')->pluck('node_id')->toArray();

        // Delete all filter nodes
        $this->record->nodes()->where('type', 'filter')->delete();

        // Notify frontend about each removed node
        foreach ($filterNodeIds as $nodeId) {
            $this->dispatch('node-removed', $nodeId);
        }
    }

    public function deleteFilter(string $nodeId): void
    {
        // Delete specific filter node by node_id
        $this->record->nodes()->where('node_id', $nodeId)->delete();

        // Also delete edges connected to this filter
        $this->record->edges()
            ->where('source_node_id', $nodeId)
            ->orWhere('target_node_id', $nodeId)
            ->delete();

        // Notify frontend
        $this->dispatch('node-removed', $nodeId);
    }

    public function deleteAction(string $nodeId): void
    {
        // Delete specific action node by node_id
        $this->record->nodes()->where('node_id', $nodeId)->delete();

        // Notify frontend
        $this->dispatch('node-removed', $nodeId);
    }

    // Generic delete for custom nodes (like SendWebhook)
    public function deleteNode(string $nodeId): void
    {
        // Delete specific node by node_id
        $this->record->nodes()->where('node_id', $nodeId)->delete();

        // Also delete edges connected to this node
        $this->record->edges()
            ->where('source_node_id', $nodeId)
            ->orWhere('target_node_id', $nodeId)
            ->delete();

        // Notify frontend
        $this->dispatch('node-removed', $nodeId);
    }

    public function updateTriggerConfig(array $data): void
    {
        // Wrapper for BC or specific logic, but delegates to generic if possible
        // Or keep separate. Let's redirect to generic one as TriggerNode now calls generic
        $this->updateNodeConfig($data);
    }

    /**
     * Generic method to update any node config
     */
    public function updateNodeConfig(array $data): void
    {
        $nodeId = $data['nodeId'] ?? null;
        if (! $nodeId) {
            return;
        }

        $node = $this->record->nodes()->where('node_id', $nodeId)->first();

        if ($node) {
            $config = $node->config ?? [];

            // Merge all provided data into config, excluding nodeId
            foreach ($data as $key => $value) {
                if ($key !== 'nodeId') {
                    $config[$key] = $value;
                }
            }

            // Ensure transient data is not saved
            unset($config['eventOptions'], $config['livewireId'], $config['availableNodes'], $config['filterFieldsMap']);

            $node->update([
                'name' => $config['label'] ?? $node->name,
                'config' => $config,
            ]);
        }
    }

    public function updateFilterConfig(array $data): void
    {
        $nodeId = $data['nodeId'] ?? null;
        if (! $nodeId) {
            return;
        }

        $node = $this->record->nodes()->where('node_id', $nodeId)->first();

        if ($node) {
            $config = $node->config ?? [];
            $config['filters'] = $data['filters'] ?? [];
            $config['matchType'] = $data['matchType'] ?? 'all';
            $config['label'] = $data['label'] ?? ($config['label'] ?? 'Filter');
            $config['description'] = $data['description'] ?? ($config['description'] ?? '');

            // Ensure transient data is not saved
            unset($config['availableFields'], $config['livewireId'], $config['filterFieldsMap']);

            $node->update([
                'name' => $config['label'],
                'config' => $config,
            ]);
        }
    }

    // Store current editing node
    public ?string $editingNodeId = null;

    // Helper to calculate position based on source node and handle
    private function calculateNewNodePosition(?string $sourceNodeId, ?string $sourceHandle = null): array
    {
        if (! $sourceNodeId) {
            return ['x' => 400, 'y' => 100]; // Default fallback
        }

        $sourceNode = $this->record->nodes()->where('node_id', $sourceNodeId)->first();
        if (! $sourceNode || empty($sourceNode->position)) {
            return ['x' => 400, 'y' => 100];
        }

        $sourcePos = $sourceNode->position;
        $offsetX = 400;
        $offsetY = 0;

        // If branching from an 'error' handle, shift down to branch out visually
        if ($sourceHandle === 'error') {
            $offsetY = 150;
        }

        return [
            'x' => ($sourcePos['x'] ?? 0) + $offsetX,
            'y' => ($sourcePos['y'] ?? 0) + $offsetY,
        ];
    }

    // Helper to create edge
    private function createEdge(string $sourceNodeId, string $targetNodeId, ?string $sourceHandle = null): void
    {
        $edgeId = 'e' . $sourceNodeId . ($sourceHandle ? '-' . $sourceHandle : '') . '-' . $targetNodeId;

        $this->record->edges()->create([
            'edge_id' => $edgeId,
            'source_node_id' => $sourceNodeId,
            'target_node_id' => $targetNodeId,
            'source_handle' => $sourceHandle,
        ]);

        // Notify frontend to add edge
        $this->dispatch('edge-added', [
            'id' => $edgeId,
            'source' => $sourceNodeId,
            'target' => $targetNodeId,
            'sourceHandle' => $sourceHandle,
        ]);
    }

    // Public method for JavaScript to call when creating new filter
    public function createNewFilter(array $data = []): void
    {
        $sourceNodeId = $data['sourceNodeId'] ?? null;
        $sourceHandle = $data['sourceHandle'] ?? null;
        $filterId = 'filter-' . \Illuminate\Support\Str::uuid();

        // Calculate position - offset each new filter by 150px vertically if no position provided
        $existingFilters = $this->record->nodes()->where('type', 'filter')->count();
        $defaultPos = ['x' => 400, 'y' => 100 + ($existingFilters * 180)];
        $position = $data['position'] ?? $defaultPos;

        $config = [
            'matchType' => 'all',
            'filters' => [],
            'label' => 'Filter',
            'description' => '',
            'isNew' => true,
        ];

        $this->record->nodes()->create([
            'node_id' => $filterId,
            'type' => 'filter',
            'name' => $config['label'],
            'config' => $config,
            'position' => $position,
        ]);

        $this->dispatch('node-added', [
            'id' => $filterId,
            'type' => 'filter',
            'position' => $position,
            'data' => array_merge($config, ['livewireId' => $this->getId()]),
        ]);

        if ($sourceNodeId) {
            $this->createEdge($sourceNodeId, $filterId, $sourceHandle);
        }
    }

    // Public method for JavaScript to call when creating new Trigger
    public function createNewTrigger(): void
    {
        $triggerId = 'trigger-' . Str::uuid();

        // Default config for new trigger
        $config = [
            'label' => 'New Trigger',
            'description' => '',
            'eventClass' => null,
            'status' => 'draft',
            'isNew' => true,
            'eventOptions' => \Base33\FilamentSignal\Filament\Resources\SignalTriggerResource::getEventClassOptions(),
        ];

        $this->record->nodes()->create([
            'node_id' => $triggerId,
            'type' => 'trigger',
            'name' => $config['label'],
            'config' => $config,
            'config' => $config,
            'position' => $data['position'] ?? ['x' => 100, 'y' => 200],
        ]);

        $this->dispatch('node-added', [
            'id' => $triggerId,
            'type' => 'trigger',
            'position' => $data['position'] ?? ['x' => 100, 'y' => 200],
            'data' => array_merge($config, ['livewireId' => $this->getId()]),
        ]);
    }

    // Public method for JavaScript to call when creating new Action node (generic)
    public function createActionNode(array $data = []): void
    {
        $sourceNodeId = $data['sourceNodeId'] ?? null;
        $sourceHandle = $data['sourceHandle'] ?? null;
        $actionType = $data['actionType'] ?? 'log';

        $nodeId = 'action-' . Str::uuid();
        $nodeId = 'action-' . Str::uuid();
        $position = $data['position'] ?? $this->calculateNewNodePosition($sourceNodeId, $sourceHandle);

        $config = [
            'label' => ucfirst($actionType) . ' Action',
            'actionType' => $actionType,
            'description' => '',
            'isNew' => true,
        ];

        $this->record->nodes()->create([
            'node_id' => $nodeId,
            'type' => 'action',
            'name' => $config['label'],
            'config' => $config,
            'position' => $position,
        ]);

        $this->dispatch('node-added', [
            'id' => $nodeId,
            'type' => 'action',
            'position' => $position,
            'data' => array_merge($config, ['livewireId' => $this->getId()]),
        ]);

        if ($sourceNodeId) {
            $this->createEdge($sourceNodeId, $nodeId, $sourceHandle);
        }
    }

    // Public method for JavaScript to call when creating new SendWebhook node
    public function createSendWebhookNode(array $data = []): void
    {
        $sourceNodeId = $data['sourceNodeId'] ?? null;
        $sourceHandle = $data['sourceHandle'] ?? null;
        $nodeId = 'sendwebhook-' . \Illuminate\Support\Str::uuid();

        $nodeId = 'sendwebhook-' . \Illuminate\Support\Str::uuid();

        $position = $data['position'] ?? $this->calculateNewNodePosition($sourceNodeId, $sourceHandle);

        $config = [
            'label' => 'Send Webhook',
            'description' => '',
            'isNew' => true,
        ];

        $this->record->nodes()->create([
            'node_id' => $nodeId,
            'type' => 'sendWebhook',
            'name' => $config['label'],
            'config' => $config,
            'position' => $position,
        ]);

        $this->dispatch('node-added', [
            'id' => $nodeId,
            'type' => 'sendWebhook',
            'position' => $position,
            'data' => array_merge($config, ['livewireId' => $this->getId()]),
        ]);

        if ($sourceNodeId) {
            $this->createEdge($sourceNodeId, $nodeId, $sourceHandle);
        }
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
