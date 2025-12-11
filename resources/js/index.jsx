
import React from 'react';
import { createRoot } from 'react-dom/client';
import FlowEditor from './components/FlowEditor.jsx';

window.mountSignalFlowEditor = (container) => {
    if (!container) return;

    // Prevent double mounting
    if (container._reactRoot) {
        container._reactRoot.unmount();
    }

    let nodes = [];
    let edges = [];
    let viewport = { x: 0, y: 0, zoom: 0.7 };
    try {
        const rawNodes = container.dataset.nodes;
        const rawEdges = container.dataset.edges;
        const rawViewport = container.dataset.viewport;
        console.log('[FlowEditor Debug] Raw Nodes:', rawNodes);

        nodes = JSON.parse(rawNodes || '[]');
        edges = JSON.parse(rawEdges || '[]');
        if (rawViewport) {
            viewport = JSON.parse(rawViewport);
        }

        console.log('[FlowEditor Debug] Parsed Nodes:', nodes);
        console.log('[FlowEditor Debug] Parsed Edges:', edges);

        if (nodes.length === 0) {
            console.warn('[FlowEditor Debug] No nodes found! Grid will be empty.');
        }
    } catch (e) {
        console.error('[FlowEditor Debug] Error parsing flow data', e);
    }

    const livewireId = container.dataset.livewireId;
    const root = createRoot(container);
    container._reactRoot = root; // Store root for cleanup

    root.render(
        <FlowEditor nodes={nodes} edges={edges} viewport={viewport} livewireId={livewireId} />
    );
};

// Auto-mount if present on initial load (fallback)
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('react-flow-container');
    if (container) {
        window.mountSignalFlowEditor(container);
    }
});
