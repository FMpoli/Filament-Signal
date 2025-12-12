<?php

namespace Base33\FilamentSignal\Contracts;

/**
 * Interface for Signal workflow nodes.
 * 
 * Implement this interface when creating custom nodes.
 */
interface NodeInterface
{
    /**
     * Get the node type identifier (e.g., 'trigger', 'filter', 'action')
     */
    public static function type(): string;

    /**
     * Get the node name
     */
    public static function name(): string;

    /**
     * Get the default configuration for this node
     */
    public static function defaultConfig(): array;

    /**
     * Get node metadata (author, version, color, icon, grouping)
     */
    public static function metadata(): array;

    /**
     * Execute the node logic
     * 
     * @param array $input The input data from previous nodes
     * @param array $config The node configuration
     * @return array The output data to pass to next nodes
     */
    public function execute(array $input, array $config): array;

    /**
     * Validate the node configuration
     * 
     * @param array $config The node configuration
     * @return array Array of validation errors, empty if valid
     */
    public function validate(array $config): array;
}
