<?php

namespace Voodflow\Voodflow\Nodes\FilterNode;

use Voodflow\Voodflow\Contracts\NodeInterface;

/**
 * Filter Node Handler
 * 
 * Type: filter
 * Description: Filters data based on conditions
 */
class FilterNode implements NodeInterface
{
    /**
     * The node type identifier
     */
    public static function type(): string
    {
        return 'filter';
    }

    /**
     * Get the node name
     */
    public static function name(): string
    {
        return 'Filter';
    }

    /**
     * Get the default configuration for this node
     */
    public static function defaultConfig(): array
    {
        return [
            'label' => 'Filter',
            'description' => '',
            'filters' => [], // Array of filter conditions
            'logic' => 'AND', // AND / OR
        ];
    }

    /**
     * Get node metadata
     */
    public static function metadata(): array
    {
        return [
            'author' => 'Voodflow',
            'version' => '1.0.0',
            'category' => 'transform',
            'description' => 'Filter data based on conditions',
            'color' => 'purple',
            'icon' => 'heroicon-o-funnel',
            'group' => 'Filters',
            'positioning' => [
                'input' => true,
                'output' => true,
            ],
        ];
    }

    /**
     * Execute filter logic with new signature
     */
    public function execute(\Voodflow\Voodflow\Execution\ExecutionContext $context): \Voodflow\Voodflow\Execution\ExecutionResult
    {
        // Pass through for now - filtering logic not yet implemented
        return \Voodflow\Voodflow\Execution\ExecutionResult::success($context->input);
    }

    /**
     * Validate the node configuration
     * 
     * @param array $config The node configuration
     * @return array Array of validation errors, empty if valid
     */
    public function validate(array $config): array
    {
        $errors = [];

        // Example validation
        // if (empty($config['filters'])) {
        //     $errors[] = 'At least one filter condition is required';
        // }

        return $errors;
    }
}
