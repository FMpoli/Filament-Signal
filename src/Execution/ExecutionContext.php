<?php

namespace Voodflow\Voodflow\Execution;

use Voodflow\Voodflow\Exceptions\UnauthorizedCredentialAccessException;
use Voodflow\Voodflow\Models\Credential;
use Voodflow\Voodflow\Models\Execution;
use Voodflow\Voodflow\Models\Node;
use Voodflow\Voodflow\Services\CredentialProxy;

/**
 * Execution Context
 *
 * Contains all data needed for a node to execute:
 * - Current workflow execution
 * - Current node configuration
 * - Input from previous node
 * - Event that triggered the workflow
 * - Access to credentials via secure proxy
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

        if (! $executionNode) {
            return ['success' => false, 'error' => 'Node not executed yet'];
        }

        return [
            'success' => $executionNode->status === 'completed',
            'output' => $executionNode->output ?? [],
            'error' => $executionNode->error,
        ];
    }

    /**
     * Get credential proxy for secure access
     *
     * @param  int|string  $credentialId  Credential ID or name
     * @param  array  $nodeManifest  Node manifest with required scopes
     *
     * @throws UnauthorizedCredentialAccessException
     */
    public function getCredential(int | string $credentialId, array $nodeManifest = []): CredentialProxy
    {
        // Load credential
        if (is_numeric($credentialId)) {
            $credential = Credential::findOrFail($credentialId);
        } else {
            $credential = Credential::where('name', $credentialId)
                ->where('user_id', $this->execution->workflow->user_id)
                ->firstOrFail();
        }

        // Verify credential is active
        if (! $credential->isActive()) {
            throw new UnauthorizedCredentialAccessException(
                $credential->name,
                $this->node->type,
                "Credential status: {$credential->status}"
            );
        }

        // Verify workflow ownership
        if ($credential->user_id !== $this->execution->workflow->user_id) {
            throw new UnauthorizedCredentialAccessException(
                $credential->name,
                $this->node->type,
                'Credential belongs to different user'
            );
        }

        // Extract allowed scopes from node manifest
        $allowedScopes = $nodeManifest['credential_scopes'] ?? [];

        // Return proxy
        return new CredentialProxy(
            credential: $credential,
            nodeId: $this->node->id,
            workflowId: $this->execution->workflow_id,
            allowedScopes: $allowedScopes
        );
    }
}
