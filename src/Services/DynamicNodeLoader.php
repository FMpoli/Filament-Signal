<?php

namespace Voodflow\Voodflow\Services;

use Illuminate\Support\Facades\File;

/**
 * Dynamic Node Loader Service
 * 
 * Scans for installed nodes and makes their bundles available
 */
class DynamicNodeLoader
{
    /**
     * Get all installed node bundles
     */
    public function getInstalledNodeBundles(): array
    {
        $bundles = [];
        
        $paths = [
            __DIR__ . '/../Nodes',
            storage_path('app/voodflow/nodes'),
        ];

        foreach ($paths as $nodesDir) {
            if (!File::isDirectory($nodesDir)) {
                continue;
            }

            $nodeDirs = File::directories($nodesDir);

            foreach ($nodeDirs as $nodeDir) {
                $manifest = $this->loadManifest($nodeDir);
                
                if (!$manifest) {
                    continue;
                }

                // Check active status from DB
                $name = $manifest['name'] ?? null;
                if ($name && \Illuminate\Support\Facades\Schema::hasTable('voodflow_installed_packages')) {
                    $package = \Voodflow\Voodflow\Models\InstalledPackage::where('name', $name)->first();
                    if ($package && !$package->is_active) {
                        continue;
                    }
                }

                $bundlePath = $nodeDir . '/' . ($manifest['javascript']['bundle'] ?? '');
                
                if (!File::exists($bundlePath)) {
                    continue;
                }

                // Copy bundle to public if not already there
                $publicPath = $this->publishBundle($bundlePath, $manifest['name']);
                
                if ($publicPath) {
                    $bundles[] = [
                        'type' => $this->getNodeType($manifest),
                        'name' => $manifest['name'],
                        'display_name' => $manifest['display_name'] ?? $manifest['name'],
                        'url' => $publicPath,
                        'globalName' => $manifest['javascript']['component'] ?? $manifest['php']['class'],
                        'manifest' => $manifest,
                    ];
                }
            }
        }

        return $bundles;
    }

    /**
     * Load manifest from node directory
     */
    protected function loadManifest(string $nodeDir): ?array
    {
        $manifestPath = $nodeDir . '/manifest.json';
        
        if (!File::exists($manifestPath)) {
            return null;
        }

        $content = File::get($manifestPath);
        $manifest = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $manifest;
    }

    /**
     * Publish bundle to public directory
     */
    protected function publishBundle(string $bundlePath, string $nodeName): ?string
    {
        $publicDir = public_path('js/voodflow/nodes');
        
        if (!File::isDirectory($publicDir)) {
            File::makeDirectory($publicDir, 0755, true);
        }

        $fileName = basename($bundlePath);
        $publicPath = $publicDir . '/' . $fileName;

        // Copy bundle to public
        if (!File::exists($publicPath) || File::lastModified($bundlePath) > File::lastModified($publicPath)) {
            File::copy($bundlePath, $publicPath);
        }

        return '/js/voodflow/nodes/' . $fileName;
    }

    /**
     * Get node type from manifest
     */
    protected function getNodeType(array $manifest): string
    {
        // If explicit type in manifest
        if (isset($manifest['type'])) {
            return $manifest['type'];
        }

        // Convert name from kebab-case to snake_case
        return str_replace('-', '_', $manifest['name']);
    }

    /**
     * Generate JavaScript config for bundles
     */
    public function generateJavaScriptConfig(): string
    {
        $bundles = $this->getInstalledNodeBundles();
        
        return 'window.VoodflowNodeBundles = ' . json_encode($bundles, JSON_PRETTY_PRINT) . ';';
    }
}
