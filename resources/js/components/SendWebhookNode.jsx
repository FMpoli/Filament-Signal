import React, { useState, useEffect } from 'react';
import { Handle, Position, useReactFlow, useEdges, useNodes } from 'reactflow';
import ConfirmModal from './ConfirmModal';
import AddNodeButton from './AddNodeButton';
import VoodflowLogo from './VoodflowLogo';

/**
 * SendWebhook Node Component
 * 
 * Type: voodflow_send_webhook
 * Description: Send webhook with configuration
 */
const SendWebhookNode = ({ id, data }) => {
    const { setNodes } = useReactFlow();
    const edges = useEdges(); // Use useEdges hook
    const allNodes = useNodes(); // Use useNodes hook

    const [isExpanded, setIsExpanded] = useState(data.isNew || false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    // Config state
    const [label, setLabel] = useState(data.label || 'Send Webhook');
    const [description, setDescription] = useState(data.description || '');
    const [url, setUrl] = useState(data.url || '');
    const [method, setMethod] = useState(data.method || 'POST');
    const [payloadMode, setPayloadMode] = useState(data.payloadMode || 'payload');
    const [signingSecret, setSigningSecret] = useState(data.signingSecret || '');
    const [showSecret, setShowSecret] = useState(false);

    // Selected fields: ["path.to.field", "another.field"]
    const [selectedPayloadFields, setSelectedPayloadFields] = useState(data.payloadFields || []);

    // Initial secret generation if new
    useEffect(() => {
        if (!signingSecret && data.isNew) {
            const randomSecret = 'whsec_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
            setSigningSecret(randomSecret);
            // Don't save yet, wait for user interaction or explicitly save on mount if needed?
            // Better to just set local state. If user collapses, it will save.
            updateNodeData({ signingSecret: randomSecret });
        }
    }, [data.isNew]);

    // Update node data helper without backend save (for immediate UI response)
    const updateNodeData = (updates) => {
        setNodes((nds) => nds.map((node) => {
            if (node.id === id) {
                return {
                    ...node,
                    data: {
                        ...node.data,
                        ...updates
                    }
                };
            }
            return node;
        }));
    };

    // Handle collapse/expand toggle and update isNew and SAVE
    const handleCollapse = () => {
        const newExpandedState = !isExpanded;
        setIsExpanded(newExpandedState);

        // Bring node to front when expanding
        if (newExpandedState) {
            setNodes((nds) => nds.map((node) => {
                if (node.id === id) {
                    return {
                        ...node,
                        style: { ...node.style, zIndex: 1000 }
                    };
                }
                return node;
            }));
        } else {
            // Reset Z-Index on collapse
            setNodes((nds) => nds.map((node) => {
                if (node.id === id && node.style?.zIndex === 1000) {
                    const newStyle = { ...node.style };
                    delete newStyle.zIndex;
                    return { ...node, style: newStyle };
                }
                return node;
            }));
        }

        if (data.isNew && !newExpandedState) {
            updateNodeData({ isNew: false });
        }
        // Ensure everything is saved when collapsing
        if (!newExpandedState) {
            save(label, description, url, method, payloadMode, signingSecret, selectedPayloadFields);
        }
    };

    // Check connection
    const isConnected = edges.some(edge => edge.source === id);

    // Handle adding a connected node
    const handleAddConnectedNode = (nodeType) => {
        if (!data.livewireId || !window.Livewire) return;
        const component = window.Livewire.find(data.livewireId);
        if (!component) return;

        component.call('createGenericNode', {
            type: nodeType,
            sourceNodeId: id,
            sourceHandle: null // Single handle
        });

        setIsExpanded(false);
        if (data.isNew) {
            updateNodeData({ isNew: false });
        }
        save(label, description, url, method, payloadMode, signingSecret, selectedPayloadFields);
    };

    // Save configuration to backend
    const save = (
        newLabel = label,
        newDescription = description,
        newUrl = url,
        newMethod = method,
        newPayloadMode = payloadMode,
        newSigningSecret = signingSecret,
        newPayloadFields = selectedPayloadFields
    ) => {
        // Also update local ReactFlow data to reflect changes immediately in UI if needed elsewhere
        updateNodeData({
            label: newLabel,
            description: newDescription,
            url: newUrl,
            method: newMethod,
            payloadMode: newPayloadMode,
            signingSecret: newSigningSecret,
            payloadFields: newPayloadFields
        });

        if (data.livewireId && window.Livewire) {
            const component = window.Livewire.find(data.livewireId);
            if (component) {
                component.call('updateNodeConfig', {
                    nodeId: id,
                    label: newLabel,
                    description: newDescription,
                    url: newUrl,
                    method: newMethod,
                    payloadMode: newPayloadMode,
                    signingSecret: newSigningSecret,
                    payloadFields: newPayloadFields
                });
            }
        }
    };

    // Field handlers
    const handleLabelChange = (val) => { setLabel(val); save(val, description, url, method, payloadMode, signingSecret, selectedPayloadFields); };
    const handleDescriptionChange = (val) => { setDescription(val); save(label, val, url, method, payloadMode, signingSecret, selectedPayloadFields); };
    const handleUrlChange = (val) => { setUrl(val); save(label, description, val, method, payloadMode, signingSecret, selectedPayloadFields); };
    const handleMethodChange = (val) => { setMethod(val); save(label, description, url, val, payloadMode, signingSecret, selectedPayloadFields); };
    const handlePayloadModeChange = (val) => { setPayloadMode(val); save(label, description, url, method, val, signingSecret, selectedPayloadFields); };
    const handleSigningSecretChange = (val) => { setSigningSecret(val); save(label, description, url, method, payloadMode, val, selectedPayloadFields); };

    // Payload Field Toggling
    const togglePayloadField = (fieldPath) => {
        let newFields;
        if (selectedPayloadFields.includes(fieldPath)) {
            newFields = selectedPayloadFields.filter(f => f !== fieldPath);
        } else {
            newFields = [...selectedPayloadFields, fieldPath];
        }
        setSelectedPayloadFields(newFields);
        save(label, description, url, method, payloadMode, signingSecret, newFields);
    };

    const toggleAllGroup = (fieldsInGroup) => {
        const pathsInGroup = fieldsInGroup.map(f => f.path);
        const allSelected = pathsInGroup.every(p => selectedPayloadFields.includes(p));

        let newFields = [...selectedPayloadFields];
        if (allSelected) {
            newFields = newFields.filter(p => !pathsInGroup.includes(p));
        } else {
            pathsInGroup.forEach(p => {
                if (!newFields.includes(p)) newFields.push(p);
            });
        }
        setSelectedPayloadFields(newFields);
        save(label, description, url, method, payloadMode, signingSecret, newFields);
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

    // Regenerate secret
    const regenerateSecret = () => {
        const newSecret = 'whsec_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        handleSigningSecretChange(newSecret);
    };

    // --- Dynamic Field Discovery + Grouping ---
    const availableFieldsMap = React.useMemo(() => {
        // Find incoming edge
        const incomingEdge = edges.find(edge => edge.target === id);
        if (!incomingEdge) return {};

        // Helper to find trigger
        const findTriggerEventClass = (nodeId, visited = new Set()) => {
            if (visited.has(nodeId)) return null;
            visited.add(nodeId);

            const node = allNodes.find(n => n.id === nodeId);
            if (!node) return null;

            // Check for trigger type (support both voodflow and legacy base33 prefixes)
            if (node.type === 'trigger' || node.type === 'voodflow_trigger' || node.type === 'base33_trigger') {
                return node.data?.eventClass;
            }

            // If not trigger, traverse back
            const incoming = edges.find(e => e.target === nodeId);
            if (!incoming) return null;

            return findTriggerEventClass(incoming.source, visited);
        };

        const eventClass = findTriggerEventClass(incomingEdge.source);
        if (!eventClass) return {};

        const map = data.filterFieldsMap || {};
        return map[eventClass] || {};
    }, [edges, allNodes, id, data.filterFieldsMap]);

    // Group the flat map: { 'Model.Field': { label: 'Model -> Field' } }
    const groupedFields = React.useMemo(() => {
        const groups = {};
        Object.entries(availableFieldsMap).forEach(([key, info]) => {
            const rawLabel = typeof info === 'object' ? info.label : info;
            // Split by "->" or " - " to guess group
            let group = 'General';
            let name = rawLabel;

            if (rawLabel.includes('->')) {
                const parts = rawLabel.split('->');
                group = parts[0].trim();
                name = parts.slice(1).join('->').trim();
            } else if (rawLabel.includes(' - ')) {
                const parts = rawLabel.split(' - ');
                group = parts[0].trim();
                name = parts.slice(1).join(' - ').trim();
            }

            if (!groups[group]) groups[group] = [];
            groups[group].push({ path: key, name: name });
        });
        return groups;
    }, [availableFieldsMap]);


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
                border-blue-500
                shadow-lg min-w-[500px] max-w-[600px]
                transition-all duration-200
                ${isExpanded ? 'z-50' : ''}
            `}>
                <Handle
                    type="target"
                    position={Position.Left}
                    className="!bg-slate-500 !w-3 !h-3 !border-2 !border-white"
                />

                {/* Header */}
                <div className="bg-blue-500 px-4 py-2.5 flex items-center justify-between rounded-t-lg">
                    <div className="flex items-center gap-2">
                        {/* Icon */}
                        <svg className="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        <span className="text-white font-bold text-sm tracking-wide break-all max-w-[300px] truncate">
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
                        !edges.some(edge => edge.target === id) ? (
                            <div className="text-center py-4">
                                <div className="flex justify-center opacity-50 mb-2">
                                    <VoodflowLogo width={60} height={60} />
                                </div>
                                <div className="text-blue-500 font-medium text-sm">Connect data</div>
                            </div>
                        ) : (
                            <div
                                className="flex flex-col gap-1"
                            >
                                <div className="font-medium text-slate-700 dark:text-slate-200 flex items-center justify-between">
                                    <span>{label}</span>
                                    <span className="text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 font-mono">
                                        {method}
                                    </span>
                                </div>
                                <div className="text-xs font-mono text-slate-400 truncate max-w-[350px]">
                                    {url || 'No URL configured'}
                                </div>
                            </div>
                        )
                    ) : (
                        <div className="nodrag space-y-4">
                            {/* Row 1: Name & URL */}
                            <div className="grid grid-cols-12 gap-3">
                                <div className="col-span-12 md:col-span-4">
                                    <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                        Name
                                    </label>
                                    <input
                                        type="text"
                                        value={label}
                                        onChange={(e) => handleLabelChange(e.target.value)}
                                        className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                            bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <div className="col-span-12 md:col-span-8">
                                    <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                        Endpoint URL
                                    </label>
                                    <input
                                        type="text"
                                        value={url}
                                        onChange={(e) => handleUrlChange(e.target.value)}
                                        placeholder="https://api.example.com/webhook"
                                        className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                            bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                                    />
                                </div>
                            </div>

                            {/* Row 2: Method & Payload Mode */}
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                        HTTP Method
                                    </label>
                                    <select
                                        value={method}
                                        onChange={(e) => handleMethodChange(e.target.value)}
                                        className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                            bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                        <option value="PATCH">PATCH</option>
                                        <option value="GET">GET</option>
                                        <option value="DELETE">DELETE</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                        Payload Mode
                                    </label>
                                    <select
                                        value={payloadMode}
                                        onChange={(e) => handlePayloadModeChange(e.target.value)}
                                        className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                            bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="payload">Event Payload</option>
                                        <option value="envelope">Payload + Envelope</option>
                                    </select>
                                </div>
                            </div>

                            {/* Row 3: Signing Secret */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 flex justify-between">
                                    <span>Signing Secret</span>
                                    <div className="flex gap-3">
                                        <button onClick={() => setShowSecret(!showSecret)} className="text-slate-500 hover:text-slate-700 font-normal normal-case flex items-center gap-1">
                                            {showSecret ? (
                                                <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                            ) : (
                                                <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            )}
                                            {showSecret ? 'Hide' : 'Show'}
                                        </button>
                                        <button onClick={regenerateSecret} className="text-blue-500 hover:text-blue-600 font-normal normal-case">
                                            Regenerate
                                        </button>
                                    </div>
                                </label>
                                <div className="relative">
                                    <input
                                        type={showSecret ? "text" : "password"}
                                        value={signingSecret}
                                        readOnly
                                        className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                            bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 outline-none font-mono tracking-widest"
                                    />
                                </div>
                                <div className="text-[10px] text-slate-400 mt-1">
                                    Used to sign the payload (HMAC-SHA256) so receiver can verify authenticity.
                                </div>
                            </div>

                            {/* Row 4: Description */}
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

                            {/* Payload Configuration */}
                            <div className="bg-slate-50 dark:bg-slate-900/50 p-3 rounded-lg border border-slate-200 dark:border-slate-700">
                                <div className="flex items-center gap-2 text-slate-600 dark:text-slate-300 mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
                                        <path fillRule="evenodd" d="M2 4a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H4a2 2 0 01-2-2V4zm2 0v12h12V4H4z" clipRule="evenodd" />
                                        <path d="M6 7a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm0 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1z" />
                                    </svg>
                                    <span className="text-xs font-bold uppercase">Payload Configuration</span>
                                    <span className="text-[10px] text-slate-400 ml-auto font-normal">Select fields to include</span>
                                </div>

                                <div className="space-y-4">
                                    {Object.entries(groupedFields).map(([groupName, fields]) => (
                                        <div key={groupName} className="border-b border-slate-200 dark:border-slate-700 last:border-0 pb-3 last:pb-0">
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="text-xs font-semibold text-slate-700 dark:text-slate-300">{groupName}</span>
                                                <button
                                                    onClick={() => toggleAllGroup(fields)}
                                                    className="text-[10px] text-blue-500 hover:text-blue-700"
                                                >
                                                    Toggle All
                                                </button>
                                            </div>
                                            <div className="grid grid-cols-2 gap-2">
                                                {fields.map(field => {
                                                    const isSelected = selectedPayloadFields.includes(field.path);
                                                    return (
                                                        <label key={field.path} className="flex items-start gap-2 cursor-pointer group hover:bg-slate-100 dark:hover:bg-slate-800 p-1 rounded transition-colors">
                                                            <div className={`
                                                                w-3 h-3 mt-0.5 rounded border flex items-center justify-center transition-colors
                                                                ${isSelected ? 'bg-blue-500 border-blue-500' : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 group-hover:border-blue-400'}
                                                            `}>
                                                                {isSelected && <svg className="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={4} d="M5 13l4 4L19 7" /></svg>}
                                                            </div>
                                                            <input
                                                                type="checkbox"
                                                                className="hidden"
                                                                checked={isSelected}
                                                                onChange={() => togglePayloadField(field.path)}
                                                            />
                                                            <span className={`text-[11px] leading-tight ${isSelected ? 'text-slate-900 dark:text-slate-100 font-medium' : 'text-slate-500 dark:text-slate-400'}`}>
                                                                {field.name}
                                                            </span>
                                                        </label>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ))}
                                    {Object.keys(availableFieldsMap).length === 0 && (
                                        <div className="text-xs text-slate-500 text-center py-4">
                                            No fields available. Please connect a trigger first.
                                        </div>
                                    )}
                                </div>
                            </div>

                        </div>
                    )}
                </div>

                {/* Single Output Handle */}
                <Handle
                    id="output"
                    type="source"
                    position={Position.Right}
                    className={`!w-3 !h-3 !border-2 !border-white !bg-slate-400
                        ${!isConnected ? 'opacity-0' : 'opacity-100'}
                    `}
                    style={{ top: '50%', right: '-6px' }}
                />
                {!isConnected && (
                    <div
                        className="absolute right-0 translate-x-1/2 -translate-y-1/2 flex items-center justify-center"
                        style={{ top: '50%' }}
                    >
                        <AddNodeButton
                            onAddNode={(type) => handleAddConnectedNode(type)}
                            sourceNodeId={id}
                            livewireId={data.livewireId}
                            availableNodes={data.availableNodes}
                            color="slate"
                        />
                    </div>
                )}
            </div>
        </>
    );
};

export default SendWebhookNode;