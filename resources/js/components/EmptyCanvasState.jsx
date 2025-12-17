import React, { useState } from 'react';
import VoodflowLogo from './VoodflowLogo';
import NodePicker from './NodePicker';

/**
 * EmptyCanvasState Component
 * 
 * Shows an attractive call-to-action when the canvas is empty.
 * Uses NodePicker for selection.
 */
const EmptyCanvasState = ({ availableNodes = {}, onAddNode, onImport }) => {
    const [showNodePicker, setShowNodePicker] = useState(false);

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

                        <div className="flex items-center gap-3">
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

                            <button
                                onClick={onImport}
                                className="
                                    inline-flex items-center gap-3
                                    px-6 py-3
                                    bg-slate-100 dark:bg-slate-700
                                    hover:bg-slate-200 dark:hover:bg-slate-600
                                    text-slate-700 dark:text-slate-200 font-semibold text-lg
                                    rounded-xl
                                    border-2 border-slate-300 dark:border-slate-600
                                    shadow-lg
                                    transition-all duration-200
                                    hover:scale-105
                                "
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5">
                                    <path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z" />
                                    <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                                </svg>
                                Import Workflow
                            </button>
                        </div>

                        <p className="text-xs text-slate-400 dark:text-slate-500 mt-4">
                            Tip: Start with a Trigger to respond to events automatically
                        </p>

                    </div>
                ) : (
                    /* Node Picker */
                    <div className="w-[650px] animate-in fade-in slide-in-from-bottom-4 duration-300">
                        <NodePicker
                            availableNodes={availableNodes}
                            onSelectNode={(type) => {
                                onAddNode(type);
                                setShowNodePicker(false);
                            }}
                            onClose={() => setShowNodePicker(false)}
                        />
                    </div>
                )}
            </div>
        </div>
    );
};

export default EmptyCanvasState;


