import React, { useState } from 'react';
import { Handle, Position, useEdges, useReactFlow } from 'reactflow';
import ConfirmModal from './ConfirmModal';
import AddNodeButton from './AddNodeButton';

const ConditionalNode = ({ id, data }) => {
    const { setNodes } = useReactFlow();
    const edges = useEdges();

    const [isExpanded, setIsExpanded] = useState(data.isNew || false);
    const [label, setLabel] = useState(data.label || 'Condition');
    const [description, setDescription] = useState(data.description || '');
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    const handleCollapse = () => {
        setIsExpanded(false);
        if (data.isNew) {
            updateNodeData({ isNew: false });
        }
        save(label, description);
    };

    const updateNodeData = (updates) => {
        setNodes((nds) => nds.map((node) => {
            if (node.id === id) {
                return { ...node, data: { ...node.data, ...updates } };
            }
            return node;
        }));
    };

    // Check connections
    const isTrueConnected = edges.some(edge => edge.source === id && edge.sourceHandle === 'true');
    const isFalseConnected = edges.some(edge => edge.source === id && edge.sourceHandle === 'false');

    const save = (newLabel = label, newDescription = description) => {
        updateNodeData({ label: newLabel, description: newDescription });

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

    const handleLabelChange = (val) => { setLabel(val); save(val, description); };
    const handleDescriptionChange = (val) => { setDescription(val); save(label, val); };

    const handleDelete = () => { setShowDeleteModal(true); };
    const confirmDelete = () => {
        setShowDeleteModal(false);
        if (data.livewireId && window.Livewire) {
            window.Livewire.find(data.livewireId)?.call('deleteNode', id);
        }
    };

    const handleAddConnectedNode = (nodeType, handleId) => {
        if (!data.livewireId || !window.Livewire) return;
        const component = window.Livewire.find(data.livewireId);
        if (!component) return;
        component.call('createGenericNode', {
            type: nodeType,
            sourceNodeId: id,
            sourceHandle: handleId
        });
        handleCollapse();
    };

    return (
        <>
            <ConfirmModal
                isOpen={showDeleteModal}
                title="Delete Condition"
                message={`Are you sure you want to delete "${label}"?`}
                onConfirm={confirmDelete}
                onCancel={() => setShowDeleteModal(false)}
            />

            <div className={`
                relative
                bg-white dark:bg-slate-900
                border-2 rounded-xl
                border-amber-500
                shadow-lg min-w-[280px] max-w-[350px]
                transition-all duration-200
            `}>
                <Handle
                    type="target"
                    position={Position.Left}
                    className="!bg-slate-500 !w-3 !h-3 !border-2 !border-white"
                />

                {/* Header */}
                <div className="bg-amber-500 px-4 py-2.5 flex items-center justify-between rounded-t-lg">
                    <div className="flex items-center gap-2">
                        <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <span className="text-white font-bold text-sm tracking-wide break-all max-w-[150px] truncate">
                            {label.toUpperCase()}
                        </span>
                    </div>

                    <div className="flex items-center gap-2">
                        <button
                            onClick={handleCollapse}
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
                            <div className="font-medium text-slate-700 dark:text-slate-200">
                                {label}
                            </div>
                            <div className="text-xs text-slate-400 italic">
                                Routes action result
                            </div>
                        </div>
                    ) : (
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
                                    className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                        bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-amber-500"
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
                                        bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none"
                                />
                            </div>

                            <div className="text-xs text-slate-500 bg-slate-50 dark:bg-slate-800 p-2 rounded border border-slate-200 dark:border-slate-700">
                                This node routes the flow based on the action's true/false result.
                            </div>
                        </div>
                    )}
                </div>

                {/* True Handle - Green */}
                <Handle
                    id="true"
                    type="source"
                    position={Position.Right}
                    className={`!w-3 !h-3 !border-2 !border-white !bg-green-500
                        ${!isTrueConnected ? 'opacity-0' : 'opacity-100'}
                    `}
                    style={{ top: '43%', right: '-6px' }}
                />
                {!isTrueConnected && (
                    <div className="absolute right-0 translate-x-1/2 -translate-y-1/2" style={{ top: '43%' }}>
                        <AddNodeButton onAddNode={(type) => handleAddConnectedNode(type, 'true')} sourceNodeId={id} livewireId={data.livewireId} availableNodes={data.availableNodes} color="green" />
                    </div>
                )}

                {/* False Handle - Red */}
                <Handle
                    id="false"
                    type="source"
                    position={Position.Right}
                    className={`!w-3 !h-3 !border-2 !border-white !bg-red-500
                        ${!isFalseConnected ? 'opacity-0' : 'opacity-100'}
                    `}
                    style={{ top: '67%', right: '-6px' }}
                />
                {!isFalseConnected && (
                    <div className="absolute right-0 translate-x-1/2 -translate-y-1/2" style={{ top: '67%' }}>
                        <AddNodeButton onAddNode={(type) => handleAddConnectedNode(type, 'false')} sourceNodeId={id} livewireId={data.livewireId} availableNodes={data.availableNodes} color="red" />
                    </div>
                )}
            </div>
        </>
    );
};

export default ConditionalNode;
