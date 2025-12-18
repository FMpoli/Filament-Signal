<?php

namespace Voodflow\Voodflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credential extends Model
{
    protected $table = 'voodflow_credentials';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'provider',
        'credentials',
        'metadata',
        'oauth_state',
        'oauth_callback_url',
        'expires_at',
        'status',
        'last_used_at',
        'last_error',
    ];

    protected $casts = [
        'credentials' => 'encrypted:array', // Laravel 11+ auto-encryption
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials', // Never expose in JSON
    ];

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(CredentialScope::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(CredentialAccessLog::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    // ==================== HELPERS ====================

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function revoke(): void
    {
        $this->update(['status' => 'revoked']);
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function recordError(string $error): void
    {
        $this->update([
            'last_error' => $error,
            'status' => 'error',
        ]);
    }

    // ==================== OAUTH2 SCOPE MANAGEMENT ====================

    public function addScope(string $scope): void
    {
        $this->scopes()->firstOrCreate(['scope' => $scope]);
    }

    public function removeScope(string $scope): void
    {
        $this->scopes()->where('scope', $scope)->delete();
    }

    public function syncScopes(array $scopes): void
    {
        // Remove old scopes
        $this->scopes()->whereNotIn('scope', $scopes)->delete();
        
        // Add new scopes
        foreach ($scopes as $scope) {
            $this->addScope($scope);
        }
    }

    public function hasScope(string $scope): bool
    {
        return $this->scopes()->where('scope', $scope)->exists();
    }

    // ==================== SECURITY ====================

    /**
     * Prevent serialization to avoid credential leaks
     */
    public function __sleep()
    {
        throw new \Voodflow\Voodflow\Exceptions\CredentialSerializationException(
            'Credentials cannot be serialized for security reasons'
        );
    }

    /**
     * Get sanitized version for export (no credentials)
     */
    public function toExportArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'provider' => $this->provider,
            'description' => $this->description,
            // NO credentials field!
        ];
    }
}
