<x-filament-panels::page>
    @php
        $record = $this->record;

        // Load saved nodes and edges from database
        $savedNodes = $record->nodes;
        $savedEdges = $record->edges;

        $initialNodes = [];
        $initialEdges = [];

        // Rebuild nodes from database
        foreach ($savedNodes as $node) {
            $initialNodes[] = [
                'id' => $node->node_id,
                'type' => $node->type,
                'position' => $node->position ?? ['x' => 100, 'y' => 100],
                'data' => $node->config ?? [],
            ];
        }

        // Rebuild edges from database
        foreach ($savedEdges as $edge) {
            $initialEdges[] = [
                'id' => $edge->edge_id,
                'source' => $edge->source_node_id,
                'target' => $edge->target_node_id,
                'sourceHandle' => $edge->source_handle,
                'targetHandle' => $edge->target_handle,
            ];
        }

        // If no nodes exist, start with empty canvas
        $nodesJson = json_encode($initialNodes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        $edgesJson = json_encode($initialEdges, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

        // Load viewport from metadata or use default
        $viewport = $record->metadata['viewport'] ?? ['x' => 0, 'y' => 0, 'zoom' => 0.7];
        $viewportJson = json_encode($viewport, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

        $filterFieldsMapJson = json_encode($this->filterFieldsMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
        $availableNodesJson = json_encode($this->availableNodes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
    @endphp

    <div class="fi-section-content-ctn rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        x-data x-init="
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
        <div id="react-flow-container" style="width: 100%; height: calc(100vh - 300px); min-height: 600px;"
            data-nodes='{!! $nodesJson !!}' data-edges='{!! $edgesJson !!}' data-viewport='{!! $viewportJson !!}'
            data-event-options='@json(\Voodflow\Voodflow\Filament\Resources\TriggerResource::getEventClassOptions())'
            data-filter-fields-map='{!! $filterFieldsMapJson !!}' data-available-nodes='{!! $availableNodesJson !!}'
            data-livewire-id="{{ $this->getId() }}" wire:ignore></div>
    </div>

</x-filament-panels::page>