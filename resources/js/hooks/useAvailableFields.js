import { useMemo } from 'react';
import { useNodes, useEdges } from 'reactflow';

/**
 * Hook to get available fields from the parent node
 * 
 * This hook automatically propagates data fields from any parent node,
 * whether it's a Trigger, Filter, Action, or any other node type.
 * 
 * @param {string} nodeId - The current node ID
 * @returns {Object} Available fields from parent node
 */
export const useAvailableFields = (nodeId) => {
    const nodes = useNodes();
    const edges = useEdges();

    return useMemo(() => {
        // Find the incoming edge to this node
        const incomingEdge = edges.find(edge => edge.target === nodeId);

        if (!incomingEdge) {
            return {
                fields: {},
                isConnected: false,
                parentNode: null,
            };
        }

        // Find the parent (source) node
        const parentNode = nodes.find(n => n.id === incomingEdge.source);

        if (!parentNode) {
            return {
                fields: {},
                isConnected: false,
                parentNode: null,
            };
        }

        // Get fields from parent node
        let fields = {};

        // If parent is a trigger, get fields from filterFieldsMap
        if (parentNode.type === 'trigger' ||
            parentNode.type === 'voodflow_trigger' ||
            parentNode.type === 'base33_trigger') {

            const eventClass = parentNode.data?.eventClass;
            const filterFieldsMap = parentNode.data?.filterFieldsMap || {};

            if (eventClass && filterFieldsMap[eventClass]) {
                fields = filterFieldsMap[eventClass];
            }
        }
        // For any other node type, get outputFields from node data
        else if (parentNode.data?.outputFields) {
            fields = parentNode.data.outputFields;
        }
        // Fallback: try to propagate from parent's parent (recursive)
        else {
            const parentFields = getFieldsRecursively(parentNode.id, nodes, edges, new Set());
            if (parentFields) {
                fields = parentFields;
            }
        }

        return {
            fields,
            isConnected: true,
            parentNode,
            parentType: parentNode.type,
        };
    }, [nodeId, nodes, edges]);
};

/**
 * Recursively find fields by traversing back through the graph
 * 
 * @param {string} nodeId - Node to start from
 * @param {Array} nodes - All nodes
 * @param {Array} edges - All edges
 * @param {Set} visited - Visited nodes (to prevent cycles)
 * @returns {Object|null} Fields or null
 */
function getFieldsRecursively(nodeId, nodes, edges, visited) {
    if (visited.has(nodeId)) return null;
    visited.add(nodeId);

    const incomingEdge = edges.find(e => e.target === nodeId);
    if (!incomingEdge) return null;

    const parentNode = nodes.find(n => n.id === incomingEdge.source);
    if (!parentNode) return null;

    // If parent is a trigger, return its fields
    if (parentNode.type === 'trigger' ||
        parentNode.type === 'voodflow_trigger' ||
        parentNode.type === 'base33_trigger') {

        const eventClass = parentNode.data?.eventClass;
        const filterFieldsMap = parentNode.data?.filterFieldsMap || {};

        if (eventClass && filterFieldsMap[eventClass]) {
            return filterFieldsMap[eventClass];
        }
    }

    // If parent has outputFields, return them
    if (parentNode.data?.outputFields) {
        return parentNode.data.outputFields;
    }

    // Continue recursively
    return getFieldsRecursively(parentNode.id, nodes, edges, visited);
}

export default useAvailableFields;
