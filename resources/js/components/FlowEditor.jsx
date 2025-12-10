
import React, { useCallback, useEffect } from 'react';
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

// Custom Node Components
const TriggerNode = ({ data }) => {
    const isActive = data.status === 'active';
    const statusColor = isActive ? '#22c55e' : '#94a3b8';

    return (
        <div style={{
            background: '#0F172A', // Slate 900
            border: `1px solid ${isActive ? '#F97316' : '#1E293B'}`, // Orange border if active
            borderRadius: '12px',
            color: '#E2E8F0', // Slate 200
            padding: '0',
            minWidth: '280px',
            maxWidth: '320px',
            boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.3)',
            overflow: 'hidden',
        }}>
            {/* Header */}
            <div style={{
                background: 'linear-gradient(to right, #EA580C, #C2410C)', // Orange 600 -> 700
                padding: '10px 16px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
            }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style={{ width: '16px', height: '16px', color: 'white' }}>
                        <path fillRule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clipRule="evenodd" />
                    </svg>
                    <div style={{ fontSize: '12px', fontWeight: 'bold', color: 'white', textTransform: 'uppercase', letterSpacing: '0.05em' }}>Trigger</div>
                </div>
                {data.status && (
                    <div style={{
                        fontSize: '10px',
                        background: 'rgba(255,255,255,0.2)',
                        padding: '2px 6px',
                        borderRadius: '4px',
                        color: 'white',
                        fontWeight: 600,
                        textTransform: 'uppercase'
                    }}>
                        {data.status}
                    </div>
                )}
            </div>

            {/* Body */}
            <div style={{ padding: '16px' }}>
                <div style={{ fontSize: '16px', fontWeight: '600', color: '#F8FAFC', marginBottom: '8px' }}>
                    {data.label || 'Trigger'}
                </div>

                {data.eventClass && (
                    <div style={{
                        fontSize: '11px',
                        color: '#94A3B8',
                        marginBottom: '12px',
                        fontFamily: 'monospace',
                        background: '#1E293B',
                        padding: '4px 8px',
                        borderRadius: '4px',
                        border: '1px solid #334155',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                        whiteSpace: 'nowrap'
                    }}>
                        {data.eventClass}
                    </div>
                )}

                {data.description && (
                    <div style={{
                        fontSize: '12px',
                        color: '#64748B',
                        lineHeight: '1.4',
                        display: '-webkit-box',
                        WebkitLineClamp: 2,
                        WebkitBoxOrient: 'vertical',
                        overflow: 'hidden'
                    }}>
                        {data.description}
                    </div>
                )}
            </div>
            <Handle type="source" position={Position.Right} style={{ background: '#EA580C', width: '10px', height: '10px', right: '-5px' }} />
        </div>
    );
};

