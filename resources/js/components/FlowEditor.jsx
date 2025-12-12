
import React, { useCallback, useEffect, useState, useRef, useMemo } from 'react';
import ReactFlow, {
    ReactFlowProvider,
    useNodesState,
    useEdgesState,
    addEdge,
    Background,
    Controls,
    MiniMap,
    Handle,
    Position,
    useReactFlow,
} from 'reactflow';


import TriggerNode from './TriggerNode';
import FilterNode from './FilterNode';
import ActionNode from './ActionNode';
import SendWebhookNode from './SendWebhookNode';
import EmptyCanvasState from './EmptyCanvasState';

const nodeTypes = {
    trigger: TriggerNode,
    filter: FilterNode,
    action: ActionNode,
    sendWebhook: SendWebhookNode,
};

function FlowCanvas({ initialNodes, initialEdges, initialViewport, livewireId, eventOptions, filterFieldsMap, availableNodesMap }) {

    // Transform map to list for UI
    const availableNodesList = useMemo(() => {
        if (!availableNodesMap) return [];
        return Object.values(availableNodesMap)
            .filter(node => node.metadata?.positioning?.input === true)
            .map(node => ({
                type: node.type,
                label: node.metadata?.label || node.name,
                icon: node.metadata?.icon || 'circle',
                color: node.metadata?.color || 'gray',
                group: node.metadata?.group || 'Custom',
                positioning: node.metadata?.positioning || {}
            }));
    }, [availableNodesMap]);

    const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes.map(n => {
        const baseData = {
            ...n.data,
            livewireId,
            eventOptions,
            availableNodes: availableNodesList
        };
        // Pass filterFieldsMap to filter nodes for dynamic field lookup
        if (n.type === 'filter') {
            baseData.filterFieldsMap = filterFieldsMap;
        }
        return { ...n, data: baseData };
    }));

    const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges);
    const { getViewport } = useReactFlow();

    const onConnect = useCallback((params) => {
        setEdges((eds) => addEdge(params, eds));
    }, [setEdges]);

    const onNodeDoubleClick = useCallback((event, node) => {
        // Double click logic is now handled internally by nodes (expanding)
    }, []);

    const handleAddAction = useCallback(() => {
        if (!window.Livewire || !livewireId) return;
        const component = window.Livewire.find(livewireId);
        if (component) {
            component.call('createActionNode', { actionType: 'log' });
        }
    }, [livewireId]);

    const handleAddTrigger = useCallback(() => {
        if (!window.Livewire || !livewireId) return;
        const component = window.Livewire.find(livewireId);
        if (component) {
            component.call('createNewTrigger');
        }
    }, [livewireId]);

    const handleAddFilter = useCallback(() => {
        if (!window.Livewire || !livewireId) return;
        const component = window.Livewire.find(livewireId);
        if (component) {
            component.call('createNewFilter');
        }
    }, [livewireId]);

    // Save viewport when user finishes panning or zooming
    const onMoveEnd = useCallback(() => {
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
                    sourceHandle: edge.sourceHandle || null,
                    targetHandle: edge.targetHandle || null,
                })),
                viewport: getViewport(),
            };

            if (window.Livewire && livewireId) {
                const component = window.Livewire.find(livewireId);
                if (component) {
                    component.call('saveFlowData', flowData);
                }
            }
        }, 500); // Debounce 500ms for viewport changes

        return () => clearTimeout(timer);
    }, [nodes, edges, livewireId, getViewport]);

    // Livewire event listeners for dynamic node updates
    useEffect(() => {
        // Handle new node added - Livewire 3 passes data directly
        const handleNodeAdded = (nodeData) => {
            console.log('[FlowEditor] Node added event received:', nodeData);

            // Handle array format from Livewire
            const data = Array.isArray(nodeData) ? nodeData[0] : nodeData;

            if (data && data.id) {
                const newNode = {
                    id: data.id,
                    type: data.type,
                    position: data.position || { x: 400, y: 100 },
                    data: {
                        ...data.data,
                        livewireId,
                        eventOptions,
                        filterFieldsMap,
                        availableNodes: availableNodesList
                    }
                };
                console.log('[FlowEditor] Adding new node:', newNode);
                setNodes(nds => [...nds, newNode]);
            }
        };

        // Handle node removed
        const handleNodeRemoved = (nodeId) => {
            console.log('[FlowEditor] Node removed event received:', nodeId);
            const id = Array.isArray(nodeId) ? nodeId[0] : nodeId;

            if (id) {
                console.log('[FlowEditor] Removing node:', id);
                setNodes(nds => nds.filter(n => n.id !== id));
                setEdges(eds => eds.filter(e => e.source !== id && e.target !== id));
            }
        };

        // Handle full refresh
        const handleRefresh = () => {
            console.log('Flow refresh received, reloading...');
            window.location.reload();
        };

        // Handle edge added event from backend
        const handleEdgeAdded = (edgeData) => {
            console.log('[FlowEditor] Edge added event received:', edgeData);
            const data = Array.isArray(edgeData) ? edgeData[0] : edgeData;

            if (data && data.id) {
                const newEdge = {
                    id: data.id,
                    source: data.source,
                    target: data.target,
                    sourceHandle: data.sourceHandle || null,
                    targetHandle: data.targetHandle || null,
                    type: 'default', // Use default or smoothstep
                };
                setEdges((eds) => addEdge(newEdge, eds));
            }
        };

        // Register Livewire listeners
        if (window.Livewire && window.Livewire.on) {
            window.Livewire.on('node-added', handleNodeAdded);
            window.Livewire.on('node-removed', handleNodeRemoved);
            window.Livewire.on('edge-added', handleEdgeAdded);
            window.Livewire.on('flow-refresh', handleRefresh);
        }

        // Also listen for browser events (fallback)
        window.addEventListener('flow-refresh', handleRefresh);

        return () => {
            window.removeEventListener('flow-refresh', handleRefresh);
        };
    }, [livewireId, eventOptions, filterFieldsMap, availableNodesList, setNodes, setEdges]);

    // Sync with Livewire (debounced save)
    useEffect(() => {
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
                    sourceHandle: edge.sourceHandle || null,
                    targetHandle: edge.targetHandle || null,
                })),
                viewport: getViewport(),
            };

            if (window.Livewire && livewireId) {
                const component = window.Livewire.find(livewireId);
                component?.call('saveFlowData', flowData);
            }
        }, 1000); // 1s debounce

        return () => clearTimeout(timer);
    }, [nodes, edges, livewireId]);

    const hasTrigger = nodes.some(n => n.type === 'trigger');
    const isEmpty = nodes.length === 0;

    return (
        <div style={{ width: '100%', height: '100%', position: 'relative' }}>
            {/* Empty State */}
            {isEmpty && (
                <EmptyCanvasState
                    onAddTrigger={handleAddTrigger}
                    onAddAction={handleAddAction}
                    onAddFilter={handleAddFilter}
                />
            )}

            <ReactFlow
                nodes={nodes}
                edges={edges}
                nodeTypes={nodeTypes}
                onNodesChange={onNodesChange}
                onEdgesChange={onEdgesChange}
                onConnect={onConnect}
                onNodeDoubleClick={onNodeDoubleClick}
                onMoveEnd={onMoveEnd}
                defaultViewport={initialViewport}
                fitView={!initialViewport || (initialViewport.x === 0 && initialViewport.y === 0 && initialViewport.zoom === 1)}
            >
                <Background variant="dots" gap={12} size={1} />
                <Controls />
                <MiniMap />
            </ReactFlow>
        </div>
    );
}

export default function FlowEditor({ nodes, edges, viewport, livewireId, eventOptions, filterFieldsMap, availableNodesMap }) {
    return (
        <ReactFlowProvider>
            <FlowCanvas
                initialNodes={nodes}
                initialEdges={edges}
                initialViewport={viewport}
                livewireId={livewireId}
                eventOptions={eventOptions}
                filterFieldsMap={filterFieldsMap || {}}
                availableNodesMap={availableNodesMap || {}}
            />
        </ReactFlowProvider>
    );
}
