<?php

namespace Voodflow\Voodflow\Nodes;

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
        return 'base33_filter';
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
            'author' => 'Base33',
            'version' => '1.0.0',
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
     * Execute the node logic
     *
     * @param  array  $input  The input data from previous nodes
     * @param  array  $config  The node configuration
     * @return array The output data to pass to next nodes
     */
    public function execute(array $input, array $config): array
    {
        // TODO: Implement filter logic
        // Return input if it passes filters, or empty/null if it fails?
        // Or throw exception to stop flow?
        // Usually filters just stop propagation if condition met.

        return $input;
    }

    /**
     * Validate the node configuration
     *
     * @param  array  $config  The node configuration
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
