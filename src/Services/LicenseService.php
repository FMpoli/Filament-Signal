<?php

namespace Voodflow\Voodflow\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * License Validation Service
 * 
 * Validates node licenses against Anystack or custom license servers
 */
class LicenseService
{
    /**
     * Validate a node license
     */
    public function validate(string $nodeName, string $licenseKey): array
    {
        // Check cache first (valid for 24 hours)
        $cacheKey = "voodflow_license_{$nodeName}_{$licenseKey}";
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Get node manifest
        $manifest = $this->getNodeManifest($nodeName);
        
        if (! $manifest || ! isset($manifest['license'])) {
            return [
                'valid' => true, // Free nodes are always valid
                'tier' => 'FREE',
            ];
        }

        // Validate against license server
        $result = $this->validateWithServer(
            $manifest['license']['validation_url'] ?? null,
            $manifest['license']['anystack_product_id'] ?? null,
            $licenseKey
        );

        // Cache result
        if ($result['valid']) {
            Cache::put($cacheKey, $result, now()->addHours(24));
        }

        return $result;
    }

    /**
     * Validate license with external server
     */
    protected function validateWithServer(?string $url, ?string $productId, string $licenseKey): array
    {
        if (! $url || ! $productId) {
            return [
                'valid' => false,
                'error' => 'License validation URL not configured',
            ];
        }

        try {
            $response = Http::timeout(10)->post($url, [
                'product_id' => $productId,
                'license_key' => $licenseKey,
                'domain' => request()->getHost(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'valid' => $data['valid'] ?? false,
                    'tier' => $data['tier'] ?? 'PREMIUM',
                    'expires_at' => $data['expires_at'] ?? null,
                    'customer_name' => $data['customer_name'] ?? null,
                ];
            }

            return [
                'valid' => false,
                'error' => 'License validation failed: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            \Log::error('License validation error', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'error' => 'License validation error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Store encrypted license key for a node
     */
    public function storeLicense(string $nodeName, string $licenseKey): void
    {
        $encrypted = Crypt::encryptString($licenseKey);
        
        Cache::forever("voodflow_node_license_{$nodeName}", $encrypted);
    }

    /**
     * Get stored license key for a node
     */
    public function getLicense(string $nodeName): ?string
    {
        $encrypted = Cache::get("voodflow_node_license_{$nodeName}");
        
        if (! $encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if a node is licensed
     */
    public function isLicensed(string $nodeName): bool
    {
        $licenseKey = $this->getLicense($nodeName);
        
        if (! $licenseKey) {
            // Check if node requires license
            $manifest = $this->getNodeManifest($nodeName);
            
            if (! $manifest || $manifest['tier'] === 'FREE' || $manifest['tier'] === 'CORE') {
                return true; // Free nodes don't need license
            }
            
            return false;
        }

        $result = $this->validate($nodeName, $licenseKey);
        
        return $result['valid'] ?? false;
    }

    /**
     * Get node manifest
     */
    protected function getNodeManifest(string $nodeName): ?array
    {
        // Convert kebab-case to PascalCase
        $nodeClass = str_replace('-', '', ucwords($nodeName, '-'));
        $manifestPath = __DIR__ . "/../Nodes/{$nodeClass}/manifest.json";

        if (! file_exists($manifestPath)) {
            return null;
        }

        return json_decode(file_get_contents($manifestPath), true);
    }

    /**
     * Clear license cache
     */
    public function clearCache(string $nodeName): void
    {
        $licenseKey = $this->getLicense($nodeName);
        
        if ($licenseKey) {
            Cache::forget("voodflow_license_{$nodeName}_{$licenseKey}");
        }
    }
}