const FilterNode = ({ data }) => {
    const filters = Array.isArray(data.filters) ? data.filters : [];
    const matchType = data.matchType || 'all';
    const matchLabel = matchType === 'all' ? 'ALL CONDITIONS' : 'ANY CONDITION';

    // Helper map for operators
    const operatorMap = {
        'equals': 'Equals',
        'not_equals': 'Not equals',
        'contains': 'Contains',
        'not_contains': 'Not contains',
        'greater_than': '>',
        'greater_than_or_equal': '>=',
        'less_than': '<',
        'less_than_or_equal': '<=',
        'in': 'In',
        'not_in': 'Not in',
    };

    return (
        <div style={{
            background: '#1E293B', // Slate 800 - darker background
            borderRadius: '8px',
            color: '#E2E8F0',
            minWidth: '300px',
            maxWidth: '400px',
            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
            overflow: 'hidden',
            border: '1px solid #334155'
        }}>
            <Handle type="target" position={Position.Left} style={{ background: '#A855F7', width: '10px', height: '10px', left: '-5px' }} />

            {/* Header */}
            <div style={{
                background: '#A855F7', // Purple 500
                padding: '8px 16px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between'
            }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style={{ width: '16px', height: '16px', color: 'white' }}>
                        <path fillRule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clipRule="evenodd" />
                    </svg>
                    <div style={{ fontSize: '12px', fontWeight: 'bold', color: 'white', textTransform: 'uppercase' }}>Filter Logic</div>
                </div>
                <div className="nodrag" onClick={(e) => {
                    e.stopPropagation();
                    if (confirm('Are you sure you want to delete all filters?')) {
                        if (data.livewireId && window.Livewire) {
                            window.Livewire.find(data.livewireId).call('deleteFilters');
                        }
                    }
                }} style={{ cursor: 'pointer', color: 'rgba(255,255,255,0.8)', display: 'flex', marginLeft: '8px' }} title="Delete Filters">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style={{ width: '14px', height: '14px' }}>
                        <path fillRule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
                    </svg>
                </div>
            </div>

            {/* Body */}
            <div style={{ padding: '12px 16px' }}>
                {/* Conditions List */}
                {filters.length > 0 ? (
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                        {filters.map((filter, index) => (
                            <div key={index} style={{
                                background: '#0F172A', // Slate 900
                                padding: '8px 12px',
                                borderRadius: '6px',
                                fontSize: '12px',
                                border: '1px solid #334155',
                                display: 'flex',
                                alignItems: 'center',
                                gap: '8px',
                                flexWrap: 'wrap'
                            }}>
                                <span style={{ color: '#94A3B8', fontWeight: '500' }}>{filter.data?.field || 'Field'}</span>
                                <span style={{ color: '#F97316', fontWeight: 'bold' }}>{operatorMap[filter.type] || filter.type}</span>
                                <span style={{ color: '#E2E8F0', fontWeight: '500' }}>{filter.data?.value}</span>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div style={{ fontSize: '13px', color: '#94A3B8', fontStyle: 'italic' }}>No filters configured</div>
                )}

                {/* Match Type Badge (Footer) */}
                <div style={{ marginTop: '12px', borderTop: '1px solid #334155', paddingTop: '8px', display: 'flex', justifyContent: 'flex-end' }}>
                    <div style={{
                        fontSize: '10px',
                        fontWeight: 'bold',
                        background: '#334155',
                        color: '#F8FAFC',
                        padding: '4px 8px',
                        borderRadius: '4px',
                        textTransform: 'uppercase'
                    }}>
                        {matchLabel}
                    </div>
                </div>
            </div>

            <Handle type="source" position={Position.Right} style={{ background: '#A855F7', width: '10px', height: '10px', right: '-5px' }} />
        </div>
    );
};

const ActionNode = ({ data }) => {
    // Determine color based on action type
    const isWebhook = data.actionType === 'webhook';
    // Blue for Webhook, Gray/Purple for others
    const headerFrom = isWebhook ? '#2563EB' : '#4B5563'; // Blue 600 or Gray 600
    const headerTo = isWebhook ? '#1D4ED8' : '#374151'; // Blue 700 or Gray 700
    const iconColor = isWebhook ? '#3B82F6' : '#9CA3AF';
    const borderColor = isWebhook ? '#1D4ED8' : '#374151';

    return (
        <div style={{
            background: '#0F172A', // Slate 900
            border: `1px solid ${borderColor}`,
            borderRadius: '12px',
            color: '#E2E8F0', // Slate 200
            padding: '0',
            minWidth: '240px',
            maxWidth: '280px',
            boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
            overflow: 'hidden',
        }}>
            {/* Header */}
            <div style={{
                background: `linear-gradient(to right, ${headerFrom}, ${headerTo})`,
                padding: '8px 16px',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
            }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                    {isWebhook ? (
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style={{ width: '16px', height: '16px', color: 'white' }}>
                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                        </svg>
                    ) : (
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style={{ width: '16px', height: '16px', color: 'white' }}>
                            <path fillRule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.923 1.042A1 1 0 0113.88 18H6.12a1 1 0 01-.75-1.65L6.5 15H5a2 2 0 01-2-2V5zm11 1H6v8h8V6z" clipRule="evenodd" />
                        </svg>
                    )}
                    <div style={{ fontSize: '12px', fontWeight: 'bold', color: 'white', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                        {data.actionType || 'Action'}
                    </div>
                </div>
                <div className="nodrag" onClick={(e) => {
                    e.stopPropagation();
                    if (confirm('Are you sure you want to delete this action?')) {
                        if (data.livewireId && window.Livewire) {
                            window.Livewire.find(data.livewireId).call('deleteAction', data.actionId);
                        }
                    }
                }} style={{ cursor: 'pointer', color: 'rgba(255,255,255,0.8)', display: 'flex' }} title="Delete Action">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style={{ width: '14px', height: '14px' }}>
                        <path fillRule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
                    </svg>
                </div>
            </div>

            {/* Body */}
            <div style={{ padding: '12px 16px' }}>
                <div style={{ fontSize: '15px', fontWeight: '500', color: '#F8FAFC', lineHeight: '1.4' }}>
                    {data.label || 'Action'}
                </div>
            </div>

            <Handle type="target" position={Position.Left} style={{ background: headerFrom, width: '10px', height: '10px', left: '-5px' }} />
        </div>
    );
};

const nodeTypes = {
    trigger: TriggerNode,
    filter: FilterNode,
    action: ActionNode,
};

function FlowCanvas({ initialNodes, initialEdges, initialViewport, livewireId }) {
    const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes.map(n => ({
        ...n,
        data: { ...n.data, livewireId }
    })));
    const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges);
    const { getViewport } = useReactFlow();

    const onConnect = useCallback((params) => {
        setEdges((eds) => addEdge(params, eds));
    }, [setEdges]);

    const onNodeDoubleClick = useCallback((event, node) => {
        if (!window.Livewire || !livewireId) return;
        const component = window.Livewire.find(livewireId);
        if (!component) return;

        if (node.type === 'trigger') {
            component.call('mountAction', 'editTrigger');
        } else if (node.type === 'filter') {
            component.call('mountAction', 'editFilters');
        } else if (node.type === 'action') {
            component.call('mountAction', 'editAction', { actionId: node.data.actionId });
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
            component.call('mountAction', 'editTrigger');
        }
    }, [livewireId]);

    const handleAddFilter = useCallback(() => {
        if (!window.Livewire || !livewireId) return;
        const component = window.Livewire.find(livewireId);
        if (component) {
            component.call('mountAction', 'editFilters');
        }
    }, [livewireId]);

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

export default function FlowEditor({ nodes, edges, viewport, livewireId }) {
    return (
        <ReactFlowProvider>
            <FlowCanvas initialNodes={nodes} initialEdges={edges} initialViewport={viewport} livewireId={livewireId} />
        </ReactFlowProvider>
    );
}
