import React, { useState, useEffect } from 'react';

/**
 * ExportFlowModal Component
 * 
 * Modal for exporting a workflow with metadata customization
 */
const ExportFlowModal = ({ isOpen, onClose, workflowData, onExport, availableNodesMap }) => {
    const [formData, setFormData] = useState({
        author: '',
        license: 'MIT',
        description: '',
        version: '1.0.0'
    });

    const [nodeAnalysis, setNodeAnalysis] = useState({
        coreNodes: [],
        freeNodes: [],
        proNodes: [],
        paidNodes: [],
        missingNodes: []
    });

    useEffect(() => {
        if (isOpen && workflowData) {
            // Pre-fill with workflow data
            setFormData({
                author: workflowData.author || '',
                license: workflowData.license || 'MIT',
                description: workflowData.description || '',
                version: workflowData.version || '1.0.0'
            });

            // Analyze nodes
            analyzeNodes(workflowData.nodes || []);
        }
    }, [isOpen, workflowData]);

    // Calculate unique connections (edges might have duplicates)
    const getUniqueEdgesCount = (edges) => {
        if (!edges || !Array.isArray(edges)) return 0;

        // Create a Set of unique edge identifiers
        const uniqueEdges = new Set();
        edges.forEach(edge => {
            // Create a unique key based on source, target, and handles
            const key = `${edge.source}-${edge.sourceHandle || 'default'}-${edge.target}-${edge.targetHandle || 'default'}`;
            uniqueEdges.add(key);
        });

        return uniqueEdges.size;
    };

    const analyzeNodes = (nodes) => {
        const analysis = {
            coreNodes: [],
            freeNodes: [],
            proNodes: [],
            paidNodes: [],
            missingNodes: []
        };

        // Use Sets to track unique node types
        const seenTypes = {
            core: new Set(),
            free: new Set(),
            pro: new Set(),
            paid: new Set(),
            missing: new Set()
        };

        nodes.forEach(node => {
            const nodeType = node.type;
            const nodeData = node.data || {};

            // Try to get tier from availableNodesMap first (more reliable)
            let tier = 'CORE';
            let nodeName = nodeData.label || nodeType;
            let author = 'Unknown';
            let installUrl = null;
            let price = null;

            if (availableNodesMap && availableNodesMap[nodeType]) {
                const nodeMetadata = availableNodesMap[nodeType];
                tier = nodeMetadata.tier || 'CORE';
                nodeName = nodeMetadata.name || nodeName;
                author = nodeMetadata.author || 'Unknown';
                installUrl = nodeMetadata.installUrl || null;
                price = nodeMetadata.price || null;
            } else if (nodeData.tier) {
                // Fallback to node data tier
                tier = nodeData.tier;
                author = nodeData.author || 'Unknown';
                installUrl = nodeData.installUrl || null;
                price = nodeData.price || null;
            }

            const nodeInfo = {
                type: nodeType,
                name: nodeName,
                author: author
            };

            // Only add if we haven't seen this type before
            if (tier === 'CORE' && !seenTypes.core.has(nodeType)) {
                seenTypes.core.add(nodeType);
                analysis.coreNodes.push(nodeInfo);
            } else if (tier === 'FREE' && !seenTypes.free.has(nodeType)) {
                seenTypes.free.add(nodeType);
                analysis.freeNodes.push({
                    ...nodeInfo,
                    installUrl: installUrl
                });
            } else if (tier === 'PRO' && !seenTypes.pro.has(nodeType)) {
                seenTypes.pro.add(nodeType);
                analysis.proNodes.push({
                    ...nodeInfo,
                    installUrl: installUrl,
                    price: price
                });
            } else if (tier === 'PAID' && !seenTypes.paid.has(nodeType)) {
                seenTypes.paid.add(nodeType);
                analysis.paidNodes.push({
                    ...nodeInfo,
                    installUrl: installUrl,
                    price: price
                });
            } else if (!seenTypes.missing.has(nodeType)) {
                // Unknown tier - treat as missing
                seenTypes.missing.add(nodeType);
                analysis.missingNodes.push(nodeInfo);
            }
        });

        setNodeAnalysis(analysis);
    };

    // Remove duplicate edges before export
    const getUniqueEdges = (edges) => {
        if (!edges || !Array.isArray(edges)) return [];

        const uniqueEdgesMap = new Map();
        edges.forEach(edge => {
            // Create a unique key based on source, target, and handles
            const key = `${edge.source}-${edge.sourceHandle || 'default'}-${edge.target}-${edge.targetHandle || 'default'}`;
            // Only keep the first occurrence of each unique edge
            if (!uniqueEdgesMap.has(key)) {
                uniqueEdgesMap.set(key, edge);
            }
        });

        return Array.from(uniqueEdgesMap.values());
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        // Clean edges to remove duplicates
        const cleanedEdges = getUniqueEdges(workflowData.edges);

        const exportData = {
            ...formData,
            nodes: workflowData.nodes,
            edges: cleanedEdges,
            viewport: workflowData.viewport,
            nodeAnalysis,
            exportedAt: new Date().toISOString(),
            voodflowVersion: '1.0.0'
        };

        onExport(exportData);
    };

    if (!isOpen) return null;

    const hasNonCoreNodes = nodeAnalysis.freeNodes.length > 0 ||
        nodeAnalysis.proNodes.length > 0 ||
        nodeAnalysis.paidNodes.length > 0;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-3xl w-full mx-4 max-h-[80vh] flex flex-col overflow-hidden">
                {/* Header */}
                <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold text-slate-800 dark:text-slate-100">
                            Export Workflow
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

                {/* Content - No scroll */}
                <div className="px-6 py-4">
                    <form onSubmit={handleSubmit} id="export-form">
                        {/* Metadata Fields - 3 columns */}
                        <div className="mb-4">
                            <div className="grid grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Author
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.author}
                                        onChange={(e) => setFormData({ ...formData, author: e.target.value })}
                                        className="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                        placeholder="Your name"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        License
                                    </label>
                                    <select
                                        value={formData.license}
                                        onChange={(e) => setFormData({ ...formData, license: e.target.value })}
                                        className="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                    >
                                        <option value="MIT">MIT</option>
                                        <option value="Apache-2.0">Apache 2.0</option>
                                        <option value="GPL-3.0">GPL 3.0</option>
                                        <option value="BSD-3-Clause">BSD 3-Clause</option>
                                        <option value="Proprietary">Proprietary</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Version
                                    </label>
                                    <input
                                        type="text"
                                        value={formData.version}
                                        onChange={(e) => setFormData({ ...formData, version: e.target.value })}
                                        className="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                                        placeholder="1.0.0"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Description
                                </label>
                                <textarea
                                    value={formData.description}
                                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                                    rows={2}
                                    className="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-none"
                                    placeholder="Describe what this workflow does..."
                                />
                            </div>
                        </div>

                        {/* Node Analysis */}
                        {hasNonCoreNodes && (
                            <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-4">
                                <div className="flex items-start gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5">
                                        <path fillRule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clipRule="evenodd" />
                                    </svg>
                                    <div className="flex-1">
                                        <h4 className="font-semibold text-amber-800 dark:text-amber-300 mb-2">
                                            Additional Nodes Required
                                        </h4>
                                        <p className="text-sm text-amber-700 dark:text-amber-400 mb-3">
                                            This workflow uses nodes that are not part of the core system. Users will need to install these nodes before importing.
                                        </p>

                                        {nodeAnalysis.freeNodes.length > 0 && (
                                            <div className="mb-3">
                                                <h5 className="text-sm font-medium text-amber-800 dark:text-amber-300 mb-1">
                                                    Free Nodes:
                                                </h5>
                                                <ul className="text-sm text-amber-700 dark:text-amber-400 space-y-1">
                                                    {nodeAnalysis.freeNodes.map((node, idx) => (
                                                        <li key={idx} className="flex items-center gap-2">
                                                            <span className="w-1.5 h-1.5 rounded-full bg-amber-600 dark:bg-amber-400"></span>
                                                            {node.author && node.author !== 'Unknown' ? (
                                                                <span>
                                                                    {node.author} - {node.name}
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
                                                                </span>
                                                            ) : (
                                                                <span>{node.name}</span>
                                                            )}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}

                                        {nodeAnalysis.proNodes.length > 0 && (
                                            <div className="mb-3">
                                                <h5 className="text-sm font-medium text-amber-800 dark:text-amber-300 mb-1">
                                                    PRO Nodes:
                                                </h5>
                                                <ul className="text-sm text-amber-700 dark:text-amber-400 space-y-1">
                                                    {nodeAnalysis.proNodes.map((node, idx) => (
                                                        <li key={idx} className="flex items-center gap-2">
                                                            <span className="w-1.5 h-1.5 rounded-full bg-amber-600 dark:bg-amber-400"></span>
                                                            {node.author && node.author !== 'Unknown' ? (
                                                                <span>
                                                                    {node.author} - {node.name}
                                                                    {node.price && ` ($${node.price})`}
                                                                    {node.installUrl && (
                                                                        <a
                                                                            href={node.installUrl}
                                                                            target="_blank"
                                                                            rel="noopener noreferrer"
                                                                            className="ml-2 text-amber-800 dark:text-amber-300 underline hover:no-underline"
                                                                        >
                                                                            Get
                                                                        </a>
                                                                    )}
                                                                </span>
                                                            ) : (
                                                                <span>{node.name} {node.price && `($${node.price})`}</span>
                                                            )}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}

                                        {nodeAnalysis.paidNodes.length > 0 && (
                                            <div>
                                                <h5 className="text-sm font-medium text-amber-800 dark:text-amber-300 mb-1">
                                                    Paid Nodes:
                                                </h5>
                                                <ul className="text-sm text-amber-700 dark:text-amber-400 space-y-1">
                                                    {nodeAnalysis.paidNodes.map((node, idx) => (
                                                        <li key={idx} className="flex items-center gap-2">
                                                            <span className="w-1.5 h-1.5 rounded-full bg-amber-600 dark:bg-amber-400"></span>
                                                            {node.author && node.author !== 'Unknown' ? (
                                                                <span>
                                                                    {node.author} - {node.name}
                                                                    {node.price && ` ($${node.price})`}
                                                                    {node.installUrl && (
                                                                        <a
                                                                            href={node.installUrl}
                                                                            target="_blank"
                                                                            rel="noopener noreferrer"
                                                                            className="ml-2 text-amber-800 dark:text-amber-300 underline hover:no-underline"
                                                                        >
                                                                            Get
                                                                        </a>
                                                                    )}
                                                                </span>
                                                            ) : (
                                                                <span>{node.name} {node.price && `($${node.price})`}</span>
                                                            )}
                                                        </li>
                                                    ))}
                                                </ul>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Summary */}
                        <div className="bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4">
                            <h4 className="font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                Export Summary
                            </h4>
                            <div className="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span className="text-slate-500 dark:text-slate-400">Total Nodes:</span>
                                    <span className="ml-2 font-medium text-slate-700 dark:text-slate-200">
                                        {(workflowData.nodes || []).length}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-slate-500 dark:text-slate-400">Connections:</span>
                                    <span className="ml-2 font-medium text-slate-700 dark:text-slate-200">
                                        {getUniqueEdgesCount(workflowData.edges || [])}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-slate-500 dark:text-slate-400">Core Nodes:</span>
                                    <span className="ml-2 font-medium text-slate-700 dark:text-slate-200">
                                        {nodeAnalysis.coreNodes.length}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-slate-500 dark:text-slate-400">Free Nodes:</span>
                                    <span className="ml-2 font-medium text-slate-700 dark:text-slate-200">
                                        {nodeAnalysis.freeNodes.length}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-slate-500 dark:text-slate-400">PRO Nodes:</span>
                                    <span className="ml-2 font-medium text-slate-700 dark:text-slate-200">
                                        {nodeAnalysis.proNodes.length}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-slate-500 dark:text-slate-400">Paid Nodes:</span>
                                    <span className="ml-2 font-medium text-slate-700 dark:text-slate-200">
                                        {nodeAnalysis.paidNodes.length}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </form>
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
                        type="submit"
                        form="export-form"
                        className="px-6 py-2 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-semibold rounded-lg shadow-lg shadow-orange-500/25 transition-all duration-200 hover:shadow-xl hover:shadow-orange-500/30"
                    >
                        Export Workflow
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ExportFlowModal;
