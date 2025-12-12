import React, { useState, useEffect } from 'react';
import { Handle, Position, useStore, useReactFlow } from 'reactflow';
import ConfirmModal from './ConfirmModal';
import AddNodeButton from './AddNodeButton';

const TriggerNode = ({ id, data, selected }) => {
    const { setNodes } = useReactFlow();

    // Check for outgoing connections
    const edges = useStore((s) => s.edges);
    const isConnected = edges.some(edge => edge.source === id);
    const [isExpanded, setIsExpanded] = useState(data.isNew || false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [formData, setFormData] = useState({
        label: data.label || '',
        description: data.description || '',
        eventClass: data.eventClass || '',
        status: data.status || 'draft'
    });

    const eventOptions = data.eventOptions || {};

    // Save function
    const save = (newData) => {
        if (!data.livewireId) {
            console.warn(`[TriggerNode] Missing livewireId for node ${id}`);
            return;
        }

        if (window.Livewire) {
            const component = window.Livewire.find(data.livewireId);
            if (component) {
                console.log(`[TriggerNode] Saving config for ${id}`, newData);
                // Use generic updateNodeConfig standard method
                component.call('updateNodeConfig', {
                    nodeId: id,
                    label: newData.label,
                    description: newData.description,
                    eventClass: newData.eventClass,
                    status: newData.status
                    // implementation will merge these into config
                });
            } else {
                console.error(`[TriggerNode] Livewire component ${data.livewireId} not found`);
            }
        }
    };

    // Debounced save effect
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            // Only save if data is different from props (optional optimization, but good for now just save)
            save(formData);
        }, 600); // 600ms debounce

        return () => clearTimeout(timeoutId);
    }, [formData]);

    // Single handler for all field changes
    const handleChange = (field, value) => {
        setFormData(prev => ({ ...prev, [field]: value }));

        // Immediately update ReactFlow store so connected nodes (like FilterNode) 
        // can access the new data (e.g. eventClass) without a reload
        setNodes((nds) => nds.map((node) => {
            if (node.id === id) {
                return {
                    ...node,
                    data: {
                        ...node.data,
                        [field]: value
                    }
                };
            }
            return node;
        }));
    };

    const handleDelete = () => {
        setShowDeleteModal(true);
    };

    const confirmDelete = () => {
        setShowDeleteModal(false);
        if (data.livewireId && window.Livewire) {
            window.Livewire.find(data.livewireId)?.call('deleteTrigger', id);
        }
    };

    const statusColors = {
        active: 'bg-green-500',
        draft: 'bg-amber-500',
        disabled: 'bg-red-500',
    };

    const isActive = formData.status === 'active';

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

    // Handle toggle expansion
    const toggleExpansion = () => {
        if (isExpanded) {
            handleCollapse();
        } else {
            setIsExpanded(true);
        }
    };

    // Handle adding a connected node
    const handleAddConnectedNode = (nodeType, sourceNodeId) => {
        if (!data.livewireId || !window.Livewire) return;
        const component = window.Livewire.find(data.livewireId);
        if (!component) return;

        // Use generic create node
        component.call('createGenericNode', {
            type: nodeType,
            sourceNodeId: sourceNodeId
        });

        // Collapse this node
        handleCollapse();
    };

    return (
        <>
            <ConfirmModal
                isOpen={showDeleteModal}
                title="Delete Trigger"
                message={`Are you sure you want to delete "${formData.label}"? This action cannot be undone.`}
                onConfirm={confirmDelete}
                onCancel={() => setShowDeleteModal(false)}
            />

            <div className={`
                relative
                bg-white dark:bg-slate-900
                border-2 border-solid rounded-xl
                ${selected ? 'border-orange-500 shadow-lg' : 'border-slate-200 dark:border-slate-700'}
                text-slate-700 dark:text-slate-200
                min-w-[350px]
                transition-all duration-200
                hover:shadow-md
            `}>
                {/* Header */}
                <div className="bg-gradient-to-r from-orange-500 to-orange-600 px-4 py-3 flex items-center justify-between box-border rounded-t-lg">
                    <div className="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-5 h-5 text-white">
                            <path fillRule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clipRule="evenodd" />
                        </svg>
                        <div className="text-sm font-bold text-white uppercase tracking-wider">
                            {formData.label || 'Trigger'}
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        {formData.status && (
                            <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full uppercase ${formData.status === 'active'
                                ? 'bg-green-500/20 text-white border border-green-500/30'
                                : 'bg-white/20 text-white'
                                }`}>
                                {formData.status}
                            </span>
                        )}

                        {/* Expand/Collapse */}
                        <button
                            onClick={toggleExpansion}
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
                            className="nodrag cursor-pointer text-white/80 hover:text-white transition-colors"
                            title="Delete Trigger"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-3.5 h-3.5">
                                <path fillRule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Body */}
                <div className="p-4 bg-white dark:bg-slate-900 rounded-b-lg">
                    {!isExpanded ? (
                        /* Collapsed View */
                        <div>
                            {formData.description && (
                                <div className="text-slate-500 dark:text-slate-400 text-xs italic mb-2">
                                    {formData.description}
                                </div>
                            )}
                            {formData.eventClass && (
                                <div className="text-xs text-slate-500 dark:text-slate-400 font-mono bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded inline-block">
                                    {eventOptions[formData.eventClass] || formData.eventClass.split('\\').pop()}
                                </div>
                            )}
                            {!formData.description && !formData.eventClass && (
                                <div className="text-slate-400 dark:text-slate-500 text-sm italic mt-2">
                                    Click arrow to configure...
                                </div>
                            )}
                        </div>
                    ) : (
                        /* Expanded View - Edit Form */
                        <div className="nodrag space-y-3">
                            {/* Name */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Trigger Name
                                </label>
                                <input
                                    type="text"
                                    value={formData.label}
                                    onChange={(e) => handleChange('label', e.target.value)}
                                    placeholder="Enter trigger name"
                                    className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                    bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100
                                    focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none"
                                />
                            </div>

                            {/* Description */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Description
                                </label>
                                <textarea
                                    value={formData.description}
                                    onChange={(e) => handleChange('description', e.target.value)}
                                    placeholder="Describe this trigger"
                                    rows={2}
                                    className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                    bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100
                                    focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none resize-none"
                                />
                            </div>

                            {/* Event Class */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Event Class
                                </label>
                                <select
                                    value={formData.eventClass}
                                    onChange={(e) => handleChange('eventClass', e.target.value)}
                                    className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                    bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100
                                    focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none cursor-pointer"
                                >
                                    <option value="">Select an event...</option>
                                    {Object.entries(eventOptions).map(([value, label]) => (
                                        <option key={value} value={value}>{label}</option>
                                    ))}
                                </select>
                            </div>

                            {/* Status */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Status
                                </label>
                                <select
                                    value={formData.status}
                                    onChange={(e) => handleChange('status', e.target.value)}
                                    className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                    bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100
                                    focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none cursor-pointer"
                                >
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="disabled">Disabled</option>
                                </select>
                            </div>
                        </div>
                    )}
                </div>

                {/* Output Handle */}
                <Handle
                    type="source"
                    position={Position.Right}
                    className={`!w-3 !h-3 !border-2 !border-white !bg-orange-500
                        ${!isConnected ? 'opacity-0' : 'opacity-100'}
                    `}
                    style={{ right: '-6px', top: '50%' }}
                />

                {/* Add Button */}
                {!isConnected && (
                    <div className="absolute right-0 top-1/2 translate-x-1/2 -translate-y-1/2 z-10">
                        <AddNodeButton
                            onAddNode={handleAddConnectedNode}
                            sourceNodeId={id}
                            livewireId={data.livewireId}
                            availableNodes={data.availableNodes}
                            color="orange"
                        />
                    </div>
                )}
            </div>
        </>
    );
};

export default TriggerNode;
