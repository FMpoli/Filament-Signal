<?php

namespace Voodflow\Voodflow\Contracts;

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
     * Execute the node logic with ExecutionContext
     * 
     * Modern signature using ExecutionContext and ExecutionResult
     * 
     * @param \Voodflow\Voodflow\Execution\ExecutionContext $context
     * @return \Voodflow\Voodflow\Execution\ExecutionResult
     */
    public function execute(\Voodflow\Voodflow\Execution\ExecutionContext $context): \Voodflow\Voodflow\Execution\ExecutionResult;

    /**
     * Validate the node configuration
     * 
     * @param array $config The node configuration
     * @return array Array of validation errors, empty if valid
     */
    public function validate(array $config): array;
}

