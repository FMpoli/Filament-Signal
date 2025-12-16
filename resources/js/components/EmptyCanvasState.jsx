import React, { useState, useMemo } from 'react';
import VoodflowLogo from './VoodflowLogo';

/**
 * EmptyCanvasState Component
 * 
 * Shows an attractive call-to-action when the canvas is empty.
 * Now with horizontal tabs like ActivePieces for better UX!
 */
const EmptyCanvasState = ({ availableNodes = {}, onAddNode }) => {
    const [showNodePicker, setShowNodePicker] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [activeTab, setActiveTab] = useState('Triggers');

    // Category metadata with icons
    const categoryConfig = {
        'Triggers': { icon: '⚡', color: 'orange', emoji: '🔔' },
        'Actions': { icon: '📤', color: 'blue', emoji: '⚙️' },
        'Transform': { icon: '🔄', color: 'purple', emoji: '🔀' },
        'Flow Control': { icon: '↪️', color: 'yellow', emoji: '🔀' },
        'Other': { icon: '📦', color: 'gray', emoji: '📦' },
    };

    // Filter nodes by search query
    const filteredNodes = useMemo(() => {
        if (!availableNodes || typeof availableNodes !== 'object') {
            return {};
        }

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

    // Get categories that have nodes
    const availableCategories = Object.keys(filteredNodes).filter(cat =>
        Array.isArray(filteredNodes[cat]) && filteredNodes[cat].length > 0
    );

    // Set first available category as active if current tab is empty
    useMemo(() => {
        if (availableCategories.length > 0 && !availableCategories.includes(activeTab)) {
            setActiveTab(availableCategories[0]);
        }
    }, [availableCategories, activeTab]);

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
            tab: 'border-orange-500 text-orange-600 dark:text-orange-400',
            tabInactive: 'border-transparent text-slate-500 hover:text-orange-500 hover:border-orange-300',
            card: 'border-orange-200 dark:border-orange-800 hover:border-orange-400 dark:hover:border-orange-600',
            badge: 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
        },
        blue: {
            tab: 'border-blue-500 text-blue-600 dark:text-blue-400',
            tabInactive: 'border-transparent text-slate-500 hover:text-blue-500 hover:border-blue-300',
            card: 'border-blue-200 dark:border-blue-800 hover:border-blue-400 dark:hover:border-blue-600',
            badge: 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
        },
        purple: {
            tab: 'border-purple-500 text-purple-600 dark:text-purple-400',
            tabInactive: 'border-transparent text-slate-500 hover:text-purple-500 hover:border-purple-300',
            card: 'border-purple-200 dark:border-purple-800 hover:border-purple-400 dark:hover:border-purple-600',
            badge: 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
        },
        yellow: {
            tab: 'border-yellow-500 text-yellow-600 dark:text-yellow-400',
            tabInactive: 'border-transparent text-slate-500 hover:text-yellow-500 hover:border-yellow-300',
            card: 'border-yellow-200 dark:border-yellow-800 hover:border-yellow-400 dark:hover:border-yellow-600',
            badge: 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400',
        },
        gray: {
            tab: 'border-gray-500 text-gray-600 dark:text-gray-400',
            tabInactive: 'border-transparent text-slate-500 hover:text-gray-500 hover:border-gray-300',
            card: 'border-gray-200 dark:border-gray-800 hover:border-gray-400 dark:hover:border-gray-600',
            badge: 'bg-gray-100 dark:bg-gray-900/30 text-gray-600 dark:text-gray-400',
        },
    };

    // Get current tab nodes
    const currentNodes = filteredNodes[activeTab] || [];
    const currentConfig = categoryConfig[activeTab] || categoryConfig['Other'];
    const currentColors = colorClasses[currentConfig.color];

    return (
        <div className="absolute inset-0 flex items-center justify-center z-10 pointer-events-none">
            <div className="pointer-events-auto">
                {!showNodePicker ? (
                    /* Initial CTA */
                    <div className="text-center">
                        {/* Animated Logo */}
                        <div className="relative inline-block mb-6">
                            <div className="absolute inset-0 bg-gradient-to-r from-purple-400 to-emerald-500 rounded-full blur-xl opacity-30 animate-pulse"></div>
                            <div className="relative bg-gradient-to-br from-slate-800 to-slate-900 dark:from-slate-700 dark:to-slate-800 rounded-2xl p-8 shadow-2xl border border-slate-700">
                                <VoodflowLogo width={120} height={120} />
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
                    /* Node Picker with Horizontal Tabs */
                    <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-[650px] overflow-hidden flex flex-col">
                        {/* Header */}
                        <div className="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
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
                        <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
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
                                        w-full pl-10 pr-4 py-2.5
                                        bg-slate-50 dark:bg-slate-700/50
                                        border border-slate-200 dark:border-slate-600
                                        rounded-lg
                                        text-slate-700 dark:text-slate-200
                                        placeholder:text-slate-400
                                        focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent
                                    "
                                />
                            </div>
                        </div>

                        {/* Horizontal Tabs */}
                        <div className="border-b border-slate-200 dark:border-slate-700 px-6">
                            <div className="flex gap-1 -mb-px">
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
                                                px-4 py-3 border-b-2 font-medium text-sm
                                                transition-all duration-200
                                                flex items-center gap-2
                                                ${isActive ? colors.tab : colors.tabInactive}
                                            `}
                                        >
                                            <span className="text-lg">{config.emoji}</span>
                                            <span>{category}</span>
                                            <span className={`
                                                px-1.5 py-0.5 rounded text-xs font-semibold
                                                ${isActive ? colors.badge : 'bg-slate-100 dark:bg-slate-700 text-slate-500'}
                                            `}>
                                                {count}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Nodes Grid - NO visible scrollbar */}
                        <div className="p-6 max-h-[400px] overflow-y-auto scrollbar-hide">
                            {currentNodes.length === 0 ? (
                                <div className="text-center py-12">
                                    <p className="text-slate-400 dark:text-slate-500">
                                        No nodes found {searchQuery && `for "${searchQuery}"`}
                                    </p>
                                </div>
                            ) : (
                                <div className="grid grid-cols-2 gap-3">
                                    {currentNodes.map((node) => (
                                        <button
                                            key={node.type}
                                            onClick={() => {
                                                onAddNode(node.type);
                                                setShowNodePicker(false);
                                                setSearchQuery('');
                                            }}
                                            className={`
                                                p-4 rounded-lg text-left
                                                bg-white dark:bg-slate-700/50
                                                border-2 ${currentColors.card}
                                                transition-all duration-150
                                                hover:shadow-md hover:scale-102
                                                group
                                            `}
                                        >
                                            <div className="flex items-start gap-3">
                                                <span className="text-3xl flex-shrink-0" title={node.icon}>
                                                    {getIconForNode(node.icon)}
                                                </span>
                                                <div className="flex-1 min-w-0">
                                                    <h4 className="font-semibold text-slate-700 dark:text-slate-200 mb-1">
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
                    </div>
                )}
            </div>

            <style jsx>{`
                /* Hide scrollbar but keep functionality */
                .scrollbar-hide {
                    -ms-overflow-style: none;  /* IE and Edge */
                    scrollbar-width: none;  /* Firefox */
                }
                .scrollbar-hide::-webkit-scrollbar {
                    display: none;  /* Chrome, Safari, Opera */
                }
            `}</style>
        </div>
    );
};

export default EmptyCanvasState;
