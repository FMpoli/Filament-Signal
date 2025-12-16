<?php

namespace Voodflow\Voodflow\Nodes\ConditionalNode;

use Voodflow\Voodflow\Contracts\NodeInterface;

class ConditionalNode implements NodeInterface
{
    public static function type(): string
    {
        return 'conditional';
    }

    public static function name(): string
    {
        return 'Conditional';
    }

    public static function metadata(): array
    {
        return [
            'author' => 'Voodflow',
            'version' => '1.0.0',
            'tier' => 'CORE',
            'category' => 'flow',
            'description' => 'Branch workflow based on conditions',
            'color' => 'warning',
            'icon' => 'heroicon-o-arrows-pointing-out',
            'group' => 'Logic',
            'positioning' => [
                'input' => true,
                'output' => false, // Handles maintained by React component (True/False)
            ],
        ];
    }

    public static function defaultConfig(): array
    {
        return [
            'label' => 'Condition',
            'description' => '',
        ];
    }

    public function validate(array $config): array
    {
        return [];
    }

    /**
     * Execute the conditional logic with new signature
     */
    public function execute(\Voodflow\Voodflow\Execution\ExecutionContext $context): \Voodflow\Voodflow\Execution\ExecutionResult
    {
        // For now, just pass through input - conditional logic not yet implemented
        return \Voodflow\Voodflow\Execution\ExecutionResult::success($context->input);
    }
}
