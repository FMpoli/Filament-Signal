import React, { useState } from 'react';
import { Handle, Position } from 'reactflow';
import ConfirmModal from './ConfirmModal';

const TriggerNode = ({ id, data }) => {
    const [isExpanded, setIsExpanded] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [formData, setFormData] = useState({
        label: data.label || '',
        description: data.description || '',
        eventClass: data.eventClass || '',
        status: data.status || 'draft'
    });

    const eventOptions = data.eventOptions || {};

    // Simple save function - called immediately on change
    const save = (newData) => {
        if (data.livewireId && window.Livewire) {
            const component = window.Livewire.find(data.livewireId);
            if (component) {
                component.call('updateTriggerConfig', {
                    nodeId: id,
                    label: newData.label,
                    description: newData.description,
                    eventClass: newData.eventClass,
                    status: newData.status
                });
            }
        }
    };

    // Single handler for all field changes
    const handleChange = (field, value) => {
        const updated = { ...formData, [field]: value };
        setFormData(updated);
        save(updated);
    };

    const handleDelete = () => {
        setShowDeleteModal(true);
    };

    const confirmDelete = () => {
        setShowDeleteModal(false);
        if (data.livewireId && window.Livewire) {
            window.Livewire.find(data.livewireId)?.call('deleteTrigger');
        }
    };

    const statusColors = {
        active: 'bg-green-500',
        draft: 'bg-amber-500',
        disabled: 'bg-red-500',
    };

    const isActive = formData.status === 'active';

    return (
        <>
            <ConfirmModal
                isOpen={showDeleteModal}
                title="Delete Trigger"
                message="Are you sure you want to delete this trigger? This will also remove all connected filters and actions."
                onConfirm={confirmDelete}
                onCancel={() => setShowDeleteModal(false)}
                confirmColor="orange"
            />

            <div className={`
            bg-white dark:bg-slate-900
            border-2 rounded-xl
            ${isActive ? 'border-orange-500' : 'border-slate-200 dark:border-slate-700'}
            shadow-lg min-w-[280px] max-w-[380px]
            transition-all duration-200
        `}>
                {/* Header */}
                <div className="bg-orange-500 px-4 py-2.5 flex items-center justify-between rounded-t-lg">
                    <div className="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4 text-white">
                            <path fillRule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clipRule="evenodd" />
                        </svg>
                        <span className="text-xs font-bold text-white uppercase tracking-wider">Trigger</span>
                    </div>

                    <div className="flex items-center gap-2">
                        {/* Status Badge */}
                        <span className={`text-[10px] px-2 py-0.5 rounded font-bold uppercase text-white ${statusColors[formData.status]}`}>
                            {formData.status}
                        </span>

                        {/* Expand/Collapse */}
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

                        {/* Delete */}
                        <button
                            onClick={handleDelete}
                            className="nodrag text-white/80 hover:text-white transition-colors"
                            title="Delete Trigger"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
                                <path fillRule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Body */}
                <div className="p-4">
                    {!isExpanded ? (
                        /* Collapsed View */
                        <div>
                            <div className="text-base font-semibold text-slate-800 dark:text-slate-100 mb-1">
                                {formData.label || 'Untitled Trigger'}
                            </div>
                            {formData.eventClass && (
                                <div className="text-xs text-slate-500 dark:text-slate-400 font-mono bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded truncate">
                                    {eventOptions[formData.eventClass] || formData.eventClass}
                                </div>
                            )}
                            {formData.description && (
                                <div className="text-xs text-slate-600 dark:text-slate-400 mt-2 line-clamp-2">
                                    {formData.description}
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

                <Handle
                    type="source"
                    position={Position.Right}
                    className="!bg-orange-500 !w-3 !h-3 !border-2 !border-white"
                />
            </div>
        </>
    );
};

export default TriggerNode;
