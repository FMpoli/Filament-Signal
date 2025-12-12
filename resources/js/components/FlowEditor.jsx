
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

const nodeTypes = {
    trigger: TriggerNode,
    filter: FilterNode,
    action: ActionNode,
};

function FlowCanvas({ initialNodes, initialEdges, initialViewport, livewireId, eventOptions, filterFieldsMap }) {
    const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes.map(n => {
        const baseData = { ...n.data, livewireId, eventOptions };
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
        if (!window.Livewire || !livewireId) return;
        const component = window.Livewire.find(livewireId);
        if (!component) return;

        // Only action nodes open modal on double-click
        if (node.type === 'action') {
            component.call('mountAction', 'editAction', { nodeId: node.id, nodeData: node.data });
        }
    }, [livewireId]);

    const handleAddAction = useCallback(() => {
        if (!window.Livewire || !livewireId) return;
        const component = window.Livewire.find(livewireId);
        if (component) {
            component.call('mountAction', 'createAction');
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

    // Livewire Refresh Listener
    useEffect(() => {
        const handleRefresh = () => {
            console.log('Flow refresh received, reloading...');
            window.location.reload();
        };
        window.addEventListener('flow-refresh', handleRefresh);
        if (window.Livewire && window.Livewire.on) {
            window.Livewire.on('flow-refresh', handleRefresh);
        }
        return () => {
            window.removeEventListener('flow-refresh', handleRefresh);
        };
    }, []);

    // Sync with Livewire (existing code...)
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
                })),
                viewport: getViewport(),
            };

            if (window.Livewire && livewireId) {
                const component = window.Livewire.find(livewireId);
                if (component) {
                    component.call('saveFlowData', flowData);
                }
            }
        }, 1000); // Debounce 1s

        return () => clearTimeout(timer);
    }, [nodes, edges, livewireId]); // getViewport is stable, can emit

    const hasTrigger = nodes.some(n => n.type === 'trigger');
    const hasFilter = nodes.some(n => n.type === 'filter');

    return (
        <div style={{ width: '100%', height: '100%', position: 'relative' }}>
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

            {/* Toolbar */}
            <div style={{
                position: 'absolute',
                top: '20px',
                right: '20px',
                zIndex: 5,
                display: 'flex',
                gap: '10px',
            }}>
                <button
                    onClick={handleAddTrigger}
                    disabled={hasTrigger}
                    style={{
                        background: hasTrigger ? '#64748B' : '#EA580C',
                        color: 'white',
                        border: 'none',
                        padding: '8px 16px',
                        borderRadius: '6px',
                        fontWeight: '600',
                        cursor: hasTrigger ? 'not-allowed' : 'pointer',
                        boxShadow: '0 4px 6px rgba(0,0,0,0.1)',
                        display: 'flex', alignItems: 'center', gap: '6px', opacity: hasTrigger ? 0.7 : 1
                    }}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style={{ width: '16px', height: '16px' }}>
                        <path fillRule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clipRule="evenodd" />
                    </svg>
                    Add Trigger
                </button>
                <button
                    onClick={handleAddFilter}
                    disabled={hasFilter}
                    style={{
                        background: hasFilter ? '#64748B' : '#9333EA',
                        color: 'white',
                        border: 'none',
                        padding: '8px 16px',
                        borderRadius: '6px',
                        fontWeight: '600',
                        cursor: hasFilter ? 'not-allowed' : 'pointer',
                        boxShadow: '0 4px 6px rgba(0,0,0,0.1)',
                        display: 'flex', alignItems: 'center', gap: '6px', opacity: hasFilter ? 0.7 : 1
                    }}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style={{ width: '16px', height: '16px' }}>
                        <path fillRule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clipRule="evenodd" />
                    </svg>
                    Add Filter
                </button>
                <button
                    onClick={handleAddAction}
                    style={{
                        background: '#2563EB',
                        color: 'white',
                        border: 'none',
                        padding: '8px 16px',
                        borderRadius: '6px',
                        fontWeight: '600',
                        cursor: 'pointer',
                        boxShadow: '0 4px 6px rgba(0,0,0,0.1)',
                        display: 'flex',
                        alignItems: 'center',
                        gap: '6px',
                    }}
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style={{ width: '16px', height: '16px' }}>
                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clipRule="evenodd" />
                    </svg>
                    Add Action
                </button>
            </div>
        </div>
    );
}

export default function FlowEditor({ nodes, edges, viewport, livewireId, eventOptions, filterFieldsMap }) {
    return (
        <ReactFlowProvider>
            <FlowCanvas
                initialNodes={nodes}
                initialEdges={edges}
                initialViewport={viewport}
                livewireId={livewireId}
                eventOptions={eventOptions}
                filterFieldsMap={filterFieldsMap || {}}
            />
        </ReactFlowProvider>
    );
}
