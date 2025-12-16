<?php

namespace Voodflow\Voodflow\Execution;

use Voodflow\Voodflow\Models\Execution;
use Voodflow\Voodflow\Models\Node;

/**
 * Execution Context
 *
 * Contains all data needed for a node to execute:
 * - Current workflow execution
 * - Current node configuration
 * - Input from previous node
 * - Event that triggered the workflow
 */
class ExecutionContext
{
    public function __construct(
        public Execution $execution,
        public Node $node,
        public array $input,
        public array $config,
        public string $eventClass,
    ) {}

    /**
     * Get a config value with optional default
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Get an input value with optional default
     */
    public function getInput(string $key, mixed $default = null): mixed
    {
        return data_get($this->input, $key, $default);
    }

    /**
     * Get the execution result from another node in this workflow
     * 
     * Used by conditional/routing nodes to check results from action nodes
     */
    public function getNodeResult(string $nodeId): array
    {
        $executionNode = $this->execution
            ->executionNodes()
            ->where('node_id', $nodeId)
            ->first();

        if (!$executionNode) {
            return ['success' => false, 'error' => 'Node not executed yet'];
        }

        return [
            'success' => $executionNode->status === 'completed',
            'output' => $executionNode->output ?? [],
            'error' => $executionNode->error,
        ];
    }
}
