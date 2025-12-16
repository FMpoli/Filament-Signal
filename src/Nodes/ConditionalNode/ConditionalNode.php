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
            'group' => 'Flow Control',

            // NEW: Multiple output handles
            'positioning' => [
                'inputs' => [
                    [
                        'id' => 'main',
                        'type' => 'main',
                        'label' => 'Input',
                        'required' => true,
                    ],
                ],
                'outputs' => [
                    [
                        'id' => 'true',
                        'type' => 'main',
                        'label' => 'True',
                        'color' => 'green',
                    ],
                    [
                        'id' => 'false',
                        'type' => 'main',
                        'label' => 'False',
                        'color' => 'red',
                    ],
                ],
            ],

            // NEW: Data flow configuration
            'data_flow' => [
                'accepts_input' => true,
                'produces_output' => true,
                'output_schema' => 'passthrough', // Passes input data unchanged
            ],
        ];
    }

    public static function defaultConfig(): array
    {
        return [
            'label' => 'Condition',
            'description' => '',
            'source_node_id' => null, // ID of the action node to evaluate
        ];
    }

    public function validate(array $config): array
    {
        $errors = [];

        if (empty($config['source_node_id'])) {
            $errors['source_node_id'] = 'Source action node is required';
        }

        return $errors;
    }

    /**
     * Execute the conditional logic
     *
     * Routes data to 'true' or 'false' output based on the result
     * from the connected action node
     */
    public function execute(\Voodflow\Voodflow\Execution\ExecutionContext $context): \Voodflow\Voodflow\Execution\ExecutionResult
    {
        $sourceNodeId = $context->getConfig('source_node_id');

        if (! $sourceNodeId) {
            return \Voodflow\Voodflow\Execution\ExecutionResult::failure(
                'No source action node configured'
            );
        }

        // Get the result from the source action node
        // This would be stored in the execution context or execution_nodes table
        $actionResult = $context->getNodeResult($sourceNodeId);

        // Determine which output to route to
        $isTrue = $actionResult['success'] ?? false;

        // Route to appropriate output handle
        return \Voodflow\Voodflow\Execution\ExecutionResult::success($context->input)
            ->toOutput($isTrue ? 'true' : 'false');
    }
}
