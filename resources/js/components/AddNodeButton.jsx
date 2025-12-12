import React, { useState, useRef, useEffect } from 'react';

/**
 * AddNodeButton Component
 * 
 * A floating "+" button that appears near nodes with output handles.
 * When clicked, shows a dropdown menu to select which node type to add.
 */
const AddNodeButton = ({
    onAddNode,
    position = { x: 0, y: 0 },
    availableNodes = [],
    sourceNodeId,
    livewireId,
    color = 'slate'
}) => {
    const [isOpen, setIsOpen] = useState(false);
    const menuRef = useRef(null);

    // Button color mapping
    const buttonColors = {
        purple: 'bg-purple-600 hover:bg-purple-500',
        blue: 'bg-blue-600 hover:bg-blue-500',
        red: 'bg-red-600 hover:bg-red-500',
        orange: 'bg-orange-600 hover:bg-orange-500',
        green: 'bg-green-600 hover:bg-green-500',
        slate: 'bg-slate-600 hover:bg-slate-500',
    };

    const buttonClass = buttonColors[color] || buttonColors.slate;

    // Close menu when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (menuRef.current && !menuRef.current.contains(event.target)) {
                setIsOpen(false);
            }
        };

        if (isOpen) {
            document.addEventListener('mousedown', handleClickOutside);
        }

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [isOpen]);

    const handleAddNode = (nodeType) => {
        setIsOpen(false);
        if (onAddNode) {
            onAddNode(nodeType, sourceNodeId);
        }
    };

    // Default available nodes
    const defaultNodes = [
        { type: 'filter', label: 'Filter', icon: 'filter', color: 'purple' },
        { type: 'action', label: 'Action', icon: 'action', color: 'blue' },
        { type: 'sendWebhook', label: 'Send Webhook', icon: 'webhook', color: 'red' },
    ];

    const nodes = availableNodes.length > 0 ? availableNodes : defaultNodes;

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
        purple: 'bg-purple-500 hover:bg-purple-600',
        blue: 'bg-blue-500 hover:bg-blue-600',
        red: 'bg-red-500 hover:bg-red-600',
        orange: 'bg-orange-500 hover:bg-orange-600',
        green: 'bg-green-500 hover:bg-green-600',
    };

    return (
        <div
            ref={menuRef}
            className="relative nodrag nopan"
            style={{ zIndex: 10 }}
        >
            {/* Plus Button */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                className={`
                    w-6 h-6 rounded-full
                    ${buttonClass}
                    text-white
                    flex items-center justify-center
                    shadow-lg
                    transition-all duration-200
                    hover:scale-110
                    ${isOpen ? 'ring-2 ring-offset-1 ring-slate-400 rotate-45' : ''}
                `}
                title="Add connected node"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
                    <path fillRule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clipRule="evenodd" />
                </svg>
            </button>

            {/* Dropdown Menu */}
            {isOpen && (
                <div
                    className={`
                        absolute left-8 top-0
                        bg-white dark:bg-slate-800
                        border border-slate-200 dark:border-slate-700
                        rounded-lg shadow-xl
                        min-w-[180px]
                        py-1
                        z-50
                    `}
                >
                    <div className="px-3 py-2 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-700">
                        Add Node
                    </div>
                    {nodes.map((node) => (
                        <button
                            key={node.type}
                            onClick={() => handleAddNode(node.type)}
                            className={`
                                w-full px-3 py-2
                                flex items-center gap-2
                                text-sm text-slate-700 dark:text-slate-200
                                hover:bg-slate-100 dark:hover:bg-slate-700
                                transition-colors
                                text-left
                            `}
                        >
                            <span className={`
                                w-6 h-6 rounded flex items-center justify-center text-white
                                ${colorClasses[node.color] || 'bg-slate-500'}
                            `}>
                                {icons[node.icon] || icons.action}
                            </span>
                            <span>{node.label}</span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
};

export default AddNodeButton;
