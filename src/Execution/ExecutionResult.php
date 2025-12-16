<?php

namespace Voodflow\Voodflow\Execution;

/**
 * Execution Result
 *
 * Returned by node execution containing:
 * - Success status
 * - Output data for next node
 * - Error message if failed
 * - Optional next node ID for conditional routing
 */
class ExecutionResult
{
    public function __construct(
        public bool $success,
        public array $output = [],
        public ?string $error = null,
        public ?string $nextNodeId = null,
        public ?string $outputHandle = null, // NEW: For multiple output handles
    ) {}

    /**
     * Create a successful result
     */
    public static function success(array $output = []): self
    {
        return new self(
            success: true,
            output: $output,
        );
    }

    /**
     * Create a failed result
     */
    public static function failure(string $error, array $output = []): self
    {
        return new self(
            success: false,
            output: $output,
            error: $error,
        );
    }

    /**
     * Create a result with conditional routing
     */
    public static function route(string $nextNodeId, array $output = []): self
    {
        return new self(
            success: true,
            output: $output,
            nextNodeId: $nextNodeId,
        );
    }

    /**
     * Route output to a specific handle (for nodes with multiple outputs)
     * 
     * Example: return ExecutionResult::success($data)->toOutput('true');
     */
    public function toOutput(string $handleId): self
    {
        $this->outputHandle = $handleId;
        return $this;
    }
}
