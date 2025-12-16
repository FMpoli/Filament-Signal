import React, { useState } from 'react';
import NodePicker from './NodePicker';

/**
 * AddNodeButton Component
 * 
 * Contextual menu that appears when clicking "+" on a node.
 * Uses the reusable NodePicker component.
 */
const AddNodeButton = ({ sourceNodeId, position, onAddNode, availableNodes = {} }) => {
    const [isOpen, setIsOpen] = useState(false);

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
                onClick={() => setIsOpen(false)}
            />

            {/* Popover */}
            <div
                onClick={(e) => e.stopPropagation()}
                className="fixed z-50 animate-in fade-in zoom-in-95 duration-200"
                style={{
                    left: position?.x || '50%',
                    top: position?.y || '50%',
                    // Adjust transform to center if no specific position provided, or anchor to button
                    transform: position ? 'none' : 'translate(-50%, -50%)',
                }}
            >
                <NodePicker
                    availableNodes={availableNodes}
                    onSelectNode={(type) => {
                        onAddNode(type, sourceNodeId);
                        setIsOpen(false);
                    }}
                    onClose={() => setIsOpen(false)}
                    title="Add Node"
                    className="w-[600px] max-h-[80vh]"
                />
            </div>
        </>
    );
};

export default AddNodeButton;
