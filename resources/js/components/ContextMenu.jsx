import React, { useCallback } from 'react';
import { useReactFlow } from 'reactflow';
import NodePicker from './NodePicker';

/**
 * ContextMenu Component
 * 
 * Right-click contextual menu.
 * - If clicking a node: Shows node actions (Duplicate, Delete).
 * - If clicking canvas: Shows the NodeSelector.
 */
export default function ContextMenu({ id, top, left, right, bottom, availableNodes = {}, onAddNode, ...props }) {
    const { getNode, setNodes, addNodes, setEdges } = useReactFlow();

    const duplicateNode = useCallback(() => {
        const node = getNode(id);
        const position = {
            x: node.position.x + 50,
            y: node.position.y + 50,
        };
        addNodes({ ...node, id: `${node.id}-copy`, position });
        if (props.onClick) props.onClick();
    }, [id, getNode, addNodes, props]);

    const deleteNode = useCallback(() => {
        setNodes((nodes) => nodes.filter((node) => node.id !== id));
        setEdges((edges) => edges.filter((edge) => edge.source !== id && edge.target !== id));
        if (props.onClick) props.onClick();
    }, [id, setNodes, setEdges, props]);

    // If clicking on a node, show simple actions menu
    if (id) {
        return (
            <div
                style={{ top, left, right, bottom }}
                className="absolute z-50 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl rounded-lg overflow-hidden min-w-[200px]"
                {...props}
            >
                <div className="px-3 py-2 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                    <p className="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        Node Actions
                    </p>
                </div>
                <div className="p-1">
                    <button
                        className="w-full text-left px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center gap-2 transition-colors text-sm rounded-md"
                        onClick={duplicateNode}
                    >
                        <span>📋</span>
                        <span>Duplicate</span>
                    </button>
                    <button
                        className="w-full text-left px-3 py-2 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 flex items-center gap-2 transition-colors text-sm rounded-md"
                        onClick={deleteNode}
                    >
                        <span>🗑️</span>
                        <span>Delete</span>
                    </button>
                </div>
            </div>
        );
    }

    // If clicking on canvas, show NodePicker
    return (
        <div
            style={{ top, left, right, bottom }}
            className="absolute z-50"
            onClick={(e) => e.stopPropagation()}
        >
            <NodePicker
                availableNodes={availableNodes}
                onSelectNode={(type) => {
                    onAddNode(type);
                    if (props.onClick) props.onClick();
                }}
                onClose={props.onClick}
                title="Add to Flow"
                className="w-[600px] max-h-[80vh]"
            />
        </div>
    );
}
