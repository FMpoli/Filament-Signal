<?php

namespace Voodflow\Voodflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialAccessLog extends Model
{
    protected $table = 'voodflow_credential_access_logs';

    const UPDATED_AT = null; // Only created_at

    protected $fillable = [
        'credential_id',
        'node_id',
        'workflow_id',
        'action',
        'params',
        'status',
        'error_message',
        'ip_address',
        'user_agent',
        'is_suspicious',
        'suspicious_reason',
    ];

    protected $casts = [
        'params' => 'array',
        'is_suspicious' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    // ==================== STATIC METHODS FOR LOGGING ====================

    public static function logAccess(
        int $credentialId,
        string $action,
        array $params = [],
        ?int $nodeId = null,
        ?int $workflowId = null,
        string $status = 'success',
        ?string $error = null
    ): self {
        // Sanitize params (remove any sensitive data)
        $sanitizedParams = self::sanitizeParams($params);

        return self::create([
            'credential_id' => $credentialId,
            'node_id' => $nodeId,
            'workflow_id' => $workflowId,
            'action' => $action,
            'params' => $sanitizedParams,
            'status' => $status,
            'error_message' => $error,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Sanitize parameters to remove any credentials or sensitive data
     */
    protected static function sanitizeParams(array $params): array
    {
        $sensitiveKeys = [
            'password',
            'secret',
            'api_key',
            'token',
            'access_token',
            'refresh_token',
            'client_secret',
            'private_key',
            'passphrase',
            'credentials',
        ];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $params[$key] = self::sanitizeParams($value);
            } elseif (in_array(strtolower($key), $sensitiveKeys)) {
                $params[$key] = '[REDACTED]';
            }
        }

        return $params;
    }

    /**
     * Mark this log as suspicious
     */
    public function markAsSuspicious(string $reason): void
    {
        $this->update([
            'is_suspicious' => true,
            'suspicious_reason' => $reason,
        ]);
    }

    // ==================== SCOPES ====================

    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failure');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
