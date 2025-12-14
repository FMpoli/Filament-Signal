
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
import FilterProNode from './FilterProNode';
import SendWebhookNode from './SendWebhookNode';
import ConditionalNode from './ConditionalNode';
import EmptyCanvasState from './EmptyCanvasState';
import ContextMenu from './ContextMenu';

const nodeTypes = {
    // Legacy support
    trigger: TriggerNode,
    filter: FilterNode,
    filter_pro: FilterProNode,
    sendWebhook: SendWebhookNode,

    // Namespaced types (Best Practice)
    base33_trigger: TriggerNode,
    base33_filter: FilterNode,
    base33_filter_pro: FilterProNode,
    base33_send_webhook: SendWebhookNode,
    base33_conditional: ConditionalNode,
};

function FlowCanvas({ initialNodes, initialEdges, initialViewport, livewireId, eventOptions, filterFieldsMap, availableNodesMap }) {

    // Transform map to list for UI
    const availableNodesList = useMemo(() => {
        if (!availableNodesMap) return [];
        const list = Object.values(availableNodesMap)
            // Don't filter by input - we want ALL nodes including triggers
            .map(node => ({
                id: node.className, // Unique identifier instead of just type
                type: node.type, // Visual component type (trigger, filter, etc)
                label: node.metadata?.label || node.name,
                icon: (node.metadata?.icon || 'circle').replace('heroicon-o-', ''),
                color: node.metadata?.color || 'gray',
                group: node.metadata?.group || 'Custom',
                positioning: node.metadata?.positioning || {},
                metadata: node.metadata // Include full metadata
            }));
        return list;
    }, [availableNodesMap]);

    // TEMPORARY: Force light mode until we understand Filament's theme system
    // Filament doesn't use .dark class on html element
    const [colorMode, setColorMode] = useState('light');

    // TODO: Investigate Filament's actual theme detection mechanism
    // For now, always use light mode
    useEffect(() => {
        console.log('[Voodflow] Forcing light mode - Filament theme detection TBD');
    }, []);

    const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes.map(n => {
        const baseData = {
            ...n.data,
            livewireId,
            eventOptions,
            availableNodes: availableNodesList
        };
        // Pass filterFieldsMap to all nodes for dynamic field lookup
        baseData.filterFieldsMap = filterFieldsMap;
        return { ...n, data: baseData };
    }));

    const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges);
    const [menu, setMenu] = useState(null);
    const ref = useRef(null);
    const { getViewport, screenToFlowPosition } = useReactFlow();

    const onConnect = useCallback((params) => {
        setEdges((eds) => addEdge(params, eds));
    }, [setEdges]);

    const onNodeDoubleClick = useCallback((event, node) => {
        // Double click logic is now handled internally by nodes (expanding)
    }, []);

    const onPaneContextMenu = useCallback(
        (event) => {
            event.preventDefault();

            // Calculate position for the menu
            const pane = ref.current.getBoundingClientRect();
            setMenu({
                id: null,
                top: event.clientY < pane.bottom - 200 ? event.clientY - pane.top : undefined,
                left: event.clientX < pane.right - 200 ? event.clientX - pane.left : undefined,
                bottom: event.clientY >= pane.bottom - 200 ? pane.bottom - event.clientY : undefined,
                right: event.clientX >= pane.right - 200 ? pane.right - event.clientX : undefined,
                // store absolute screen coords for flow conversion later
                screenX: event.clientX,
                screenY: event.clientY,
            });
        },
        [],
    );

    const onNodeContextMenu = useCallback(
        (event, node) => {
            event.preventDefault();

            // Calculate position for the menu
            const pane = ref.current.getBoundingClientRect();
            setMenu({
                id: node.id, // Store the source node ID
                top: event.clientY < pane.bottom - 200 ? event.clientY - pane.top : undefined,
                left: event.clientX < pane.right - 200 ? event.clientX - pane.left : undefined,
                bottom: event.clientY >= pane.bottom - 200 ? pane.bottom - event.clientY : undefined,
                right: event.clientX >= pane.right - 200 ? pane.right - event.clientX : undefined,
                // store absolute screen coords for flow conversion later
                screenX: event.clientX,
                screenY: event.clientY,
            });
        },
        [],
    );

    const onPaneClick = useCallback(() => setMenu(null), []);

    const handleAddAction = useCallback((posOrEvent) => {
        if (!window.Livewire || !livewireId) return;
        const component = window.Livewire.find(livewireId);

        let flowPos = null;
        if (posOrEvent && posOrEvent.x && posOrEvent.y) {
            flowPos = screenToFlowPosition({ x: posOrEvent.x, y: posOrEvent.y });
        } else if (menu && menu.screenX && menu.screenY) {
            flowPos = screenToFlowPosition({ x: menu.screenX, y: menu.screenY });
        }

        let sourceNodeId = menu?.id || null;

        if (component) {
            component.call('createActionNode', { actionType: 'log', position: flowPos, sourceNodeId: sourceNodeId });
        }
        setMenu(null);
    }, [livewireId, menu, screenToFlowPosition]);

    const handleAddTrigger = useCallback((posOrEvent) => {
        if (!window.Livewire || !livewireId) return;
        const component = window.Livewire.find(livewireId);

        let flowPos = null;
        if (posOrEvent && posOrEvent.x && posOrEvent.y) {
            flowPos = screenToFlowPosition({ x: posOrEvent.x, y: posOrEvent.y });
        } else if (menu && menu.screenX && menu.screenY) {
            flowPos = screenToFlowPosition({ x: menu.screenX, y: menu.screenY });
        }
        // Triggers usually don't have sources, but just in case

        if (component) {
            component.call('createNewTrigger', { position: flowPos });
        }
        setMenu(null);
    }, [livewireId, menu, screenToFlowPosition]);

    const handleAddFilter = useCallback((posOrEvent) => {
        if (!window.Livewire || !livewireId) return;
        const component = window.Livewire.find(livewireId);

        let flowPos = null;
        if (posOrEvent && posOrEvent.x && posOrEvent.y) {
            flowPos = screenToFlowPosition({ x: posOrEvent.x, y: posOrEvent.y });
        } else if (menu && menu.screenX && menu.screenY) {
            flowPos = screenToFlowPosition({ x: menu.screenX, y: menu.screenY });
        }

        let sourceNodeId = menu?.id || null;

        if (component) {
            component.call('createNewFilter', { position: flowPos, sourceNodeId: sourceNodeId });
        }
        setMenu(null);
    }, [livewireId, menu, screenToFlowPosition]);

    const handleAddWebhook = useCallback(() => {
        if (!window.Livewire || !livewireId) return;
        const component = window.Livewire.find(livewireId);

        let flowPos = null;
        if (menu && menu.screenX && menu.screenY) {
            flowPos = screenToFlowPosition({ x: menu.screenX, y: menu.screenY });
        }

        let sourceNodeId = menu?.id || null;

        if (component) {
            component.call('createSendWebhookNode', { position: flowPos, sourceNodeId: sourceNodeId });
        }
        setMenu(null);
    }, [livewireId, menu, screenToFlowPosition]);

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
        <div ref={ref} style={{ width: '100%', height: '100%', position: 'relative' }}>
            {/* Empty State */}
            {isEmpty && (
                <EmptyCanvasState
                    availableNodes={availableNodesList}
                    onAddNode={(nodeType) => {
                        if (!window.Livewire || !livewireId) return;
                        const component = window.Livewire.find(livewireId);

                        let flowPos = { x: 400, y: 200 };

                        if (component) {
                            component.call('createGenericNode', {
                                type: nodeType,
                                position: flowPos,
                                sourceNodeId: null
                            });
                        }
                    }}
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
                onPaneContextMenu={onPaneContextMenu}
                onNodeContextMenu={onNodeContextMenu}
                onPaneClick={onPaneClick}
                onMoveEnd={onMoveEnd}
                defaultViewport={initialViewport}
                fitView={!initialViewport || (initialViewport.x === 0 && initialViewport.y === 0 && initialViewport.zoom === 1)}
                colorMode={colorMode}
            >
                <Background variant="dots" gap={12} size={1} />
                <Controls />
                <MiniMap />
                {menu && (
                    <ContextMenu
                        onClick={onPaneClick}
                        {...menu}
                        availableNodes={availableNodesList}
                        onAddNode={(nodeType) => {
                            if (!window.Livewire || !livewireId) return;
                            const component = window.Livewire.find(livewireId);

                            let flowPos = null;
                            if (menu && menu.screenX && menu.screenY) {
                                flowPos = screenToFlowPosition({ x: menu.screenX, y: menu.screenY });
                            }

                            let sourceNodeId = menu?.id || null;

                            if (component) {
                                component.call('createGenericNode', {
                                    type: nodeType,
                                    position: flowPos,
                                    sourceNodeId: sourceNodeId
                                });
                            }
                            setMenu(null);
                        }}
                    />
                )}
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
