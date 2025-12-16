import React, { useState, useMemo } from 'react';

/**
 * AddNodeButton Component
 * 
 * Contextual menu that appears when clicking "+" on a node.
 * Now with the same categorized tab UI as EmptyCanvasState!
 */
const AddNodeButton = ({ sourceNodeId, position, onAddNode, availableNodes = {} }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [activeTab, setActiveTab] = useState('Actions');

    // Category metadata
    const categoryConfig = {
        'Triggers': { icon: '⚡', color: 'orange', emoji: '🔔' },
        'Actions': { icon: '📤', color: 'blue', emoji: '⚙️' },
        'Transform': { icon: '🔄', color: 'purple', emoji: '🔀' },
        'Flow Control': { icon: '↪️', color: 'yellow', emoji: '🔀' },
        'Other': { icon: '📦', color: 'gray', emoji: '📦' },
    };

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
        orange: {
            tab: 'border-orange-500 text-orange-600 dark:text-orange-400',
            tabInactive: 'border-transparent text-slate-500 hover:text-orange-500',
            card: 'border-orange-200 hover:border-orange-400',
        },
        blue: {
            tab: 'border-blue-500 text-blue-600 dark:text-blue-400',
            tabInactive: 'border-transparent text-slate-500 hover:text-blue-500',
            card: 'border-blue-200 hover:border-blue-400',
        },
        purple: {
            tab: 'border-purple-500 text-purple-600 dark:text-purple-400',
            tabInactive: 'border-transparent text-slate-500 hover:text-purple-500',
            card: 'border-purple-200 hover:border-purple-400',
        },
        yellow: {
            tab: 'border-yellow-500 text-yellow-600 dark:text-yellow-400',
            tabInactive: 'border-transparent text-slate-500 hover:text-yellow-500',
            card: 'border-yellow-200 hover:border-yellow-400',
        },
        gray: {
            tab: 'border-gray-500 text-gray-600 dark:text-gray-400',
            tabInactive: 'border-transparent text-slate-500 hover:text-gray-500',
            card: 'border-gray-200 hover:border-gray-400',
        },
    };

    const currentNodes = filteredNodes[activeTab] || [];
    const currentConfig = categoryConfig[activeTab] || categoryConfig['Other'];
    const currentColors = colorClasses[currentConfig.color];

    if (!isOpen) {
        return (
            <button
                onClick={() => setIsOpen(true)}
                className="
                    absolute -bottom-6 left-1/2 -translate-x-1/2
                    w-8 h-8 rounded-full
                    bg-orange-500 hover:bg-orange-600
                    text-white shadow-lg
                    flex items-center justify-center
                    transition-all duration-200
                    hover:scale-110
                    z-10
                "
                title="Add Node"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5">
                    <path fillRule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clipRule="evenodd" />
                </svg>
            </button>
        );
    }

    return (
        <>
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-black/20 z-40"
                onClick={() => {
                    setIsOpen(false);
                    setSearchQuery('');
                }}
            />

            {/* Menu */}
            <div
                onClick={(e) => e.stopPropagation()}
                className="
                    fixed z-50
                    bg-white dark:bg-slate-800 
                    rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700
                    w-[500px] max-h-[70vh]
                    flex flex-col overflow-hidden
                "
                style={{
                    left: `${position?.x || 0}px`,
                    top: `${position?.y || 0}px`,
                }}
            >
                {/* Header */}
                <div className="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                    <h4 className="font-semibold text-slate-700 dark:text-slate-200">Add Node</h4>
                    <button
                        onClick={() => {
                            setIsOpen(false);
                            setSearchQuery('');
                        }}
                        className="text-slate-400 hover:text-slate-600 transition-colors"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5">
                            <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                        </svg>
                    </button>
                </div>

                {/* Search */}
                <div className="px-4 py-3 border-b border-slate-200 dark:border-slate-700">
                    <div className="relative">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <path fillRule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clipRule="evenodd" />
                        </svg>
                        <input
                            type="text"
                            placeholder="Search nodes..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="
                                w-full pl-9 pr-3 py-2
                                bg-slate-50 dark:bg-slate-700/50
                                border border-slate-200 dark:border-slate-600
                                rounded-lg text-sm
                                text-slate-700 dark:text-slate-200
                                placeholder:text-slate-400
                                focus:outline-none focus:ring-2 focus:ring-orange-500
                            "
                        />
                    </div>
                </div>

                {/* Tabs */}
                <div className="border-b border-slate-200 dark:border-slate-700 px-3">
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
                                        px-2.5 py-2 border-b-2 font-medium text-xs
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

                {/* Nodes List */}
                <div className="p-3 max-h-[400px] overflow-y-auto scrollbar-hide">
                    {currentNodes.length === 0 ? (
                        <div className="text-center py-8">
                            <p className="text-slate-400 text-sm">No nodes found</p>
                        </div>
                    ) : (
                        <div className="space-y-1">
                            {currentNodes.map((node) => {
                                // Tier badge configuration
                                const tierColors = {
                                    'CORE': 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600',
                                    'PRO': 'bg-gradient-to-r from-purple-500 to-pink-500 text-white border-0',
                                    'FREE': 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-300',
                                };
                                const tierClass = tierColors[node.tier] || tierColors['FREE'];

                                return (
                                    <button
                                        key={node.type}
                                        onClick={() => {
                                            onAddNode(node.type, sourceNodeId);
                                            setIsOpen(false);
                                            setSearchQuery('');
                                        }}
                                        className={`
                                            w-full p-3 rounded-lg text-left
                                            bg-white dark:bg-slate-700/50
                                            border ${currentColors.card}
                                            transition-all duration-150
                                            hover:shadow-md
                                        `}
                                    >
                                        <div className="flex items-center gap-2">
                                            <span className="text-2xl">{getIconForNode(node.icon)}</span>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center justify-between gap-2 mb-0.5">
                                                    <h5 className="font-medium text-sm text-slate-700 dark:text-slate-200">
                                                        {node.name}
                                                    </h5>
                                                    <span className={`
                                                        px-1.5 py-0.5 rounded text-xs font-bold
                                                        flex-shrink-0 ${tierClass}
                                                    `}>
                                                        {node.tier || 'FREE'}
                                                    </span>
                                                </div>
                                                <p className="text-xs text-slate-500 dark:text-slate-400 truncate mb-0.5">
                                                    {node.description || `Add ${node.name}`}
                                                </p>
                                                <p className="text-xs text-slate-400 dark:text-slate-500 italic">
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
        </>
    );
};

export default AddNodeButton;
