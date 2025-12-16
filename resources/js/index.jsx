import React from 'react';
import * as ReactDOM from 'react-dom/client';
import * as ReactFlow from 'reactflow';
import FlowEditor from './components/FlowEditor.jsx';

// Expose React globals for dynamic nodes (which must be built with externals)
window.React = React;
window.ReactDOM = ReactDOM;
window.ReactFlow = ReactFlow;

window.mountSignalFlowEditor = (container) => {
    if (!container) return;

    // Prevent double mounting
    if (container._reactRoot) {
        container._reactRoot.unmount();
    }

    let nodes = [];
    let edges = [];
    let viewport = { x: 0, y: 0, zoom: 0.7 };
    let eventOptions = {};
    let filterFieldsMap = {};
    let availableNodes = [];
    try {
        const rawNodes = container.dataset.nodes;
        const rawEdges = container.dataset.edges;
        const rawViewport = container.dataset.viewport;
        const rawEventOptions = container.dataset.eventOptions;
        const rawFilterFieldsMap = container.dataset.filterFieldsMap;
        const rawAvailableNodes = container.dataset.availableNodes;

        console.log('[FlowEditor Debug] Raw Nodes:', rawNodes);

        nodes = JSON.parse(rawNodes || '[]');
        edges = JSON.parse(rawEdges || '[]');
        if (rawViewport) {
            viewport = JSON.parse(rawViewport);
        }
        if (rawEventOptions) {
            eventOptions = JSON.parse(rawEventOptions);
        }
        if (rawFilterFieldsMap) {
            filterFieldsMap = JSON.parse(rawFilterFieldsMap);
        }
        if (rawAvailableNodes) {
            availableNodes = JSON.parse(rawAvailableNodes);
            // Convert metadata map (object) to array if needed for UI list
            // Or keep as object. The FlowEditor expects array for AddNodeButton?
            // AddNodeButton expects: [{type, label, icon, color} ...]

            // The backend sends map: key => { name, type, metadata: { ... } }
            // We'll process this inside FlowEditor or here.
            // Let's pass it raw or transformed.
            // Let's transform here to match AddNodeButton expectation for specific usage,
            // BUT keeping full metadata is better.
            // Let's just pass the raw object/array.
        }

        console.log('[FlowEditor Debug] Parsed Nodes:', nodes);
        console.log('[FlowEditor Debug] Available Nodes:', availableNodes);

    } catch (e) {
        console.error('[FlowEditor Debug] Error parsing flow data', e);
    }

    const livewireId = container.dataset.livewireId;
    const root = ReactDOM.createRoot(container);
    container._reactRoot = root; // Store root for cleanup

    root.render(
        <FlowEditor
            nodes={nodes}
            edges={edges}
            viewport={viewport}
            livewireId={livewireId}
            eventOptions={eventOptions}
            filterFieldsMap={filterFieldsMap}
            availableNodesMap={availableNodes}
        />
    );
};

// Auto-mount if present on initial load (fallback)
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('react-flow-container');
    if (container) {
        window.mountSignalFlowEditor(container);
    }
});
