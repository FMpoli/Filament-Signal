<?php

namespace Voodflow\Voodflow\Nodes;

use Voodflow\Voodflow\Contracts\NodeInterface;

class ConditionalNode implements NodeInterface
{
    public static function type(): string
    {
        return 'base33_conditional';
    }

    public static function name(): string
    {
        return 'Conditional';
    }

    public static function metadata(): array
    {
        return [
            'author' => 'Base33',
            'version' => '1.0.0',
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

    public function execute(array $inputData, array $config): array
    {
        return $inputData;
    }
}
