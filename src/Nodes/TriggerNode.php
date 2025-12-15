<?php

namespace Voodflow\Voodflow\Nodes;

use Voodflow\Voodflow\Contracts\NodeInterface;

/**
 * Trigger Node Handler
 * 
 * Type: trigger
 * Description: The workflow starting point
 */
class TriggerNode implements NodeInterface
{
    /**
     * The node type identifier
     */
    public static function type(): string
    {
        return 'trigger';
    }

    /**
     * Get the node name
     */
    public static function name(): string
    {
        return 'Trigger';
    }

    /**
     * Get the default configuration for this node
     */
    public static function defaultConfig(): array
    {
        return [
            'label' => 'Trigger',
            'description' => '',
            'eventClass' => null,
            'status' => 'draft',
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
            'color' => 'orange',
            'icon' => 'heroicon-o-bolt',
            'group' => 'Triggers',
            'positioning' => [
                'input' => false,
                'output' => true,
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
        // Trigger is a source node, pass input or event data through
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
        $errors = [];

        if (empty($config['eventClass'])) {
            $errors[] = 'Event Class is required';
        }

        return $errors;
    }
}
