import React, { useState } from 'react';
import { Handle, Position, useEdges, useReactFlow } from 'reactflow';
import ConfirmModal from '../../../../resources/js/components/ConfirmModal';
import AddNodeButton from '../../../../resources/js/components/AddNodeButton';

/**
 * MailNode React Component
 * 
 * Custom node for workflow automation
 * 
 * @author Voodflow
 * @version 1.0.0
 */
const MailNode = ({ id, data }) => {
    const { setNodes } = useReactFlow();
    const edges = useEdges();

    const [isExpanded, setIsExpanded] = useState(data.isNew || false);
    const [label, setLabel] = useState(data.label || 'MailNode');
    const [description, setDescription] = useState(data.description || '');
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    // Check connections
    const isConnected = edges.some(edge => edge.target === id);
    const isOutputConnected = edges.some(edge => edge.source === id);

    // Handle collapse and update isNew
    const handleCollapse = () => {
        setIsExpanded(false);
        if (data.isNew) {
            setNodes((nds) => nds.map((node) => {
                if (node.id === id) {
                    return {
                        ...node,
                        data: {
                            ...node.data,
                            isNew: false
                        }
                    };
                }
                return node;
            }));
        }
    };

    // Save configuration to backend
    const save = (newLabel = label, newDescription = description) => {
        if (data.livewireId && window.Livewire) {
            const component = window.Livewire.find(data.livewireId);
            if (component) {
                component.call('updateNodeConfig', {
                    nodeId: id,
                    label: newLabel,
                    description: newDescription,
                });
            }
        }
    };

    const handleLabelChange = (value) => {
        setLabel(value);
        save(value, description);
    };

    const handleDescriptionChange = (value) => {
        setDescription(value);
        save(label, value);
    };

    const handleDelete = () => {
        setShowDeleteModal(true);
    };

    const confirmDelete = () => {
        setShowDeleteModal(false);
        if (data.livewireId && window.Livewire) {
            window.Livewire.find(data.livewireId)?.call('deleteNode', id);
        }
    };

    // Handle adding a connected node
    const handleAddConnectedNode = (nodeType, sourceNodeId) => {
        if (!data.livewireId || !window.Livewire) return;
        const component = window.Livewire.find(data.livewireId);
        if (!component) return;
        component.call('createGenericNode', { type: nodeType, sourceNodeId: sourceNodeId });
        handleCollapse();
    };

    return (
        <>
            <ConfirmModal
                isOpen={showDeleteModal}
                title="Delete MailNode"
                message={"Are you sure you want to delete \"" + label + "\"?"}
                onConfirm={confirmDelete}
                onCancel={() => setShowDeleteModal(false)}
            />

            <div className={"relative bg-white dark:bg-slate-900 border-2 rounded-xl " + (isConnected ? "border-blue-500" : "border-slate-300 dark:border-slate-600") + " shadow-lg min-w-[300px] max-w-[420px] transition-all duration-200"}>
                <Handle
                    type="target"
                    position={Position.Left}
                    className="!bg-blue-500 !w-3 !h-3 !border-2 !border-white"
                />

                {/* Header */}
                <div className="bg-blue-500 px-4 py-2.5 flex items-center justify-between rounded-t-lg">
                    <div className="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4 text-white">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        <span className="text-xs font-bold text-white uppercase tracking-wider">
                            {label}
                        </span>
                    </div>

                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => setIsExpanded(!isExpanded)}
                            className="nodrag text-white/80 hover:text-white transition-colors"
                            title={isExpanded ? "Collapse" : "Expand"}
                            disabled={!isConnected}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                className={"w-4 h-4 transition-transform " + (isExpanded ? "rotate-180" : "") + " " + (!isConnected ? "opacity-50" : "")}>
                                <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                            </svg>
                        </button>
                        <button
                            onClick={handleDelete}
                            className="nodrag text-white/80 hover:text-white transition-colors"
                            title="Delete"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
                                <path fillRule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Body */}
                <div className="p-4">
                    {!isConnected ? (
                        <div className="text-center py-4">
                            <div className="text-slate-400 dark:text-slate-500 text-sm mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-8 h-8 mx-auto mb-2 opacity-50">
                                    <path fillRule="evenodd" d="M4.25 2A2.25 2.25 0 002 4.25v2.5A2.25 2.25 0 004.25 9h2.5A2.25 2.25 0 009 6.75v-2.5A2.25 2.25 0 006.75 2h-2.5zm0 9A2.25 2.25 0 002 13.25v2.5A2.25 2.25 0 004.25 18h2.5A2.25 2.25 0 009 15.75v-2.5A2.25 2.25 0 006.75 11h-2.5zm9-9A2.25 2.25 0 0011 4.25v2.5A2.25 2.25 0 0013.25 9h2.5A2.25 2.25 0 0018 6.75v-2.5A2.25 2.25 0 0015.75 2h-2.5zm0 9A2.25 2.25 0 0011 13.25v2.5A2.25 2.25 0 0013.25 18h2.5A2.25 2.25 0 0018 15.75v-2.5A2.25 2.25 0 0015.75 11h-2.5z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="text-blue-500 font-medium text-sm">Connect data</div>
                        </div>
                    ) : !isExpanded ? (
                        /* Collapsed View */
                        <div>
                            {description && (
                                <div className="text-slate-500 dark:text-slate-400 text-xs italic mb-2">
                                    {description}
                                </div>
                            )}
                            <div className="text-slate-400 dark:text-slate-500 italic text-sm">
                                Click to configure
                            </div>
                        </div>
                    ) : (
                        /* Expanded View - Edit Form */
                        <div className="nodrag space-y-3">
                            {/* Name */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Name
                                </label>
                                <input
                                    type="text"
                                    value={label}
                                    onChange={(e) => handleLabelChange(e.target.value)}
                                    placeholder="Node name..."
                                    className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                        bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100
                                        focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                />
                            </div>

                            {/* Description */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Description
                                </label>
                                <input
                                    type="text"
                                    value={description}
                                    onChange={(e) => handleDescriptionChange(e.target.value)}
                                    placeholder="Optional description..."
                                    className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                        bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100
                                        focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                />
                            </div>

                            {/* TODO: Add your custom configuration fields here */}
                            <div className="text-xs text-slate-400 italic text-center py-2">
                                Add your configuration fields here
                            </div>
                        </div>
                    )}
                </div>

                {/* Output Handle(s) */}
                <Handle
                    type="source"
                    position={Position.Right}
                    className={"!bg-blue-500 !w-3 !h-3 !border-2 !border-white " + (!isOutputConnected ? "opacity-0" : "opacity-100")}
                    style={{ right: '-6px', top: '50%' }}
                />

                {/* Add Button */}
                {!isOutputConnected && (
                    <div className="absolute right-0 top-1/2 translate-x-1/2 -translate-y-1/2 z-10">
                        <AddNodeButton
                            onAddNode={handleAddConnectedNode}
                            sourceNodeId={id}
                            livewireId={data.livewireId}
                            availableNodes={data.availableNodes}
                            color="blue"
                        />
                    </div>
                )}
            </div>
        </>
    );
};

export default MailNode;
