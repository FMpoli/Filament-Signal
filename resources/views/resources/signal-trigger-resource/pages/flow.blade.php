<x-filament-panels::page>
    @php
        $record = $this->record;
        $flowData = $record->metadata['flow'] ?? [];
        $savedNodes = collect($flowData['nodes'] ?? []);
        $savedViewport = $flowData['viewport'] ?? ['x' => 0, 'y' => 0, 'zoom' => 1];

        $triggerNodeSaved = $savedNodes->firstWhere('type', 'trigger');
        $triggerPos = $triggerNodeSaved['position'] ?? ['x' => 100, 'y' => 100];

        $filterNodeSaved = $savedNodes->firstWhere('type', 'filter');
        $filterPos = $filterNodeSaved['position'] ?? ['x' => 400, 'y' => 100];

        $initialNodes = [];
        $initialEdges = [];

            // Nodo Trigger
            $initialNodes[] = [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'position' => $triggerPos,
                'data' => [
                    'label' => $record->name ?? 'Trigger',
                    'eventClass' => $record->event_class ?? '',
                    'status' => $record->status ?? 'draft',
                    'description' => $record->description ?? '',
                    'triggerId' => $record->id,
                ],
            ];

            // Nodi Filter
            if (!empty($record->filters)) {
                $initialNodes[] = [
                    'id' => 'filter-1',
                    'type' => 'filter',
                    'position' => $filterPos,
                    'data' => [
                        'label' => 'Filters',
                        'filterCount' => is_array($record->filters) ? count($record->filters) : 0,
                        'filters' => $record->filters,
                        'matchType' => $record->match_type ?? 'all',
                    ],
                ];
                $initialEdges[] = [
                    'id' => 'e1-2',
                    'source' => 'trigger-1',
                    'target' => 'filter-1',
                ];
            }

            // Nodi Action
            $sourceNodeId = !empty($record->filters) ? 'filter-1' : 'trigger-1';
            foreach ($record->actions ?? [] as $action) {
                // Use stored metadata position or default
                $actionPos = $action->metadata['position'] ?? ['x' => 700, 'y' => 200]; 
                
                $nodeId = 'action-' . $action->id;
                
                $initialNodes[] = [
                    'id' => $nodeId,
                    'type' => 'action',
                    'position' => $actionPos,
                    'data' => [
                        'label' => $action->name ?? 'Action',
                        'actionType' => $action->action_type ?? 'log',
                        'actionId' => $action->id,
                    ],
                ];
                $initialEdges[] = [
                    'id' => 'e-' . $action->id,
                    'source' => $sourceNodeId,
                    'target' => $nodeId,
                ];
            }

        $nodesJson = json_encode($initialNodes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        $edgesJson = json_encode($initialEdges, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        $viewportJson = json_encode($savedViewport, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    @endphp

    <div class="fi-section-content-ctn rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
         x-data
         x-init="
             $nextTick(() => {
                 if (window.mountSignalFlowEditor) {
                     window.mountSignalFlowEditor(document.getElementById('react-flow-container'));
                 } else {
                     // Check if script is loaded, retry once
                     setTimeout(() => {
                         if (window.mountSignalFlowEditor) {
                              window.mountSignalFlowEditor(document.getElementById('react-flow-container'));
                         }
                     }, 500);
                 }
             })
         ">
        <div id="react-flow-container"
             style="width: 100%; height: calc(100vh - 300px); min-height: 600px;"
             data-nodes='{!! $nodesJson !!}'
             data-edges='{!! $edgesJson !!}'
             data-viewport='{!! $viewportJson !!}'
             data-livewire-id="{{ $this->getId() }}"
             wire:ignore></div>
    </div>

</x-filament-panels::page>
