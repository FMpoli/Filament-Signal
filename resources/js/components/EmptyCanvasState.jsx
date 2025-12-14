import React, { useState } from 'react';

/**
 * EmptyCanvasState Component
 * 
 * Shows an attractive call-to-action when the canvas is empty.
 * Allows users to start a new flow by adding a trigger.
 */
const EmptyCanvasState = ({ availableNodes = [], onAddNode }) => {
    const [showNodePicker, setShowNodePicker] = useState(false);

    // Icon mapping for common node icons
    const getIconForNode = (iconName) => {
        const icons = {
            'bolt': (
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-8 h-8">
                    <path fillRule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clipRule="evenodd" />
                </svg>
            ),
            'funnel': (
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-8 h-8">
                    <path fillRule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clipRule="evenodd" />
                </svg>
            ),
            'filter': (
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-8 h-8">
                    <path fillRule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clipRule="evenodd" />
                </svg>
            ),
            'circle': (
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-8 h-8">
                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clipRule="evenodd" />
                </svg>
            ),
        };
        return icons[iconName] || icons['circle'];
    };

    // Transform availableNodes to nodeOptions format
    const nodeOptions = availableNodes.map(node => ({
        type: node.type,
        label: node.label,
        description: node.metadata?.description || `Add a ${node.label} node`,
        icon: getIconForNode(node.icon),
        color: node.color,
        onClick: () => onAddNode(node.type),
        recommended: node.positioning?.recommended || false,
    }));

    const colorClasses = {
        orange: {
            bg: 'bg-orange-500',
            bgLight: 'bg-orange-100 dark:bg-orange-900/30',
            border: 'border-orange-500',
            text: 'text-orange-500',
            hover: 'hover:bg-orange-50 dark:hover:bg-orange-900/20',
            ring: 'ring-orange-500',
        },
        purple: {
            bg: 'bg-purple-500',
            bgLight: 'bg-purple-100 dark:bg-purple-900/30',
            border: 'border-purple-500',
            text: 'text-purple-500',
            hover: 'hover:bg-purple-50 dark:hover:bg-purple-900/20',
            ring: 'ring-purple-500',
        },
        blue: {
            bg: 'bg-blue-500',
            bgLight: 'bg-blue-100 dark:bg-blue-900/30',
            border: 'border-blue-500',
            text: 'text-blue-500',
            hover: 'hover:bg-blue-50 dark:hover:bg-blue-900/20',
            ring: 'ring-blue-500',
        },
        warning: {
            bg: 'bg-amber-500',
            bgLight: 'bg-amber-100 dark:bg-amber-900/30',
            border: 'border-amber-500',
            text: 'text-amber-500',
            hover: 'hover:bg-amber-50 dark:hover:bg-amber-900/20',
            ring: 'ring-amber-500',
        },
        gray: {
            bg: 'bg-gray-500',
            bgLight: 'bg-gray-100 dark:bg-gray-900/30',
            border: 'border-gray-500',
            text: 'text-gray-500',
            hover: 'hover:bg-gray-50 dark:hover:bg-gray-900/20',
            ring: 'ring-gray-500',
        },
    };

    return (
        <div className="absolute inset-0 flex items-center justify-center z-10 pointer-events-none">
            <div className="pointer-events-auto">
                {!showNodePicker ? (
                    /* Initial CTA */
                    <div className="text-center">
                        {/* Animated Icon */}
                        <div className="relative inline-block mb-6">
                            <div className="absolute inset-0 bg-gradient-to-r from-orange-400 to-purple-500 rounded-full blur-xl opacity-30 animate-pulse"></div>
                            <div className="relative bg-gradient-to-br from-slate-800 to-slate-900 dark:from-slate-700 dark:to-slate-800 rounded-2xl p-6 shadow-2xl border border-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="w-16 h-16 text-slate-300">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 6h3M10.5 18h3M6 10.5v3M18 10.5v3" />
                                </svg>
                            </div>
                        </div>

                        {/* Title */}
                        <h2 className="text-2xl font-bold text-slate-700 dark:text-slate-200 mb-2">
                            Start Building Your Workflow
                        </h2>
                        <p className="text-slate-500 dark:text-slate-400 mb-6 max-w-md">
                            Create automation rules by connecting triggers, filters, and actions together.
                        </p>

                        {/* Main CTA Button */}
                        <button
                            onClick={() => setShowNodePicker(true)}
                            className={`
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
                            `}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5">
                                <path fillRule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clipRule="evenodd" />
                            </svg>
                            Add First Node
                        </button>

                        {/* Quick Hint */}
                        <p className="text-xs text-slate-400 dark:text-slate-500 mt-4">
                            Tip: Start with a Trigger to respond to events automatically
                        </p>
                    </div>
                ) : (
                    /* Node Picker */
                    <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 p-6 max-w-xl">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-bold text-slate-700 dark:text-slate-200">
                                Choose a Node Type
                            </h3>
                            <button
                                onClick={() => setShowNodePicker(false)}
                                className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5">
                                    <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div className="space-y-3">
                            {nodeOptions.map((option) => {
                                const colors = colorClasses[option.color] || colorClasses.gray; // Fallback to gray
                                return (
                                    <button
                                        key={option.type}
                                        onClick={option.onClick}
                                        className={`
                                            w-full p-4 rounded-xl
                                            border-2 ${option.recommended ? colors.border : 'border-slate-200 dark:border-slate-600'}
                                            ${colors.hover}
                                            transition-all duration-200
                                            text-left
                                            group
                                            relative
                                            hover:border-current hover:${colors.text}
                                        `}
                                    >
                                        {option.recommended && (
                                            <span className={`
                                                absolute -top-2 right-3
                                                px-2 py-0.5
                                                ${colors.bg}
                                                text-white text-xs font-bold
                                                rounded-full
                                            `}>
                                                Recommended
                                            </span>
                                        )}
                                        <div className="flex items-center gap-4">
                                            <div className={`
                                                p-3 rounded-xl
                                                ${colors.bgLight}
                                                ${colors.text}
                                            `}>
                                                {option.icon}
                                            </div>
                                            <div>
                                                <h4 className={`font-semibold text-slate-700 dark:text-slate-200 group-hover:${colors.text}`}>
                                                    {option.label}
                                                </h4>
                                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                                    {option.description}
                                                </p>
                                            </div>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default EmptyCanvasState;
