import React, { useState, useRef, useEffect } from 'react';

/**
 * ImportFlowModal Component
 * 
 * Modal for importing a workflow from JSON file
 */
const ImportFlowModal = ({ isOpen, onClose, onImport }) => {
    const [selectedFile, setSelectedFile] = useState(null);
    const [workflowData, setWorkflowData] = useState(null);
    const [error, setError] = useState(null);
    const [warnings, setWarnings] = useState([]);
    const fileInputRef = useRef(null);

    // Reset state when modal is closed
    useEffect(() => {
        if (!isOpen) {
            setSelectedFile(null);
            setWorkflowData(null);
            setError(null);
            setWarnings([]);
        }
    }, [isOpen]);

    const handleFileSelect = (event) => {
        const file = event.target.files[0];
        if (!file) return;

        setError(null);
        setWarnings([]);
        setSelectedFile(file);

        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const data = JSON.parse(e.target.result);

                // Validate workflow structure
                if (!data.nodes || !Array.isArray(data.nodes)) {
                    throw new Error('Invalid workflow format: missing nodes array');
                }

                if (!data.edges || !Array.isArray(data.edges)) {
                    throw new Error('Invalid workflow format: missing edges array');
                }

                // Check for non-core nodes
                const newWarnings = [];
                if (data.nodeAnalysis) {
                    if (data.nodeAnalysis.freeNodes && data.nodeAnalysis.freeNodes.length > 0) {
                        newWarnings.push({
                            type: 'free',
                            message: 'This workflow requires free nodes that may not be installed.',
                            nodes: data.nodeAnalysis.freeNodes
                        });
                    }

                    if (data.nodeAnalysis.paidNodes && data.nodeAnalysis.paidNodes.length > 0) {
                        newWarnings.push({
                            type: 'paid',
                            message: 'This workflow requires paid nodes that may not be installed.',
                            nodes: data.nodeAnalysis.paidNodes
                        });
                    }
                }

                setWorkflowData(data);
                setWarnings(newWarnings);
            } catch (err) {
                setError(err.message);
                setWorkflowData(null);
            }
        };

        reader.onerror = () => {
            setError('Failed to read file');
            setWorkflowData(null);
        };

        reader.readAsText(file);
    };

    const handleImport = () => {
        if (!workflowData) return;
        onImport(workflowData);
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            if (file.type === 'application/json' || file.name.endsWith('.json')) {
                // Create a fake event object for handleFileSelect
                handleFileSelect({ target: { files: [file] } });
            } else {
                setError('Please select a JSON file');
            }
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
                {/* Header */}
                <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold text-slate-800 dark:text-slate-100">
                            Import Workflow
                        </h2>
                        <button
                            onClick={onClose}
                            className="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-6 h-6">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Content */}
                <div className="flex-1 overflow-y-auto px-6 py-4">
                    {/* File Upload Area */}
                    <div
                        onDragOver={handleDragOver}
                        onDrop={handleDrop}
                        onClick={() => fileInputRef.current?.click()}
                        className="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-8 text-center cursor-pointer hover:border-orange-500 dark:hover:border-orange-400 transition-colors"
                    >
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept=".json,application/json"
                            onChange={handleFileSelect}
                            className="hidden"
                        />

                        <div className="flex flex-col items-center gap-3">
                            <div className="w-16 h-16 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-8 h-8 text-orange-600 dark:text-orange-400">
                                    <path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z" />
                                    <path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" />
                                </svg>
                            </div>

                            <div>
                                <p className="text-lg font-semibold text-slate-700 dark:text-slate-200 mb-1">
                                    {selectedFile ? selectedFile.name : 'Choose a file or drag it here'}
                                </p>
                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                    JSON workflow files only
                                </p>
                            </div>

                            {!selectedFile && (
                                <button
                                    type="button"
                                    className="mt-2 px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg transition-colors"
                                >
                                    Browse Files
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Error Display */}
                    {error && (
                        <div className="mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                            <div className="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clipRule="evenodd" />
                                </svg>
                                <div>
                                    <h4 className="font-semibold text-red-800 dark:text-red-300 mb-1">
                                        Import Error
                                    </h4>
                                    <p className="text-sm text-red-700 dark:text-red-400">
                                        {error}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Warnings Display */}
                    {warnings.length > 0 && (
                        <div className="mt-4 space-y-3">
                            {warnings.map((warning, idx) => (
                                <div key={idx} className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                                    <div className="flex items-start gap-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5">
                                            <path fillRule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clipRule="evenodd" />
                                        </svg>
                                        <div className="flex-1">
                                            <h4 className="font-semibold text-amber-800 dark:text-amber-300 mb-2">
                                                {warning.type === 'paid' ? 'Paid Nodes Required' : 'Additional Nodes Required'}
                                            </h4>
                                            <p className="text-sm text-amber-700 dark:text-amber-400 mb-2">
                                                {warning.message}
                                            </p>
                                            <ul className="text-sm text-amber-700 dark:text-amber-400 space-y-1">
                                                {warning.nodes.map((node, nodeIdx) => (
                                                    <li key={nodeIdx} className="flex items-center gap-2">
                                                        <span className="w-1.5 h-1.5 rounded-full bg-amber-600 dark:bg-amber-400"></span>
                                                        <span>{node.name}</span>
                                                        {node.installUrl && (
                                                            <a
                                                                href={node.installUrl}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className="ml-2 text-amber-800 dark:text-amber-300 underline hover:no-underline"
                                                            >
                                                                Install
                                                            </a>
                                                        )}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Workflow Preview */}
                    {workflowData && !error && (
                        <div className="mt-4 bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                            <h4 className="font-semibold text-slate-700 dark:text-slate-300 mb-3">
                                Workflow Details
                            </h4>

                            <div className="space-y-2 text-sm">
                                {workflowData.author && (
                                    <div className="flex items-center gap-2">
                                        <span className="text-slate-500 dark:text-slate-400 w-24">Author:</span>
                                        <span className="font-medium text-slate-700 dark:text-slate-200">
                                            {workflowData.author}
                                        </span>
                                    </div>
                                )}

                                {workflowData.version && (
                                    <div className="flex items-center gap-2">
                                        <span className="text-slate-500 dark:text-slate-400 w-24">Version:</span>
                                        <span className="font-medium text-slate-700 dark:text-slate-200">
                                            {workflowData.version}
                                        </span>
                                    </div>
                                )}

                                {workflowData.license && (
                                    <div className="flex items-center gap-2">
                                        <span className="text-slate-500 dark:text-slate-400 w-24">License:</span>
                                        <span className="font-medium text-slate-700 dark:text-slate-200">
                                            {workflowData.license}
                                        </span>
                                    </div>
                                )}

                                {workflowData.description && (
                                    <div className="flex gap-2 pt-2 border-t border-slate-200 dark:border-slate-600 mt-2">
                                        <span className="text-slate-500 dark:text-slate-400 w-24 flex-shrink-0">Description:</span>
                                        <span className="text-slate-700 dark:text-slate-200">
                                            {workflowData.description}
                                        </span>
                                    </div>
                                )}

                                <div className="grid grid-cols-2 gap-3 pt-2 border-t border-slate-200 dark:border-slate-600 mt-2">
                                    <div>
                                        <span className="text-slate-500 dark:text-slate-400">Nodes:</span>
                                        <span className="ml-2 font-medium text-slate-700 dark:text-slate-200">
                                            {workflowData.nodes.length}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-slate-500 dark:text-slate-400">Connections:</span>
                                        <span className="ml-2 font-medium text-slate-700 dark:text-slate-200">
                                            {workflowData.edges.length}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Footer */}
                <div className="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        onClick={onClose}
                        className="px-4 py-2 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        onClick={handleImport}
                        disabled={!workflowData || error}
                        className="px-6 py-2 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-semibold rounded-lg shadow-lg shadow-orange-500/25 transition-all duration-200 hover:shadow-xl hover:shadow-orange-500/30 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:from-orange-500 disabled:hover:to-orange-600"
                    >
                        Import Workflow
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ImportFlowModal;
