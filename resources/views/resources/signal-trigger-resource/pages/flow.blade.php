<x-filament-panels::page>
    @once
        @push('scripts')
            <script>
                window.livewirePrefetch = false;
            </script>
        @endpush
    @endonce
    
    @php
        $record = $this->record;
        $flowData = $record->metadata['flow'] ?? null;
        
        // Prepara i dati iniziali per React Flow
        $initialNodes = [];
        $initialEdges = [];
        
        if ($flowData) {
            $initialNodes = $flowData['nodes'] ?? [];
            $initialEdges = $flowData['edges'] ?? [];
        } else {
            // Crea nodi iniziali dal trigger esistente
            // Nodo Trigger
            $initialNodes[] = [
                'id' => 'trigger-1',
                'type' => 'trigger',
                'position' => ['x' => 100, 'y' => 100],
                'data' => [
                    'label' => $record->name,
                    'eventClass' => $record->event_class,
                    'description' => $record->description,
                    'status' => $record->status,
                ],
            ];
            
            // Nodi Filter (se ci sono filtri)
            if (!empty($record->filters)) {
                $initialNodes[] = [
                    'id' => 'filter-1',
                    'type' => 'filter',
                    'position' => ['x' => 400, 'y' => 100],
                    'data' => [
                        'label' => 'Filters',
                        'filters' => $record->filters,
                        'matchType' => $record->match_type,
                    ],
                ];
                $initialEdges[] = [
                    'id' => 'e1-2',
                    'source' => 'trigger-1',
                    'target' => 'filter-1',
                ];
            }
            
            // Nodi Action
            foreach ($record->actions as $index => $action) {
                $nodeId = 'action-' . ($index + 1);
                $prevNodeId = !empty($record->filters) ? 'filter-1' : 'trigger-1';
                
                $initialNodes[] = [
                    'id' => $nodeId,
                    'type' => 'action',
                    'position' => ['x' => 700 + ($index * 300), 'y' => 100],
                    'data' => [
                        'label' => $action->name,
                        'actionType' => $action->action_type,
                        'configuration' => $action->configuration,
                        'executionOrder' => $action->execution_order,
                        'isActive' => $action->is_active,
                    ],
                ];
                
                $initialEdges[] = [
                    'id' => 'e-' . ($index + 1),
                    'source' => $prevNodeId,
                    'target' => $nodeId,
                ];
                
                $prevNodeId = $nodeId;
            }
        }
    @endphp

    <div class="fi-section-content-ctn rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" wire:ignore.self>
        <div id="react-flow-container" 
             style="width: 100%; height: calc(100vh - 300px); min-height: 600px;" 
             data-nodes="{{ e(json_encode($initialNodes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) }}"
             data-edges="{{ e(json_encode($initialEdges, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)) }}"
             data-trigger-id="{{ $record->id }}"
             data-livewire-id="{{ $this->getId() }}"
             wire:ignore>
            <div class="flex items-center justify-center h-full">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
                    <p class="text-gray-500 dark:text-gray-400">Loading Flow Editor...</p>
                </div>
            </div>
        </div>
    </div>

    @once
        @push('styles')
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reactflow@latest/dist/style.css">
            <style>
            .react-flow__node-trigger {
                background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
                border-radius: 8px;
                color: white;
                font-weight: 600;
                padding: 12px 16px;
                min-width: 200px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .react-flow__node-filter {
                background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
                border-radius: 8px;
                color: white;
                font-weight: 600;
                padding: 12px 16px;
                min-width: 200px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .react-flow__node-action {
                background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                border-radius: 8px;
                color: white;
                font-weight: 600;
                padding: 12px 16px;
                min-width: 200px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .react-flow__node-label {
                font-size: 14px;
                margin-bottom: 4px;
            }
            .react-flow__node-subtitle {
                font-size: 12px;
                opacity: 0.9;
                margin-top: 4px;
            }
            .react-flow__handle {
                width: 10px;
                height: 10px;
                background: white;
                border: 2px solid #1f2937;
            }
            </style>
        @endpush
    @endonce

    @once
        @push('scripts')
            <!-- React Flow CDN -->
            <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
            <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
            <script crossorigin src="https://cdn.jsdelivr.net/npm/reactflow@latest/dist/umd/index.js"></script>
        @endpush
    @endonce
    
    @push('scripts')
        <script>
            let reactFlowLoaded = false;
            
            function waitForReactFlow() {
                return new Promise((resolve) => {
                    if (typeof React !== 'undefined' && typeof ReactDOM !== 'undefined' && typeof ReactFlow !== 'undefined') {
                        resolve();
                        return;
                    }
                    
                    let attempts = 0;
                    const checkInterval = setInterval(() => {
                        attempts++;
                        if (typeof React !== 'undefined' && typeof ReactDOM !== 'undefined' && typeof ReactFlow !== 'undefined') {
                            clearInterval(checkInterval);
                            resolve();
                        } else if (attempts > 50) {
                            clearInterval(checkInterval);
                            console.error('React Flow failed to load after 5 seconds');
                            resolve(); // Resolve anyway to show error
                        }
                    }, 100);
                });
            }
            
            async function initReactFlow() {
                console.log('initReactFlow called');
                const container = document.getElementById('react-flow-container');
                if (!container) {
                    console.error('React Flow container not found');
                    return;
                }

                console.log('Container found, waiting for React Flow...');
                // Mostra loading
                container.innerHTML = '<div class="p-4 text-center text-gray-500 dark:text-gray-400">Loading React Flow...</div>';

                // Attendi che tutti gli script siano caricati
                await waitForReactFlow();

                console.log('Checking React and ReactFlow availability...');
                // Verifica che React e ReactFlow siano caricati
                if (typeof React === 'undefined' || typeof ReactDOM === 'undefined') {
                    console.error('React not loaded');
                    container.innerHTML = '<div class="p-4 text-center text-red-500">Error: React not loaded</div>';
                    return;
                }

                if (typeof ReactFlow === 'undefined') {
                    console.error('ReactFlow not loaded');
                    container.innerHTML = '<div class="p-4 text-center text-red-500">Error: ReactFlow not loaded. Please check the CDN URL.</div>';
                    return;
                }

                console.log('React and ReactFlow loaded, initializing...');

                try {
                    const { ReactFlowProvider, ReactFlow: ReactFlowComponent, useNodesState, useEdgesState, addEdge, Background, Controls, MiniMap, Handle, Position } = ReactFlow;
                
                    // Parse dei dati dai data attributes con gestione errori
                    let initialNodes = [];
                    let initialEdges = [];
                    try {
                        const nodesAttr = container.getAttribute('data-nodes');
                        const edgesAttr = container.getAttribute('data-edges');
                        if (nodesAttr) {
                            initialNodes = JSON.parse(nodesAttr);
                        }
                        if (edgesAttr) {
                            initialEdges = JSON.parse(edgesAttr);
                        }
                    } catch (e) {
                        console.error('Error parsing initial nodes/edges:', e);
                        console.log('Nodes attribute:', container.getAttribute('data-nodes'));
                        console.log('Edges attribute:', container.getAttribute('data-edges'));
                    }
                    const triggerId = container.dataset.triggerId;

                // Nodo personalizzato per Trigger
                function TriggerNode({ data }) {
                    return React.createElement('div', { className: 'react-flow__node-trigger' },
                        React.createElement(Handle, { type: 'source', position: Position.Right }),
                        React.createElement('div', { className: 'react-flow__node-label' }, 'TRIGGER'),
                        React.createElement('div', { style: { fontSize: '16px', fontWeight: 'bold', marginTop: '4px' } }, data.label || 'Trigger'),
                        React.createElement('div', { className: 'react-flow__node-subtitle' }, data.eventClass ? data.eventClass.split(':').pop() : 'Eloquent Event')
                    );
                }

                // Nodo personalizzato per Filter
                function FilterNode({ data }) {
                    const filterCount = data.filters ? data.filters.length : 0;
                    return React.createElement('div', { className: 'react-flow__node-filter' },
                        React.createElement(Handle, { type: 'target', position: Position.Left }),
                        React.createElement(Handle, { type: 'source', position: Position.Right }),
                        React.createElement('div', { className: 'react-flow__node-label' }, 'FILTER'),
                        React.createElement('div', { style: { fontSize: '16px', fontWeight: 'bold', marginTop: '4px' } }, data.label || 'Filters'),
                        React.createElement('div', { className: 'react-flow__node-subtitle' }, `${filterCount} condition${filterCount !== 1 ? 's' : ''}`)
                    );
                }

                // Nodo personalizzato per Action
                function ActionNode({ data }) {
                    const actionType = data.actionType === 'webhook' ? 'POST Request' : 'Log';
                    return React.createElement('div', { className: 'react-flow__node-action' },
                        React.createElement(Handle, { type: 'target', position: Position.Left }),
                        React.createElement('div', { className: 'react-flow__node-label' }, 'ACTION'),
                        React.createElement('div', { style: { fontSize: '16px', fontWeight: 'bold', marginTop: '4px' } }, data.label || 'Action'),
                        React.createElement('div', { className: 'react-flow__node-subtitle' }, actionType)
                    );
                }

                // Registra i tipi di nodi personalizzati
                const nodeTypes = {
                    trigger: TriggerNode,
                    filter: FilterNode,
                    action: ActionNode,
                };

                // Componente Flow
                function FlowCanvas() {
                    const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes);
                    const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges);

                    const onConnect = React.useCallback((params) => {
                        setEdges((eds) => addEdge(params, eds));
                    }, [setEdges]);

                    // Salva automaticamente quando cambiano i nodi o gli edge
                    React.useEffect(() => {
                        const timer = setTimeout(() => {
                            const flowData = {
                                nodes: nodes.map(node => ({
                                    id: node.id,
                                    type: node.type,
                                    position: node.position,
                                    data: node.data,
                                })),
                                edges: edges.map(edge => ({
                                    id: edge.id,
                                    source: edge.source,
                                    target: edge.target,
                                })),
                            };

                            // Salva tramite Livewire se disponibile
                            const livewireId = container.dataset.livewireId;
                            if (window.Livewire && livewireId) {
                                try {
                                    const component = window.Livewire.find(livewireId);
                                    if (component) {
                                        component.call('saveFlowData', flowData);
                                    }
                                } catch (e) {
                                    console.warn('Could not save flow data:', e);
                                }
                            }
                        }, 1000); // Debounce di 1 secondo

                        return () => clearTimeout(timer);
                    }, [nodes, edges]);

                    return React.createElement(ReactFlowComponent, {
                        nodes: nodes,
                        edges: edges,
                        nodeTypes: nodeTypes,
                        onNodesChange: onNodesChange,
                        onEdgesChange: onEdgesChange,
                        onConnect: onConnect,
                        fitView: true,
                        defaultEdgeOptions: {
                            style: { strokeWidth: 2, stroke: '#6b7280' },
                            type: 'smoothstep',
                        },
                    }, [
                        React.createElement(Background, { key: 'bg', variant: 'dots', gap: 12, size: 1 }),
                        React.createElement(Controls, { key: 'controls' }),
                        React.createElement(MiniMap, { key: 'minimap', nodeColor: '#9333ea' }),
                    ]);
                }

                // Renderizza React Flow
                try {
                    console.log('[ReactFlow] Rendering React Flow...');
                    const root = ReactDOM.createRoot(container);
                    root.render(
                        React.createElement(ReactFlowProvider, null,
                            React.createElement(FlowCanvas)
                        )
                    );
                    console.log('[ReactFlow] React Flow rendered successfully');
                } catch (error) {
                    console.error('[ReactFlow] Error rendering React Flow:', error);
                    container.innerHTML = '<div class="p-4 text-center text-red-500">Error loading React Flow: ' + error.message + '<br><small>Check browser console for details</small></div>';
                }
            }
            
            // Funzione per inizializzare quando tutto è pronto
            function startInit() {
                // Verifica che il container esista
                const container = document.getElementById('react-flow-container');
                if (!container) {
                    setTimeout(startInit, 100);
                    return;
                }
                
                // Inizializza React Flow
                initReactFlow();
            }
            
            // Attendi che Livewire sia pronto (per navigazione SPA)
            document.addEventListener('livewire:navigated', function() {
                setTimeout(startInit, 200);
            });
            
            // Attendi che Livewire sia inizializzato
            document.addEventListener('livewire:init', function() {
                setTimeout(startInit, 200);
            });
            
            // Fallback per caricamento diretto della pagina
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(startInit, 500);
                });
            } else {
                setTimeout(startInit, 500);
            }
        </script>
    @endpush
</x-filament-panels::page>

