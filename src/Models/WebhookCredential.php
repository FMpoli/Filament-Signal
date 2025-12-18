<?php

namespace Voodflow\Voodflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookCredential extends Model
{
    protected $table = 'voodflow_webhook_credentials';

    protected $fillable = [
        'workflow_id',
        'auth_type',
        'secret',
        'validation_config',
        'is_active',
        'last_verified_at',
    ];

    protected $casts = [
        'secret' => 'encrypted', // Laravel 11+ auto-encryption
        'validation_config' => 'array',
        'is_active' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    protected $hidden = [
        'secret', // Never expose in JSON
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Validate incoming webhook request
     */
    public function validateRequest(array $headers, string $body): bool
    {
        if ($this->auth_type === 'none') {
            return true;
        }

        if ($this->auth_type === 'secret_key') {
            $headerName = $this->validation_config['header_name'] ?? 'X-Webhook-Secret';
            $providedSecret = $headers[$headerName] ?? null;
            
            return hash_equals($this->secret, $providedSecret);
        }

        if ($this->auth_type === 'signature') {
            $headerName = $this->validation_config['signature_header'] ?? 'X-Webhook-Signature';
            $providedSignature = $headers[$headerName] ?? null;
            $algorithm = $this->validation_config['algorithm'] ?? 'sha256';
            
            $computedSignature = hash_hmac($algorithm, $body, $this->secret);
            
            return hash_equals($computedSignature, $providedSignature);
        }

        return false;
    }

    public function markAsVerified(): void
    {
        $this->update(['last_verified_at' => now()]);
    }
}
