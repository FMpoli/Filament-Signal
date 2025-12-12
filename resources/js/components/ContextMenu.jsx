import React, { useCallback } from 'react';
import { useReactFlow } from 'reactflow';

const icons = {
    filter: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
            <path fillRule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clipRule="evenodd" />
        </svg>
    ),
    action: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clipRule="evenodd" />
        </svg>
    ),
    webhook: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
            <path fillRule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clipRule="evenodd" />
        </svg>
    ),
    trigger: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
            <path fillRule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clipRule="evenodd" />
        </svg>
    ),
};

const colorClasses = {
    purple: 'bg-purple-500',
    blue: 'bg-blue-500',
    red: 'bg-red-500',
    orange: 'bg-orange-500',
    green: 'bg-green-500',
    slate: 'bg-slate-500',
    gray: 'bg-gray-500',
};

export default function ContextMenu({ id, top, left, right, bottom, availableNodes = [], onAddNode, ...props }) {
    const { getNode, setNodes, addNodes, setEdges } = useReactFlow();

    const duplicateNode = useCallback(() => {
        const node = getNode(id);
        const position = {
            x: node.position.x + 50,
            y: node.position.y + 50,
        };

        addNodes({ ...node, id: `${node.id}-copy`, position });
        if (props.onClick) props.onClick();
    }, [id, getNode, addNodes, props.onClick]);

    const deleteNode = useCallback(() => {
        setNodes((nodes) => nodes.filter((node) => node.id !== id));
        setEdges((edges) => edges.filter((edge) => edge.source !== id && edge.target !== id));
        if (props.onClick) props.onClick();
    }, [id, setNodes, setEdges, props.onClick]);

    // Handle generic node add from dynamic list
    const handleNodeClick = (type) => {
        // Map generic type to specific handler if needed, or pass type to parent
        if (onAddNode) onAddNode(type);
    };

    return (
        <div
            style={{ top, left, right, bottom }}
            className="context-menu bg-white border border-slate-200 shadow-xl rounded-lg py-2 min-w-[200px] absolute z-50 overflow-hidden"
            {...props}
        >
            {id && (
                <>
                    <p className="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100 mb-1">
                        Node Actions
                    </p>
                    <button className="w-full text-left px-4 py-2 hover:bg-slate-50 text-slate-700 flex items-center gap-2 transition-colors" onClick={duplicateNode}>
                        <span>Duplicate</span>
                    </button>
                    <button className="w-full text-left px-4 py-2 hover:bg-red-50 text-red-600 flex items-center gap-2 transition-colors" onClick={deleteNode}>
                        <span>Delete</span>
                    </button>
                    <div className="border-t border-slate-100 my-1"></div>
                </>
            )}

            <p className="px-4 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider border-b border-slate-100 mb-1">
                Add Node
            </p>

            {availableNodes.map((node) => (
                <button
                    key={node.id}
                    className="w-full text-left px-4 py-2 hover:bg-slate-50 text-slate-700 flex items-center gap-3 transition-colors group"
                    onClick={() => handleNodeClick(node.type)}
                >
                    <span className={`
                w-6 h-6 rounded flex items-center justify-center text-white shadow-sm
                ${colorClasses[node.color] || 'bg-slate-500'}
                group-hover:scale-110 transition-transform
            `}>
                        {icons[node.icon] || icons.action}
                    </span>
                    <span className="font-medium text-sm text-slate-600 group-hover:text-slate-900">{node.label}</span>
                </button>
            ))}

            {/* Fallback/Hardcoded items if no availableNodes (e.g. at start) */}
            {availableNodes.length === 0 && (
                <div className="px-4 py-2 text-sm text-slate-400 italic">
                    No nodes available
                </div>
            )}
        </div>
    );
}
