import React, { useState, useMemo, useCallback } from 'react';
import { useReactFlow } from 'reactflow';

/**
 * ContextMenu Component
 * 
 * Right-click contextual menu with categorized node selector.
 * Matches the same UI as AddNodeButton for consistency.
 */
export default function ContextMenu({ id, top, left, right, bottom, availableNodes = {}, onAddNode, ...props }) {
    const { getNode, setNodes, addNodes, setEdges } = useReactFlow();
    const [searchQuery, setSearchQuery] = useState('');
    const [activeTab, setActiveTab] = useState('Actions');

    // Category metadata
    const categoryConfig = {
        'Triggers': { emoji: '🔔', color: 'orange' },
        'Actions': { emoji: '⚙️', color: 'blue' },
        'Transform': { emoji: '🔀', color: 'purple' },
        'Flow Control': { emoji: '🔀', color: 'yellow' },
        'Other': { emoji: '📦', color: 'gray' },
    };

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

    // Filter nodes by search
    const filteredNodes = useMemo(() => {
        if (!availableNodes || typeof availableNodes !== 'object') return {};
        if (!searchQuery) return availableNodes;

        const filtered = {};
        Object.entries(availableNodes).forEach(([category, nodes]) => {
            if (!Array.isArray(nodes)) return;

            const matchingNodes = nodes.filter(node =>
                node.name?.toLowerCase().includes(searchQuery.toLowerCase()) ||
                node.description?.toLowerCase().includes(searchQuery.toLowerCase())
            );

            if (matchingNodes.length > 0) {
                filtered[category] = matchingNodes;
            }
        });

        return filtered;
    }, [availableNodes, searchQuery]);

    const availableCategories = Object.keys(filteredNodes).filter(cat =>
        Array.isArray(filteredNodes[cat]) && filteredNodes[cat].length > 0
    );

    // Auto-switch to first available category
    useMemo(() => {
        if (availableCategories.length > 0 && !availableCategories.includes(activeTab)) {
            setActiveTab(availableCategories[0]);
        }
    }, [availableCategories, activeTab]);

    const getIconForNode = (iconName) => {
        const cleanIcon = iconName?.replace('heroicon-o-', '').replace('heroicon-', '');
        const icons = {
            'bolt': '⚡', 'paper-airplane': '📤', 'funnel': '🔽',
            'filter': '🔽', 'arrows-pointing-out': '↪️', 'cube': '📦',
        };
        return icons[cleanIcon] || '⚙️';
    };

    const colorClasses = {
        orange: { tab: 'border-orange-500 text-orange-600 dark:text-orange-400', tabInactive: 'text-slate-500 hover:text-orange-500', card: 'border-orange-200 hover:border-orange-400' },
        blue: { tab: 'border-blue-500 text-blue-600 dark:text-blue-400', tabInactive: 'text-slate-500 hover:text-blue-500', card: 'border-blue-200 hover:border-blue-400' },
        purple: { tab: 'border-purple-500 text-purple-600 dark:text-purple-400', tabInactive: 'text-slate-500 hover:text-purple-500', card: 'border-purple-200 hover:border-purple-400' },
        yellow: { tab: 'border-yellow-500 text-yellow-600 dark:text-yellow-400', tabInactive: 'text-slate-500 hover:text-yellow-500', card: 'border-yellow-200 hover:border-yellow-400' },
        gray: { tab: 'border-gray-500 text-gray-600 dark:text-gray-400', tabInactive: 'text-slate-500 hover:text-gray-500', card: 'border-gray-200 hover:border-gray-400' },
    };

    const currentNodes = filteredNodes[activeTab] || [];
    const currentConfig = categoryConfig[activeTab] || categoryConfig['Other'];
    const currentColors = colorClasses[currentConfig.color];

    const tierColors = {
        'CORE': 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300',
        'PRO': 'bg-gradient-to-r from-purple-500 to-pink-500 text-white border-0',
        'FREE': 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-300',
    };

    return (
        <div
            style={{ top, left, right, bottom }}
            className="
                bg-white dark:bg-slate-800 
                border border-slate-200 dark:border-slate-700
                shadow-2xl rounded-lg 
                min-w-[450px] max-h-[500px]
                overflow-hidden flex flex-col
                absolute z-50
            "
            {...props}
        >
            {/* Node Actions (only if node is right-clicked) */}
            {id && (
                <>
                    <div className="px-3 py-2 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                        <p className="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            Node Actions
                        </p>
                    </div>
                    <div className="border-b border-slate-200 dark:border-slate-700">
                        <button
                            className="w-full text-left px-3 py-2 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center gap-2 transition-colors text-sm"
                            onClick={duplicateNode}
                        >
                            <span>📋</span>
                            <span>Duplicate</span>
                        </button>
                        <button
                            className="w-full text-left px-3 py-2 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 flex items-center gap-2 transition-colors text-sm"
                            onClick={deleteNode}
                        >
                            <span>🗑️</span>
                            <span>Delete</span>
                        </button>
                    </div>
                </>
            )}

            {/* Header */}
            <div className="px-3 py-2 border-b border-slate-200 dark:border-slate-700">
                <p className="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    Add Node
                </p>
            </div>

            {/* Search */}
            <div className="px-3 py-2 border-b border-slate-200 dark:border-slate-700">
                <div className="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4 absolute left-2 top-1/2 -translate-y-1/2 text-slate-400">
                        <path fillRule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clipRule="evenodd" />
                    </svg>
                    <input
                        type="text"
                        placeholder="Search..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="
                            w-full pl-8 pr-2 py-1.5
                            bg-slate-50 dark:bg-slate-700/50
                            border border-slate-200 dark:border-slate-600
                            rounded text-xs
                            text-slate-700 dark:text-slate-200
                            placeholder:text-slate-400
                            focus:outline-none focus:ring-2 focus:ring-orange-500
                        "
                    />
                </div>
            </div>

            {/* Tabs */}
            <div className="border-b border-slate-200 dark:border-slate-700 px-2">
                <div className="flex gap-0.5 -mb-px overflow-x-auto scrollbar-hide">
                    {availableCategories.map((category) => {
                        const config = categoryConfig[category] || categoryConfig['Other'];
                        const colors = colorClasses[config.color];
                        const isActive = activeTab === category;
                        const count = filteredNodes[category]?.length || 0;

                        return (
                            <button
                                key={category}
                                onClick={() => setActiveTab(category)}
                                className={`
                                    px-2 py-1.5 border-b-2 font-medium text-xs
                                    transition-all duration-200
                                    flex items-center gap-1 whitespace-nowrap
                                    ${isActive ? colors.tab : colors.tabInactive}
                                `}
                            >
                                <span className="text-sm">{config.emoji}</span>
                                <span>{category}</span>
                                <span className="px-1 py-0.5 rounded text-xs bg-slate-100 dark:bg-slate-700 text-slate-500">
                                    {count}
                                </span>
                            </button>
                        );
                    })}
                </div>
            </div>

            {/* Nodes */}
            <div className="p-2 max-h-[300px] overflow-y-auto scrollbar-hide">
                {currentNodes.length === 0 ? (
                    <div className="text-center py-6">
                        <p className="text-slate-400 text-xs">No nodes found</p>
                    </div>
                ) : (
                    <div className="space-y-1">
                        {currentNodes.map((node) => {
                            const tierClass = tierColors[node.tier] || tierColors['FREE'];

                            return (
                                <button
                                    key={node.type}
                                    onClick={() => {
                                        onAddNode(node.type);
                                        if (props.onClick) props.onClick();
                                    }}
                                    className={`
                                        w-full p-2 rounded text-left
                                        bg-white dark:bg-slate-700/50
                                        border ${currentColors.card}
                                        transition-all duration-150
                                        hover:shadow-md
                                    `}
                                >
                                    <div className="flex items-center gap-2">
                                        <span className="text-xl">{getIconForNode(node.icon)}</span>
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center justify-between gap-1 mb-0.5">
                                                <h6 className="font-medium text-xs text-slate-700 dark:text-slate-200">
                                                    {node.name}
                                                </h6>
                                                <span className={`px-1 py-0.5 rounded text-xs font-bold flex-shrink-0 ${tierClass}`}>
                                                    {node.tier || 'FREE'}
                                                </span>
                                            </div>
                                            <p className="text-xs text-slate-500 dark:text-slate-400 truncate">
                                                {node.description || `Add ${node.name}`}
                                            </p>
                                            <p className="text-xs text-slate-400 dark:text-slate-500 italic truncate">
                                                by {node.author || 'Unknown'}
                                            </p>
                                        </div>
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                )}
            </div>

            <style jsx>{`
                .scrollbar-hide {
                    -ms-overflow-style: none;
                    scrollbar-width: none;
                }
                .scrollbar-hide::-webkit-scrollbar {
                    display: none;
                }
            `}</style>
        </div>
    );
}
