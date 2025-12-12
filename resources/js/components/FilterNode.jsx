import React, { useState } from 'react';
import { Handle, Position, useEdges } from 'reactflow';

const FilterNode = ({ id, data }) => {
    const edges = useEdges();
    const [isExpanded, setIsExpanded] = useState(false);
    const [filters, setFilters] = useState(Array.isArray(data.filters) ? data.filters : []);
    const [matchType, setMatchType] = useState(data.matchType || 'all');

    // Check if this filter node is connected to a trigger
    const isConnected = edges.some(edge => edge.target === id);

    // Get available fields from connected trigger (passed via data)
    const availableFields = data.availableFields || {};

    const operatorOptions = [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'contains', label: 'Contains' },
        { value: 'not_contains', label: 'Not contains' },
        { value: 'greater_than', label: 'Greater than' },
        { value: 'greater_than_or_equal', label: 'Greater or equal' },
        { value: 'less_than', label: 'Less than' },
        { value: 'less_than_or_equal', label: 'Less or equal' },
    ];

    // Save to backend
    const save = (newFilters, newMatchType) => {
        if (data.livewireId && window.Livewire) {
            const component = window.Livewire.find(data.livewireId);
            if (component) {
                component.call('updateFilterConfig', {
                    nodeId: id,
                    filters: newFilters,
                    matchType: newMatchType
                });
            }
        }
    };

    const handleAddFilter = () => {
        const newFilter = {
            type: 'equals',
            data: { field: '', value: '' }
        };
        const updated = [...filters, newFilter];
        setFilters(updated);
        save(updated, matchType);
    };

    const handleRemoveFilter = (index) => {
        const updated = filters.filter((_, i) => i !== index);
        setFilters(updated);
        save(updated, matchType);
    };

    const handleFilterChange = (index, field, value) => {
        const updated = [...filters];
        if (field === 'type') {
            updated[index].type = value;
        } else {
            updated[index].data = { ...updated[index].data, [field]: value };
        }
        setFilters(updated);
        save(updated, matchType);
    };

    const handleMatchTypeChange = (value) => {
        setMatchType(value);
        save(filters, value);
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete all filters?')) {
            if (data.livewireId && window.Livewire) {
                window.Livewire.find(data.livewireId)?.call('deleteFilters');
            }
        }
    };

    return (
        <div className={`
            bg-white dark:bg-slate-900
            border-2 rounded-xl
            ${isConnected ? 'border-purple-500' : 'border-slate-300 dark:border-slate-600'}
            shadow-lg min-w-[300px] max-w-[400px]
            transition-all duration-200
        `}>
            <Handle
                type="target"
                position={Position.Left}
                className="!bg-purple-500 !w-3 !h-3 !border-2 !border-white"
            />

            {/* Header */}
            <div className="bg-purple-500 px-4 py-2.5 flex items-center justify-between rounded-t-lg">
                <div className="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4 text-white">
                        <path fillRule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clipRule="evenodd" />
                    </svg>
                    <span className="text-xs font-bold text-white uppercase tracking-wider">Filter Logic</span>
                </div>

                <div className="flex items-center gap-2">
                    {/* Match Type Badge */}
                    <span className="text-[10px] px-2 py-0.5 rounded font-bold uppercase text-white bg-purple-600">
                        {matchType === 'all' ? 'AND' : 'OR'}
                    </span>

                    {/* Expand/Collapse */}
                    <button
                        onClick={() => setIsExpanded(!isExpanded)}
                        className="nodrag text-white/80 hover:text-white transition-colors"
                        title={isExpanded ? "Collapse" : "Expand"}
                        disabled={!isConnected}
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                            className={`w-4 h-4 transition-transform ${isExpanded ? 'rotate-180' : ''} ${!isConnected ? 'opacity-50' : ''}`}>
                            <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                        </svg>
                    </button>

                    {/* Delete */}
                    <button
                        onClick={handleDelete}
                        className="nodrag text-white/80 hover:text-white transition-colors"
                        title="Delete Filters"
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
                    /* Not Connected */
                    <div className="text-center py-4">
                        <div className="text-slate-400 dark:text-slate-500 text-sm mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-8 h-8 mx-auto mb-2 opacity-50">
                                <path fillRule="evenodd" d="M4.25 2A2.25 2.25 0 002 4.25v2.5A2.25 2.25 0 004.25 9h2.5A2.25 2.25 0 009 6.75v-2.5A2.25 2.25 0 006.75 2h-2.5zm0 9A2.25 2.25 0 002 13.25v2.5A2.25 2.25 0 004.25 18h2.5A2.25 2.25 0 009 15.75v-2.5A2.25 2.25 0 006.75 11h-2.5zm9-9A2.25 2.25 0 0011 4.25v2.5A2.25 2.25 0 0013.25 9h2.5A2.25 2.25 0 0018 6.75v-2.5A2.25 2.25 0 0015.75 2h-2.5zm0 9A2.25 2.25 0 0011 13.25v2.5A2.25 2.25 0 0013.25 18h2.5A2.25 2.25 0 0018 15.75v-2.5A2.25 2.25 0 0015.75 11h-2.5z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <div className="text-purple-500 font-medium text-sm">Connect data</div>
                        <div className="text-slate-400 text-xs mt-1">Connect a trigger to configure filters</div>
                    </div>
                ) : !isExpanded ? (
                    /* Collapsed View */
                    <div>
                        {filters.length > 0 ? (
                            <div className="space-y-2">
                                {filters.map((filter, index) => (
                                    <div key={index} className="bg-slate-50 dark:bg-slate-800 rounded-md p-2 px-3 text-xs border border-slate-200 dark:border-slate-700 flex items-center gap-2">
                                        <span className="text-slate-500 dark:text-slate-400 font-medium">
                                            {availableFields[filter.data?.field] || filter.data?.field || 'Field'}
                                        </span>
                                        <span className="text-purple-500 font-bold">
                                            {operatorOptions.find(o => o.value === filter.type)?.label || filter.type}
                                        </span>
                                        <span className="text-slate-700 dark:text-slate-200 font-medium truncate">
                                            {filter.data?.value || '—'}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-slate-400 dark:text-slate-500 italic text-sm">
                                No filters configured
                            </div>
                        )}
                    </div>
                ) : (
                    /* Expanded View - Edit Form */
                    <div className="nodrag space-y-3">
                        {/* Match Type */}
                        <div>
                            <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                Match Type
                            </label>
                            <select
                                value={matchType}
                                onChange={(e) => handleMatchTypeChange(e.target.value)}
                                className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                    bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100
                                    focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none cursor-pointer"
                            >
                                <option value="all">All conditions must match (AND)</option>
                                <option value="any">Any condition must match (OR)</option>
                            </select>
                        </div>

                        {/* Filters List */}
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                    Conditions
                                </label>
                                <button
                                    onClick={handleAddFilter}
                                    className="text-xs text-purple-500 hover:text-purple-600 font-medium flex items-center gap-1"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
                                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clipRule="evenodd" />
                                    </svg>
                                    Add
                                </button>
                            </div>

                            {filters.length === 0 ? (
                                <div className="text-center py-4 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg">
                                    <div className="text-slate-400 text-sm">No conditions yet</div>
                                    <button
                                        onClick={handleAddFilter}
                                        className="mt-2 text-sm text-purple-500 hover:text-purple-600 font-medium"
                                    >
                                        + Add first condition
                                    </button>
                                </div>
                            ) : (
                                filters.map((filter, index) => (
                                    <div key={index} className="bg-slate-50 dark:bg-slate-800 rounded-lg p-3 border border-slate-200 dark:border-slate-700">
                                        <div className="flex items-start gap-2">
                                            <div className="flex-1 space-y-2">
                                                {/* Field Select */}
                                                <select
                                                    value={filter.data?.field || ''}
                                                    onChange={(e) => handleFilterChange(index, 'field', e.target.value)}
                                                    className="w-full px-2 py-1.5 text-xs border border-slate-300 dark:border-slate-600 rounded 
                                                        bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100
                                                        focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none"
                                                >
                                                    <option value="">Select field...</option>
                                                    {Object.entries(availableFields).map(([value, label]) => (
                                                        <option key={value} value={value}>{label}</option>
                                                    ))}
                                                </select>

                                                {/* Operator Select */}
                                                <select
                                                    value={filter.type || 'equals'}
                                                    onChange={(e) => handleFilterChange(index, 'type', e.target.value)}
                                                    className="w-full px-2 py-1.5 text-xs border border-slate-300 dark:border-slate-600 rounded 
                                                        bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100
                                                        focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none"
                                                >
                                                    {operatorOptions.map(op => (
                                                        <option key={op.value} value={op.value}>{op.label}</option>
                                                    ))}
                                                </select>

                                                {/* Value Input */}
                                                <input
                                                    type="text"
                                                    value={filter.data?.value || ''}
                                                    onChange={(e) => handleFilterChange(index, 'value', e.target.value)}
                                                    placeholder="Value..."
                                                    className="w-full px-2 py-1.5 text-xs border border-slate-300 dark:border-slate-600 rounded 
                                                        bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100
                                                        focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none"
                                                />
                                            </div>

                                            {/* Remove Button */}
                                            <button
                                                onClick={() => handleRemoveFilter(index)}
                                                className="text-slate-400 hover:text-red-500 transition-colors p-1"
                                                title="Remove condition"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
                                                    <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                )}
            </div>

            <Handle
                type="source"
                position={Position.Right}
                className="!bg-purple-500 !w-3 !h-3 !border-2 !border-white"
            />
        </div>
    );
};

export default FilterNode;
