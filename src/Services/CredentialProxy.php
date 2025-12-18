<?php

namespace Voodflow\Voodflow\Services;

use Voodflow\Voodflow\Exceptions\UnauthorizedCredentialAccessException;
use Voodflow\Voodflow\Exceptions\UnsupportedCredentialActionException;
use Voodflow\Voodflow\Models\Credential;
use Voodflow\Voodflow\Models\CredentialAccessLog;

/**
 * Credential Proxy
 *
 * This service sits between nodes and credentials, providing:
 * 1. Authorization (scope checking based on node manifest)
 * 2. Audit trail (every access is logged)
 * 3. Abstraction (nodes never see raw credentials)
 */
class CredentialProxy
{
    public function __construct(
        protected Credential $credential,
        protected ?int $nodeId = null,
        protected ?int $workflowId = null,
        protected array $allowedScopes = []
    ) {}

    /**
     * Generic action executor with scope validation
     */
    public function execute(string $action, array $params = []): mixed
    {
        // Check if action is allowed for this node
        if (! $this->isActionAllowed($action)) {
            throw new UnauthorizedCredentialAccessException(
                $this->credential->name,
                "Node #{$this->nodeId}",
                "Action '{$action}' requires scope that is not granted"
            );
        }

        // Log access attempt
        $log = CredentialAccessLog::logAccess(
            credentialId: $this->credential->id,
            action: $action,
            params: $params,
            nodeId: $this->nodeId,
            workflowId: $this->workflowId,
            status: 'success'
        );

        try {
            // Execute the action
            $result = match ($action) {
                'get_smtp_client' => $this->getSmtpClient(),
                'get_oauth_client' => $this->getOAuthClient(),
                'get_api_client' => $this->getApiClient(),
                'get_ssh_connection' => $this->getSshConnection(),
                default => throw new UnsupportedCredentialActionException($action, $this->credential->type)
            };

            // Mark credential as used
            $this->credential->markAsUsed();

            return $result;

        } catch (\Exception $e) {
            // Log failure
            $log->update([
                'status' => 'failure',
                'error_message' => $e->getMessage(),
            ]);

            // Record error on credential
            $this->credential->recordError($e->getMessage());

            throw $e;
        }
    }

    /**
     * Check if the action is allowed based on node's manifest scopes
     */
    protected function isActionAllowed(string $action): bool
    {
        // If no scopes specified, allow all (backward compatibility)
        if (empty($this->allowedScopes)) {
            return true;
        }

        // Map actions to required scopes
        $scopeMap = [
            'get_smtp_client' => 'smtp.send',
            'get_oauth_client' => 'oauth.access',
            'get_api_client' => 'api.request',
            'get_ssh_connection' => 'ssh.connect',
        ];

        $requiredScope = $scopeMap[$action] ?? $action;

        return in_array($requiredScope, $this->allowedScopes);
    }

    // ==================== ACTION IMPLEMENTATIONS ====================

    protected function getSmtpClient(): array
    {
        if ($this->credential->type !== 'basic_auth') {
            throw new UnsupportedCredentialActionException('get_smtp_client', $this->credential->type);
        }

        $creds = $this->credential->credentials;

        return [
            'host' => $creds['host'] ?? '',
            'port' => $creds['port'] ?? 587,
            'username' => $creds['username'] ?? '',
            'password' => $creds['password'] ?? '',
            'encryption' => $creds['encryption'] ?? 'tls',
        ];
    }

    protected function getOAuthClient(): array
    {
        if ($this->credential->type !== 'oauth2') {
            throw new UnsupportedCredentialActionException('get_oauth_client', $this->credential->type);
        }

        $creds = $this->credential->credentials;

        return [
            'client_id' => $creds['client_id'] ?? '',
            'client_secret' => $creds['client_secret'] ?? '',
            'access_token' => $creds['access_token'] ?? '',
            'refresh_token' => $creds['refresh_token'] ?? '',
            'expires_at' => $this->credential->expires_at?->toIso8601String(),
        ];
    }

    protected function getApiClient(): array
    {
        if ($this->credential->type !== 'api_token') {
            throw new UnsupportedCredentialActionException('get_api_client', $this->credential->type);
        }

        $creds = $this->credential->credentials;

        return [
            'api_key' => $creds['api_key'] ?? '',
            'api_secret' => $creds['api_secret'] ?? null,
            'base_url' => $this->credential->metadata['base_url'] ?? '',
        ];
    }

    protected function getSshConnection(): array
    {
        if ($this->credential->type !== 'ssh_key') {
            throw new UnsupportedCredentialActionException('get_ssh_connection', $this->credential->type);
        }

        $creds = $this->credential->credentials;

        return [
            'private_key' => $creds['private_key'] ?? '',
            'passphrase' => $creds['passphrase'] ?? null,
            'host' => $this->credential->metadata['host'] ?? '',
            'port' => $this->credential->metadata['port'] ?? 22,
            'username' => $this->credential->metadata['username'] ?? 'root',
        ];
    }

    // ==================== ANTI-LEAK PROTECTIONS ====================

    /**
     * Prevent dumping/debugging
     */
    public function __debugInfo(): array
    {
        return [
            'credential_id' => $this->credential->id,
            'credential_name' => $this->credential->name,
            'node_id' => $this->nodeId,
            'workflow_id' => $this->workflowId,
            'message' => '🔒 Credential data is protected',
        ];
    }

    /**
     * Prevent serialization
     */
    public function __sleep()
    {
        throw new \Voodflow\Voodflow\Exceptions\CredentialSerializationException;
    }
}
