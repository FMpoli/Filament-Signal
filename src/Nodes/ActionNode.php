<?php

namespace Base33\FilamentSignal\Nodes;

use Base33\FilamentSignal\Contracts\NodeInterface;

/**
 * Action Node Handler
 * 
 * Type: action
 * Description: Generic action node
 */
class ActionNode implements NodeInterface
{
    /**
     * The node type identifier
     */
    public static function type(): string
    {
        return 'action';
    }

    /**
     * Get the node name
     */
    public static function name(): string
    {
        return 'Action';
    }

    /**
     * Get the default configuration for this node
     */
    public static function defaultConfig(): array
    {
        return [
            'label' => 'Action',
            'description' => '',
            'actionType' => 'log',
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
            'color' => 'blue',
            'icon' => 'heroicon-o-check-circle',
            'group' => 'Actions',
            'positioning' => [
                'input' => true,
                'output' => false,
            ],
        ];
    }

    /**
     * Execute the node logic
     * 
     * @param array $input The input data from previous nodes
     * @param array $config The node configuration
     * @return array The output data to pass to next nodes
     */
    public function execute(array $input, array $config): array
    {
        return $input;
    }

    /**
     * Validate the node configuration
     * 
     * @param array $config The node configuration
     * @return array Array of validation errors, empty if valid
     */
    public function validate(array $config): array
    {
        return [];
    }
}
