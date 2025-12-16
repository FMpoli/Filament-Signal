
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
import { nodeRegistry } from '../nodeRegistry';

import { loadDynamicNodeBundles } from '../utils/dynamicNodeLoader';

console.log('[NodeRegistry] Loaded nodes:', Object.keys(nodeRegistry));

// Static node types (core system nodes)
const coreNodeTypes = {
    trigger: TriggerNode,
    filter: FilterNode,
    filter_pro: FilterProNode,
    send_webhook: SendWebhookNode,
    conditional: ConditionalNode,

    // Voodflow namespace
    voodflow_trigger: TriggerNode,
    voodflow_filter: FilterNode,
    voodflow_filter_pro: FilterProNode,
    voodflow_send_webhook: SendWebhookNode,
    voodflow_conditional: ConditionalNode,

    // Legacy namespace
    base33_trigger: TriggerNode,
    base33_filter: FilterNode,
    base33_filter_pro: FilterProNode,
    base33_send_webhook: SendWebhookNode,
    base33_conditional: ConditionalNode,
};

// Fallback component for unknown node types/loading state
const FallbackNode = ({ data, type }) => (
    <div style={{
        padding: '12px',
        border: '2px dashed #9ca3af',
        borderRadius: '8px',
        background: '#f3f4f6',
        minWidth: '200px',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        gap: '8px'
    }}>
        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-600"></div>
        <div style={{ fontSize: '12px', color: '#666' }}>
            Loading {type}...
        </div>
    </div>
);

function FlowCanvas({ initialNodes, initialEdges, initialViewport, livewireId, eventOptions, filterFieldsMap, availableNodesMap }) {

    // Transform grouped categories to flat list for internal use
    // availableNodesMap is now: { "Triggers": [{node}, {node}], "Actions": [...] }
    const availableNodesList = useMemo(() => {
        if (!availableNodesMap || typeof availableNodesMap !== 'object') return [];

        // Flatten all categories into a single list
        const list = [];
        Object.entries(availableNodesMap).forEach(([category, nodes]) => {
            if (Array.isArray(nodes)) {
                nodes.forEach(node => {
                    list.push({
                        id: node.type, // Use type as unique ID
                        type: node.type,
                        label: node.name,
                        icon: (node.icon || 'circle').replace('heroicon-o-', ''),
                        color: node.color || 'gray',
                        group: category,
                        category: category,
                        metadata: {
                            description: node.description,
                            category: node.category
                        }
                    });
                });
            }
        });

        return list;
    }, [availableNodesMap]);

    // Detect theme from Filament's localStorage
    const getThemeFromFilament = useCallback(() => {
        if (typeof window === 'undefined') return 'light';

        const savedTheme = localStorage.getItem('theme');

        // If theme is 'system' or not set, use prefers-color-scheme
        if (savedTheme === 'system' || !savedTheme) {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        return savedTheme === 'dark' ? 'dark' : 'light';
    }, []);

    // Dynamic Nodes Loading
    const [dynamicNodeTypes, setDynamicNodeTypes] = useState({});

    useEffect(() => {
        loadDynamicNodeBundles().then(loadedBundles => {
            if (Object.keys(loadedBundles).length > 0) {
                setDynamicNodeTypes(loadedBundles);
            }
        });
    }, []);

    const nodeTypes = useMemo(() => {
        const types = {
            ...coreNodeTypes,
            ...nodeRegistry,
            ...dynamicNodeTypes
        };

        // Ensure every available node type has a component handler
        // If a bundle is not loaded yet or missing, use FallbackNode
        if (availableNodesMap) {
            Object.values(availableNodesMap).flat().forEach(node => {
                if (!types[node.type]) {
                    // console.warn(`[FlowEditor] Node type "${node.type}" missing component, using FallbackNode`);
                    types[node.type] = (props) => <FallbackNode type={node.type} {...props} />;
                }
            });
        }

        return types;
    }, [dynamicNodeTypes, availableNodesMap]);

    const [colorMode, setColorMode] = useState(getThemeFromFilament);

    // Watch for Filament theme changes
    // NOTE: Filament does NOT dispatch dark-mode-toggled event (verified via browser test)
    // Must use polling instead
    useEffect(() => {
        if (typeof window === 'undefined') return;

        let prevTheme = localStorage.getItem('theme');
        let intervalId;

        // Poll localStorage - only when page is visible
        const startPolling = () => {
            intervalId = setInterval(() => {
                const currentTheme = localStorage.getItem('theme');
                if (currentTheme !== prevTheme) {
                    prevTheme = currentTheme;
                    const newMode = getThemeFromFilament();
                    setColorMode(newMode);
                    console.log('[Voodflow] Theme changed to:', newMode, '(from localStorage)');
                }
            }, 200); // Fast polling for instant feedback
        };

        const stopPolling = () => {
            if (intervalId) {
                clearInterval(intervalId);
                intervalId = null;
            }
        };

        // Handle page visibility changes
        const handleVisibilityChange = () => {
            if (document.hidden) {
                stopPolling();
            } else {
                // Check immediately when page becomes visible
                const currentTheme = localStorage.getItem('theme');
                if (currentTheme !== prevTheme) {
                    prevTheme = currentTheme;
                    const newMode = getThemeFromFilament();
                    setColorMode(newMode);
                }
                startPolling();
            }
        };

        // Start polling
        startPolling();

        // Pause polling when tab not visible
        document.addEventListener('visibilitychange', handleVisibilityChange);

        // Also listen to system theme changes (when Filament theme is 'system')
        const handleMediaChange = (e) => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'system' || !savedTheme) {
                const newMode = e.matches ? 'dark' : 'light';
                setColorMode(newMode);
                console.log('[Voodflow] System theme changed to:', newMode);
            }
        };

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', handleMediaChange);
        }

        return () => {
            stopPolling();
            document.removeEventListener('visibilitychange', handleVisibilityChange);
            if (mediaQuery.removeEventListener) {
                mediaQuery.removeEventListener('change', handleMediaChange);
            }
        };
    }, [getThemeFromFilament]);


    const [nodes, setNodes, onNodesChange] = useNodesState(initialNodes.map(n => {
        const baseData = {
            ...n.data,
            livewireId,
            eventOptions,
            availableNodes: availableNodesMap // Pass categorized object, not flat array
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
                        ...nodeData.config,
                        id: nodeData.node_id,
                        nodeId: nodeData.node_id,
                        livewireId,
                        eventOptions,
                        filterFieldsMap,
                        availableNodes: availableNodesMap, // Use categorized object
                    },
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

    console.log('[FlowCanvas] isEmpty:', isEmpty, 'nodes:', nodes.length);

    return (
        <div ref={ref} style={{ width: '100%', height: '100%', position: 'relative' }}>
            {/* Empty State */}
            {isEmpty && (
                <EmptyCanvasState
                    availableNodes={availableNodesMap}
                    onAddNode={(nodeType) => {
                        if (!window.Livewire || !livewireId) return;
                        const livewire = window.Livewire.find(livewireId);
                        const position = { x: 400, y: 200 };
                        livewire && livewire.call('createGenericNode', {
                            type: nodeType,
                            position,
                            sourceNodeId: null
                        });
                    }}
                />
            )}

            <ReactFlow
                key={`react-flow-${colorMode}`}
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
                        availableNodes={availableNodesMap}
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
