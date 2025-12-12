import React, { useState } from 'react';
import { Handle, Position, useStore } from 'reactflow';
import ConfirmModal from './ConfirmModal';
import AddNodeButton from './AddNodeButton';

/**
 * SendWebhook Node Component
 * 
 * Type: action
 * Description: Send webhook with Success/Error branches
 */
const SendWebhookNode = ({ id, data }) => {
    const [isExpanded, setIsExpanded] = useState(data.isNew || false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [label, setLabel] = useState(data.label || 'SendWebhook');
    const [description, setDescription] = useState(data.description || '');

    const edges = useStore((s) => s.edges);

    // Check connections for specific handles
    const isSuccessConnected = edges.some(edge => edge.source === id && edge.sourceHandle === 'success');
    const isErrorConnected = edges.some(edge => edge.source === id && edge.sourceHandle === 'error');

    // Handle adding a connected node
    const handleAddConnectedNode = (nodeType, sourceNodeId, sourceHandle) => {
        if (!data.livewireId || !window.Livewire) return;
        const component = window.Livewire.find(data.livewireId);
        if (!component) return;

        // Use generic create node
        component.call('createGenericNode', {
            type: nodeType,
            sourceNodeId: sourceNodeId,
            sourceHandle: sourceHandle
        });
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

    return (
        <>
            <ConfirmModal
                isOpen={showDeleteModal}
                title="Delete Webhook"
                message={`Are you sure you want to delete "${label}"? This action cannot be undone.`}
                onConfirm={confirmDelete}
                onCancel={() => setShowDeleteModal(false)}
            />

            <div className={`
                relative
                bg-white dark:bg-slate-900
                border-2 rounded-xl
                border-red-500
                shadow-lg min-w-[300px] max-w-[400px]
                transition-all duration-200
            `}>
                <Handle
                    type="target"
                    position={Position.Left}
                    className="!bg-slate-500 !w-3 !h-3 !border-2 !border-white"
                />

                {/* Header */}
                <div className="bg-red-500 px-4 py-2 flex items-center justify-between rounded-t-lg">
                    <div className="flex items-center gap-2">
                        {/* Icon */}
                        <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span className="text-white font-bold text-sm tracking-wide">WEBHOOK</span>
                    </div>

                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => setIsExpanded(!isExpanded)}
                            className="nodrag text-white/80 hover:text-white transition-colors"
                            title={isExpanded ? "Collapse" : "Expand"}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                className={`w-4 h-4 transition-transform ${isExpanded ? 'rotate-180' : ''}`}>
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
                <div className="p-4 relative">
                    {!isExpanded ? (
                        <div
                            className="cursor-pointer group flex flex-col gap-1"
                            onClick={() => setIsExpanded(true)}
                        >
                            <div className="font-medium text-slate-700 dark:text-slate-200 flex items-center justify-between">
                                <span>{label}</span>
                                <svg className="w-4 h-4 text-slate-400 group-hover:text-slate-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </div>
                            {description && (
                                <div className="text-sm text-slate-500 dark:text-slate-400 truncate">
                                    {description}
                                </div>
                            )}
                            {!description && (
                                <div className="text-slate-400 dark:text-slate-500 text-sm italic">
                                    Configure webhook...
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="nodrag space-y-3">
                            <div>
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Name
                                </label>
                                <input
                                    type="text"
                                    value={label}
                                    onChange={(e) => handleLabelChange(e.target.value)}
                                    className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                        bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Description
                                </label>
                                <textarea
                                    value={description}
                                    onChange={(e) => handleDescriptionChange(e.target.value)}
                                    rows={2}
                                    className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                        bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none resize-none"
                                />
                            </div>
                        </div>
                    )}

                    {/* Output Labels */}
                    <div className="absolute right-0 top-0 bottom-0 flex flex-col justify-center pr-2 gap-8 text-[10px] font-bold text-right text-slate-400 pointer-events-none">
                        <span className="text-green-600 dark:text-green-400">Success</span>
                        <span className="text-red-500 dark:text-red-400">Error</span>
                    </div>
                </div>

                {/* Success Handle & Button */}
                <Handle
                    id="success"
                    type="source"
                    position={Position.Right}
                    className={`!w-3 !h-3 !border-2 !border-white !bg-green-500
                        ${!isSuccessConnected ? 'opacity-0' : 'opacity-100'}
                    `}
                    style={{ top: '43%', right: '-6px' }}
                />
                {!isSuccessConnected && (
                    <div
                        className="absolute right-0 translate-x-1/2 -translate-y-1/2 flex items-center justify-center"
                        style={{ top: '43%' }}
                    >
                        <AddNodeButton
                            onAddNode={(type) => handleAddConnectedNode(type, id, 'success')}
                            sourceNodeId={id}
                            livewireId={data.livewireId}
                            availableNodes={data.availableNodes}
                            color="green"
                        />
                    </div>
                )}


                {/* Error Handle & Button */}
                <Handle
                    id="error"
                    type="source"
                    position={Position.Right}
                    className={`!w-3 !h-3 !border-2 !border-white !bg-red-500
                        ${!isErrorConnected ? 'opacity-0' : 'opacity-100'}
                    `}
                    style={{ top: '67%', right: '-6px' }}
                />
                {!isErrorConnected && (
                    <div
                        className="absolute right-0 translate-x-1/2 -translate-y-1/2 flex items-center justify-center"
                        style={{ top: '67%' }}
                    >
                        <AddNodeButton
                            onAddNode={(type) => handleAddConnectedNode(type, id, 'error')}
                            sourceNodeId={id}
                            livewireId={data.livewireId}
                            availableNodes={data.availableNodes}
                            color="red"
                        />
                    </div>
                )}
            </div>
        </>
    );
};

export default SendWebhookNode;