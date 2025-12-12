import React, { useState, useMemo } from 'react';
import { Handle, Position, useNodes, useEdges, useReactFlow } from 'reactflow';
import ConfirmModal from './ConfirmModal';
import AddNodeButton from './AddNodeButton';

const FilterProNode = ({ id, data }) => {
    const { setNodes } = useReactFlow();
    const edges = useEdges();
    const allNodes = useNodes();

    const [isExpanded, setIsExpanded] = useState(data.isNew || false);
    const [filters, setFilters] = useState(Array.isArray(data.filters) ? data.filters : []);
    const [label, setLabel] = useState(data.label || 'FilterPro');
    const [description, setDescription] = useState(data.description || '');
    const [showDeleteModal, setShowDeleteModal] = useState(false);

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

    // Check if this filter node is connected to a trigger or another filter
    const isConnected = edges.some(edge => edge.target === id);
    const isOutputConnected = edges.some(edge => edge.source === id);

    // Dynamically get available fields from connected source (trigger or filter)
    const availableFields = useMemo(() => {
        // Find the edge that connects to this filter
        const incomingEdge = edges.find(edge => edge.target === id);
        if (!incomingEdge) return {};

        // Find the source node
        const sourceNode = allNodes.find(n => n.id === incomingEdge.source);
        if (!sourceNode) return {};

        // If source is trigger, get eventClass and look up fields
        if (sourceNode.type === 'trigger') {
            const eventClass = sourceNode.data?.eventClass;
            const filterFieldsMap = data.filterFieldsMap || {};
            if (!eventClass) return {};
            return filterFieldsMap[eventClass] || {};
        }

        // If source is another filter, propagate its available fields
        if (sourceNode.type.includes('filter')) {
            // Recursively find the trigger by traversing back
            const findTriggerEventClass = (nodeId, visited = new Set()) => {
                if (visited.has(nodeId)) return null;
                visited.add(nodeId);

                const edge = edges.find(e => e.target === nodeId);
                if (!edge) return null;

                const node = allNodes.find(n => n.id === edge.source);
                if (!node) return null;

                if (node.type === 'trigger') {
                    return node.data?.eventClass;
                }

                return findTriggerEventClass(node.id, visited);
            };

            const eventClass = findTriggerEventClass(sourceNode.id);
            const filterFieldsMap = data.filterFieldsMap || {};
            if (!eventClass) return {};
            return filterFieldsMap[eventClass] || {};
        }

        return {};
    }, [edges, allNodes, id, data.filterFieldsMap]);

    // Standard operators
    const standardOperators = [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'contains', label: 'Contains' },
        { value: 'not_contains', label: 'Not contains' },
        { value: 'greater_than', label: 'Greater than' },
        { value: 'greater_than_or_equal', label: 'Greater or equal' },
        { value: 'less_than', label: 'Less than' },
        { value: 'less_than_or_equal', label: 'Less or equal' },
    ];

    // Date-specific operators
    const dateOperators = [
        { value: 'equals', label: 'Is exactly' },
        { value: 'not_equals', label: 'Is not' },
        { value: 'greater_than', label: 'Is after' },
        { value: 'less_than', label: 'Is before' },
        { value: 'is_in_the_past', label: 'Is in the past' },
        { value: 'is_in_the_future', label: 'Is in the future' },
        { value: 'is_today', label: 'Is today' },
        { value: 'is_within_days', label: 'Is within X days' },
        { value: 'is_within_months', label: 'Is within X months' },
        { value: 'is_older_than_days', label: 'Is older than X days' },
        { value: 'is_older_than_months', label: 'Is older than X months' },
    ];

    // Get operators based on field type
    const getOperatorsForField = (fieldKey) => {
        const fieldInfo = availableFields[fieldKey];
        if (!fieldInfo) return standardOperators;

        const fieldType = typeof fieldInfo === 'object' ? fieldInfo.type : 'string';

        if (fieldType === 'date') {
            return dateOperators;
        }

        return standardOperators;
    };

    // Get field label
    const getFieldLabel = (fieldKey) => {
        const fieldInfo = availableFields[fieldKey];
        if (!fieldInfo) return fieldKey;
        return typeof fieldInfo === 'object' ? fieldInfo.label : fieldInfo;
    };

    // Check if operator requires a value input
    const operatorNeedsValue = (operator) => {
        return !['is_in_the_past', 'is_in_the_future', 'is_today'].includes(operator);
    };

    const operatorNeedsNumericValue = (operator) => {
        return ['is_within_days', 'is_within_months', 'is_older_than_days', 'is_older_than_months'].includes(operator);
    };

    // Save configuration to backend
    const save = (newFilters = filters, newLabel = label, newDescription = description) => {
        if (data.livewireId && window.Livewire) {
            const component = window.Livewire.find(data.livewireId);
            if (component) {
                component.call('updateNodeConfig', {
                    nodeId: id,
                    filters: newFilters,
                    label: newLabel,
                    description: newDescription,
                });
            }
        }
    };

    const handleLabelChange = (value) => {
        setLabel(value);
        save(filters, value, description);
    };

    const handleDescriptionChange = (value) => {
        setDescription(value);
        save(filters, label, value);
    };

    const handleAddFilter = () => {
        // If there are existing filters, set the logic of the last one to 'AND' by default
        let updatedFilters = [...filters];
        if (updatedFilters.length > 0) {
            updatedFilters[updatedFilters.length - 1].logic = 'AND';
        }

        const newFilter = {
            type: 'equals',
            data: { field: '', value: '' },
            logic: null // Last one has no subsequent logic
        };

        updatedFilters.push(newFilter);
        setFilters(updatedFilters);
        save(updatedFilters, label, description);
    };

    const handleRemoveFilter = (index) => {
        let updated = filters.filter((_, i) => i !== index);

        // If we removed an item, ensure the NEW last item has logic null
        if (updated.length > 0) {
            updated[updated.length - 1].logic = null;
        }

        setFilters(updated);
        save(updated, label, description);
    };

    const handleFilterChange = (index, field, value) => {
        const updated = [...filters];
        if (field === 'type') {
            updated[index].type = value;
            if (!operatorNeedsValue(value)) {
                updated[index].data = { ...updated[index].data, value: '' };
            }
        } else if (field === 'logic') {
            updated[index].logic = value;
        } else {
            updated[index].data = { ...updated[index].data, [field]: value };
            if (field === 'field') {
                updated[index].type = 'equals';
            }
        }
        setFilters(updated);
        save(updated, label, description);
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
                title="Delete FilterPro"
                message={`Are you sure you want to delete "${label}"?`}
                onConfirm={confirmDelete}
                onCancel={() => setShowDeleteModal(false)}
            />

            <div className={`
                relative
                bg-white dark:bg-slate-900
                border-2 rounded-xl
                ${isConnected ? 'border-purple-500' : 'border-slate-300 dark:border-slate-600'}
                shadow-lg min-w-[300px] max-w-[420px]
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
                                className={`w-4 h-4 transition-transform ${isExpanded ? 'rotate-180' : ''} ${!isConnected ? 'opacity-50' : ''}`}>
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
                            <div className="text-purple-500 font-medium text-sm">Connect data</div>
                        </div>
                    ) : !isExpanded ? (
                        /* Collapsed View */
                        <div>
                            {description && (
                                <div className="text-slate-500 dark:text-slate-400 text-xs italic mb-2">
                                    {description}
                                </div>
                            )}

                            {filters.length > 0 ? (
                                <div className="space-y-1">
                                    {filters.map((filter, index) => (
                                        <div key={index}>
                                            <div className="bg-slate-50 dark:bg-slate-800 rounded-md p-2 px-3 text-xs border border-slate-200 dark:border-slate-700 flex items-center gap-2">
                                                <span className="text-slate-500 dark:text-slate-400 font-medium">
                                                    {getFieldLabel(filter.data?.field) || 'Field'}
                                                </span>
                                                <span className="text-purple-500 font-bold">
                                                    {getOperatorsForField(filter.data?.field).find(o => o.value === filter.type)?.label || filter.type}
                                                </span>
                                                {operatorNeedsValue(filter.type) && (
                                                    <span className="text-slate-700 dark:text-slate-200 font-medium truncate">
                                                        {filter.data?.value || '—'}
                                                    </span>
                                                )}
                                            </div>
                                            {/* Logic Connector (Collapsed) */}
                                            {index < filters.length - 1 && (
                                                <div className="flex justify-center my-0.5">
                                                    <span className="text-[10px] uppercase font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-1 rounded">
                                                        {filter.logic || 'AND'}
                                                    </span>
                                                </div>
                                            )}
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
                                        focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none"
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
                                        focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none"
                                />
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
                                    filters.map((filter, index) => {
                                        const operators = getOperatorsForField(filter.data?.field);
                                        const needsValue = operatorNeedsValue(filter.type);
                                        const needsNumeric = operatorNeedsNumericValue(filter.type);

                                        return (
                                            <React.Fragment key={index}>
                                                <div className="bg-slate-50 dark:bg-slate-800 rounded-lg p-3 border border-slate-200 dark:border-slate-700">
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
                                                                {Object.entries(availableFields).map(([value, info]) => (
                                                                    <option key={value} value={value}>
                                                                        {typeof info === 'object' ? info.label : info}
                                                                    </option>
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
                                                                {operators.map(op => (
                                                                    <option key={op.value} value={op.value}>{op.label}</option>
                                                                ))}
                                                            </select>

                                                            {/* Value Input */}
                                                            {needsValue && (
                                                                <input
                                                                    type={needsNumeric ? 'number' : 'text'}
                                                                    value={filter.data?.value || ''}
                                                                    onChange={(e) => handleFilterChange(index, 'value', e.target.value)}
                                                                    placeholder={needsNumeric ? 'Number of days/months...' : 'Value...'}
                                                                    className="w-full px-2 py-1.5 text-xs border border-slate-300 dark:border-slate-600 rounded 
                                                                        bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100
                                                                        focus:ring-1 focus:ring-purple-500 focus:border-purple-500 outline-none"
                                                                />
                                                            )}
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

                                                {/* Logic Connector (Expanded) */}
                                                {index < filters.length - 1 && (
                                                    <div className="flex justify-center items-center gap-2 my-2 relative">
                                                        <div className="absolute inset-x-0 top-1/2 h-px bg-slate-200 dark:bg-slate-700 -z-10"></div>
                                                        <div className="flex bg-slate-100 dark:bg-slate-800 rounded-lg p-0.5 border border-slate-200 dark:border-slate-700">
                                                            <button
                                                                onClick={() => handleFilterChange(index, 'logic', 'AND')}
                                                                className={`px-3 py-1 text-[10px] font-bold rounded-md transition-colors ${(filter.logic || 'AND') === 'AND'
                                                                        ? 'bg-purple-500 text-white shadow-sm'
                                                                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'
                                                                    }`}
                                                            >
                                                                AND
                                                            </button>
                                                            <button
                                                                onClick={() => handleFilterChange(index, 'logic', 'OR')}
                                                                className={`px-3 py-1 text-[10px] font-bold rounded-md transition-colors ${filter.logic === 'OR'
                                                                        ? 'bg-purple-500 text-white shadow-sm'
                                                                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-400'
                                                                    }`}
                                                            >
                                                                OR
                                                            </button>
                                                        </div>
                                                    </div>
                                                )}
                                            </React.Fragment>
                                        );
                                    })
                                )}
                            </div>
                        </div>
                    )}
                </div>

                {/* Output Handle */}
                <Handle
                    type="source"
                    position={Position.Right}
                    className={`!bg-purple-500 !w-3 !h-3 !border-2 !border-white
                        ${!isOutputConnected ? 'opacity-0' : 'opacity-100'}
                    `}
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
                            color="purple"
                        />
                    </div>
                )}
            </div>
        </>
    );
};

export default FilterProNode;