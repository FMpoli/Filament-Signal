<?php

namespace Voodflow\Voodflow\Nodes\TelegramNode;

use Voodflow\Voodflow\Contracts\NodeInterface;
use Voodflow\Voodflow\Execution\ExecutionContext;
use Voodflow\Voodflow\Execution\ExecutionResult;

/**
 * Telegram Node
 * 
 * Custom node for workflow automation
 * 
 * @author Voodflow
 * @version 1.0.0
 */
class TelegramNode implements NodeInterface
{
    public static function type(): string
    {
        return 'telegram_node';
    }
    
    public static function name(): string
    {
        return 'Telegram Node';
    }
    
    public static function defaultConfig(): array
    {
        return [
            'label' => 'Telegram Node',
            'description' => '',
            // Add your configuration fields here
        ];
    }
    
    public static function metadata(): array
    {
        return [
            'author' => 'Voodflow',
            'version' => '1.0.0',
            'tier' => 'CORE',
            'color' => 'blue',
            'icon' => 'heroicon-o-paper-airplane',
            'group' => 'Actions',
            'category' => 'action',
            'description' => 'Custom node for workflow automation',
            'license' => 'MIT',
            
            'positioning' => [
                'input' => true,
                'output' => true,
            ],
            
            'data_flow' => [
                'accepts_input' => true,
                'produces_output' => true,
                'output_schema' => 'passthrough',
            ],
        ];
    }
    
    /**
     * Execute the node logic
     */
    public function execute(ExecutionContext $context): ExecutionResult
    {
        // TODO: Implement your node logic here
        
        // Get input data from previous node
        $inputData = $context->input;
        
        // Get configuration
        // $config = $context->getConfig('field_name', 'default');
        
        // Process and return output
        return ExecutionResult::success($inputData);
        
        // For nodes with multiple outputs:
        // return ExecutionResult::success($data)->toOutput('handle_id');
    }
    
    /**
     * Validate node configuration
     */
    public function validate(array $config): array
    {
        $errors = [];
        
        // TODO: Add validation logic
        
        return $errors;
    }
}
