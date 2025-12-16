<?php

namespace Voodflow\Voodflow\Services;

use Illuminate\Support\Facades\Log;
use Voodflow\Voodflow\Execution\ExecutionContext;
use Voodflow\Voodflow\Execution\ExecutionResult;
use Voodflow\Voodflow\Models\Execution;
use Voodflow\Voodflow\Models\ExecutionNode;
use Voodflow\Voodflow\Models\Workflow;

/**
 * Workflow Executor
 *
 * Generic workflow execution engine.
 * Executes nodes sequentially through node->execute()
 * without knowing about specific node types.
 */
class WorkflowExecutor
{
    public function __construct(
        protected NodeRegistry $nodeRegistry
    ) {}

    /**
     * Execute a workflow
     */
    public function execute(Workflow $workflow, array $initialPayload, string $eventClass): Execution
    {
        // Create execution record
        $execution = Execution::create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'started_at' => now(),
            'input_context' => $initialPayload,
        ]);

        try {
            // Get workflow nodes in execution order
            $nodes = $workflow->nodes()
                ->orderBy('order')
                ->get();

            $currentPayload = $initialPayload;

            // Execute each node sequentially
            foreach ($nodes as $node) {
                $result = $this->executeNode($execution, $node, $currentPayload, $eventClass);

                if (! $result->success) {
                    // Node failed - stop execution
                    $execution->update([
                        'status' => 'failed',
                        'finished_at' => now(),
                    ]);

                    return $execution;
                }

                // Use node output as input for next node
                $currentPayload = $result->output;

                // Handle conditional routing
                if ($result->nextNodeId) {
                    // TODO: Implement conditional routing
                    // For now, continue sequential execution
                }
            }

            // All nodes executed successfully
            $execution->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Workflow execution failed', [
                'workflow_id' => $workflow->id,
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);

            $execution->update([
                'status' => 'failed',
                'finished_at' => now(),
            ]);
        }

        return $execution;
    }

    /**
     * Execute a single node
     */
    protected function executeNode(
        Execution $execution,
        \Voodflow\Voodflow\Models\Node $node,
        array $input,
        string $eventClass
    ): ExecutionResult {
        // Create execution node record
        $executionNode = ExecutionNode::create([
            'execution_id' => $execution->id,
            'node_id' => $node->id,
            'status' => 'running',
            'input' => $input,
            'started_at' => now(),
        ]);

        try {
            // Get node class from registry
            $nodeClass = $this->nodeRegistry->get($node->type);

            if (! $nodeClass) {
                throw new \Exception("Unknown node type: {$node->type}");
            }

            // Instantiate and execute node
            $nodeInstance = new $nodeClass;

            // Build execution context
            $context = new ExecutionContext(
                execution: $execution,
                node: $node,
                input: $input,
                config: $node->config ?? [],
                eventClass: $eventClass,
            );

            // Execute using new interface
            $result = $nodeInstance->execute($context);

            // Update execution node record
            $executionNode->update([
                'status' => $result->success ? 'completed' : 'failed',
                'output' => $result->output,
                'error' => $result->error,
                'finished_at' => now(),
            ]);

            return $result;

        } catch (\Exception $e) {
            // Node execution failed
            $executionNode->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            return ExecutionResult::failure($e->getMessage());
        }
    }
}
