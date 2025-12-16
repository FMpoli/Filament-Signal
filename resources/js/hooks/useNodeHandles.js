import { useMemo } from 'react';
import { useEdges } from 'reactflow';

/**
 * Hook to manage multiple input/output handles for a node
 * 
 * Provides information about which handles are connected and their states
 * 
 * @param {string} nodeId - The current node ID
 * @param {Array} inputHandles - Array of input handle definitions from metadata
 * @param {Array} outputHandles - Array of output handle definitions from metadata
 * @returns {Object} Handle connection states
 */
export const useNodeHandles = (nodeId, inputHandles = [], outputHandles = []) => {
    const edges = useEdges();

    return useMemo(() => {
        // Map input handles to their connection status
        const inputs = inputHandles.map(handle => {
            const isConnected = edges.some(
                edge => edge.target === nodeId && edge.targetHandle === handle.id
            );

            return {
                ...handle,
                isConnected,
            };
        });

        // Map output handles to their connection status
        const outputs = outputHandles.map(handle => {
            const isConnected = edges.some(
                edge => edge.source === nodeId && edge.sourceHandle === handle.id
            );

            const connectedEdges = edges.filter(
                edge => edge.source === nodeId && edge.sourceHandle === handle.id
            );

            return {
                ...handle,
                isConnected,
                connectionCount: connectedEdges.length,
                connectedEdges,
            };
        });

        // Check if ANY input is connected
        const hasInputConnection = inputs.some(h => h.isConnected);

        // Check if ANY output is connected
        const hasOutputConnection = outputs.some(h => h.isConnected);

        return {
            inputs,
            outputs,
            hasInputConnection,
            hasOutputConnection,
            isFullyConnected: hasInputConnection && hasOutputConnection,
        };
    }, [nodeId, inputHandles, outputHandles, edges]);
};

export default useNodeHandles;
