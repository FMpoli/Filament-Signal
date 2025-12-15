import React, { useState, useMemo } from 'react';

/**
 * EmptyCanvasState Component
 * 
 * Shows an attractive call-to-action when the canvas is empty.
 * Now with categorized nodes, search, and scalable design!
 */
const EmptyCanvasState = ({ availableNodes = {}, onAddNode }) => {
    const [showNodePicker, setShowNodePicker] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [expandedCategories, setExpandedCategories] = useState(['Triggers', 'Actions']);

    // Category metadata
    const categoryConfig = {
        'Triggers': { icon: '⚡', color: 'orange', emoji: '🔔' },
        'Actions': { icon: '📤', color: 'blue', emoji: '⚙️' },
        'Transform': { icon: '🔄', color: 'purple', emoji: '🔀' },
        'Flow Control': { icon: '↪️', color: 'yellow', emoji: '🔀' },
        'Other': { icon: '📦', color: 'gray', emoji: '📦' },
    };

    // Filter nodes by search query
    const filteredNodes = useMemo(() => {
        if (!searchQuery) return availableNodes;

        const filtered = {};
        Object.entries(availableNodes).forEach(([category, nodes]) => {
            const matchingNodes = nodes.filter(node =>
                node.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                node.description?.toLowerCase().includes(searchQuery.toLowerCase()) ||
                category.toLowerCase().includes(searchQuery.toLowerCase())
            );

            if (matchingNodes.length > 0) {
                filtered[category] = matchingNodes;
            }
        });

        return filtered;
    }, [availableNodes, searchQuery]);

    // Toggle category expansion
    const toggleCategory = (category) => {
        setExpandedCategories(prev =>
            prev.includes(category)
                ? prev.filter(c => c !== category)
                : [...prev, category]
        );
    };

    // Icon mapping for nodes
    const getIconForNode = (iconName) => {
        const cleanIcon = iconName?.replace('heroicon-o-', '').replace('heroicon-', '');

        const icons = {
            'bolt': '⚡',
            'paper-airplane': '📤',
            'funnel': '🔽',
            'filter': '🔽',
            'arrows-pointing-out': '↪️',
            'cube': '📦',
        };

        return icons[cleanIcon] || '⚙️';
    };

    // Color classes for categories
    const colorClasses = {
        orange: {
            bg: 'bg-orange-500',
            bgLight: 'bg-orange-50 dark:bg-orange-900/20',
            border: 'border-orange-200 dark:border-orange-800',
            text: 'text-orange-600 dark:text-orange-400',
            hover: 'hover:bg-orange-100 dark:hover:bg-orange-900/30',
        },
        blue: {
            bg: 'bg-blue-500',
            bgLight: 'bg-blue-50 dark:bg-blue-900/20',
            border: 'border-blue-200 dark:border-blue-800',
            text: 'text-blue-600 dark:text-blue-400',
            hover: 'hover:bg-blue-100 dark:hover:bg-blue-900/30',
        },
        purple: {
            bg: 'bg-purple-500',
            bgLight: 'bg-purple-50 dark:bg-purple-900/20',
            border: 'border-purple-200 dark:border-purple-800',
            text: 'text-purple-600 dark:text-purple-400',
            hover: 'hover:bg-purple-100 dark:hover:bg-purple-900/30',
        },
        yellow: {
            bg: 'bg-yellow-500',
            bgLight: 'bg-yellow-50 dark:bg-yellow-900/20',
            border: 'border-yellow-200 dark:border-yellow-800',
            text: 'text-yellow-600 dark:text-yellow-400',
            hover: 'hover:bg-yellow-100 dark:hover:bg-yellow-900/30',
        },
        gray: {
            bg: 'bg-gray-500',
            bgLight: 'bg-gray-50 dark:bg-gray-900/20',
            border: 'border-gray-200 dark:border-gray-800',
            text: 'text-gray-600 dark:text-gray-400',
            hover: 'hover:bg-gray-100 dark:hover:bg-gray-900/30',
        },
    };

    // Total node count
    const totalNodes = Object.values(filteredNodes).reduce((sum, nodes) => sum + nodes.length, 0);

    return (
        <div className="absolute inset-0 flex items-center justify-center z-10 pointer-events-none">
            <div className="pointer-events-auto">
                {!showNodePicker ? (
                    /* Initial CTA */
                    <div className="text-center">
                        <div className="relative inline-block mb-6">
                            <div className="absolute inset-0 bg-gradient-to-r from-orange-400 to-purple-500 rounded-full blur-xl opacity-30 animate-pulse"></div>
                            <div className="relative bg-gradient-to-br from-slate-800 to-slate-900 dark:from-slate-700 dark:to-slate-800 rounded-2xl p-6 shadow-2xl border border-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="w-16 h-16 text-slate-300">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 6h3M10.5 18h3M6 10.5v3M18 10.5v3" />
                                </svg>
                            </div>
                        </div>

                        <h2 className="text-2xl font-bold text-slate-700 dark:text-slate-200 mb-2">
                            Start Building Your Workflow
                        </h2>
                        <p className="text-slate-500 dark:text-slate-400 mb-6 max-w-md">
                            Create automation rules by connecting triggers, filters, and actions together.
                        </p>

                        <button
                            onClick={() => setShowNodePicker(true)}
                            className="
                                inline-flex items-center gap-3
                                px-6 py-3
                                bg-gradient-to-r from-orange-500 to-orange-600
                                hover:from-orange-600 hover:to-orange-700
                                text-white font-semibold text-lg
                                rounded-xl
                                shadow-lg shadow-orange-500/25
                                transition-all duration-200
                                hover:scale-105
                                hover:shadow-xl hover:shadow-orange-500/30
                            "
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5">
                                <path fillRule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clipRule="evenodd" />
                            </svg>
                            Add First Node
                        </button>

                        <p className="text-xs text-slate-400 dark:text-slate-500 mt-4">
                            Tip: Start with a Trigger to respond to events automatically
                        </p>
                    </div>
                ) : (
                    /* Improved Node Picker with Categories & Search */
                    <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-[600px] max-h-[70vh] overflow-hidden flex flex-col">
                        {/* Header */}
                        <div className="flex items-center justify-between p-4 border-b border-slate-200 dark:border-slate-700">
                            <h3 className="text-lg font-bold text-slate-700 dark:text-slate-200">
                                Choose a Node Type
                            </h3>
                            <button
                                onClick={() => {
                                    setShowNodePicker(false);
                                    setSearchQuery('');
                                }}
                                className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5">
                                    <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        {/* Search Bar */}
                        <div className="p-4 border-b border-slate-200 dark:border-slate-700">
                            <div className="relative">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                    <path fillRule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clipRule="evenodd" />
                                </svg>
                                <input
                                    type="text"
                                    placeholder="🔍 Search nodes..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    className="
                                        w-full pl-10 pr-4 py-2
                                        bg-slate-50 dark:bg-slate-700
                                        border border-slate-200 dark:border-slate-600
                                        rounded-lg
                                        text-slate-700 dark:text-slate-200
                                        placeholder:text-slate-400
                                        focus:outline-none focus:ring-2 focus:ring-orange-500
                                    "
                                />
                            </div>
                            {totalNodes > 0 && (
                                <p className="text-xs text-slate-500 dark:text-slate-400 mt-2">
                                    {totalNodes} node{totalNodes !== 1 ? 's' : ''} available
                                </p>
                            )}
                        </div>

                        {/* Categories & Nodes */}
                        <div className="overflow-y-auto flex-1 p-4">
                            {Object.keys(filteredNodes).length === 0 ? (
                                <div className="text-center py-8">
                                    <p className="text-slate-400 dark:text-slate-500">
                                        No nodes found for "{searchQuery}"
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {Object.entries(filteredNodes).map(([category, nodes]) => {
                                        const config = categoryConfig[category] || categoryConfig['Other'];
                                        const colors = colorClasses[config.color];
                                        const isExpanded = expandedCategories.includes(category);

                                        return (
                                            <div key={category} className="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                                                {/* Category Header */}
                                                <button
                                                    onClick={() => toggleCategory(category)}
                                                    className={`
                                                        w-full px-4 py-2 flex items-center justify-between
                                                        ${colors.bgLight} ${colors.hover}
                                                        transition-colors
                                                    `}
                                                >
                                                    <div className="flex items-center gap-2">
                                                        <span className="text-xl">{config.emoji}</span>
                                                        <span className={`font-semibold ${colors.text}`}>
                                                            {category}
                                                        </span>
                                                        <span className="text-xs text-slate-400 dark:text-slate-500">
                                                            ({nodes.length})
                                                        </span>
                                                    </div>
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                        className={`w-5 h-5 ${colors.text} transition-transform ${isExpanded ? 'rotate-180' : ''}`}
                                                    >
                                                        <path fillRule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clipRule="evenodd" />
                                                    </svg>
                                                </button>

                                                {/* Nodes Grid */}
                                                {isExpanded && (
                                                    <div className="p-2 grid grid-cols-2 gap-2 bg-slate-50/50 dark:bg-slate-900/20">
                                                        {nodes.map((node) => (
                                                            <button
                                                                key={node.type}
                                                                onClick={() => {
                                                                    onAddNode(node.type);
                                                                    setShowNodePicker(false);
                                                                    setSearchQuery('');
                                                                }}
                                                                className={`
                                                                    p-3 rounded-lg
                                                                    bg-white dark:bg-slate-800
                                                                    border ${colors.border}
                                                                    ${colors.hover}
                                                                    transition-all duration-150
                                                                    text-left
                                                                    hover:scale-105
                                                                    group
                                                                `}
                                                            >
                                                                <div className="flex items-start gap-2">
                                                                    <span className="text-2xl" title={node.icon}>
                                                                        {getIconForNode(node.icon)}
                                                                    </span>
                                                                    <div className="flex-1 min-w-0">
                                                                        <h4 className={`font-medium text-sm ${colors.text} truncate`}>
                                                                            {node.name}
                                                                        </h4>
                                                                        <p className="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                                                            {node.description || `Add ${node.name}`}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </button>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default EmptyCanvasState;
