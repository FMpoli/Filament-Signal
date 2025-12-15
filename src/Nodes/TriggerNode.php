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
     * Execute trigger logic with new signature
     */
    public function execute(\Voodflow\Voodflow\Execution\ExecutionContext $context): \Voodflow\Voodflow\Execution\ExecutionResult
    {
        // Trigger nodes typically don't execute, they listen for events
        // Pass through input for now
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

        if (empty($config['eventClass'])) {
            $errors[] = 'Event Class is required';
        }

        return $errors;
    }
}
