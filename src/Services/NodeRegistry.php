<?php

namespace Voodflow\Voodflow\Services;

use Illuminate\Support\Facades\File;
use Voodflow\Voodflow\Contracts\NodeInterface;

/**
 * Node Registry
 *
 * Discovers and registers all available workflow nodes.
 * Nodes are auto-discovered from src/Nodes/ directories.
 */
class NodeRegistry
{
    protected array $nodes = [];

    public function __construct()
    {
        $this->discoverNodes();
    }

    /**
     * Auto-discover nodes from src/Nodes directory
     */
    protected function discoverNodes(): void
    {
        $nodesPath = __DIR__ . '/../Nodes';

        if (! File::isDirectory($nodesPath)) {
            return;
        }

        // Get all subdirectories
        $directories = File::directories($nodesPath);

        foreach ($directories as $directory) {
            $this->processDirectory($directory);
        }

        // Scan storage nodes (Installed via UI)
        $storageNodesPath = storage_path('app/voodflow/nodes');
        if (File::isDirectory($storageNodesPath)) {
            $directories = File::directories($storageNodesPath);
            foreach ($directories as $directory) {
                // Check DB status if table exists
                $manifestPath = $directory . '/manifest.json';
                if (File::exists($manifestPath)) {
                    $content = File::get($manifestPath);
                    $manifest = json_decode($content, true);
                    $name = $manifest['name'] ?? null;

                    try {
                        if ($name && \Illuminate\Support\Facades\Schema::hasTable('voodflow_installed_packages')) {
                            $package = \Voodflow\Voodflow\Models\InstalledPackage::where('name', $name)->first();
                            // If found regarding DB records, reuse logic. If not found, assume active (manually placed)?
                            // Or if manually placed in storage, maybe treat as active.
                            // But if logic is 'installed via UI', it should be in DB.
                            if ($package && ! $package->is_active) {
                                continue;
                            }
                        }
                    } catch (\Exception $e) {
                        // Fallback if DB issue
                    }
                }
                $this->processDirectory($directory);
            }
        }
    }

    /**
     * Process a node directory
     */
    protected function processDirectory(string $path): void
    {
        // 1. Try manifest.json first (Preferred)
        $manifestPath = $path . '/manifest.json';
        if (File::exists($manifestPath)) {
            $content = File::get($manifestPath);
            $manifest = json_decode($content, true);

            if ($manifest && isset($manifest['php']['namespace'], $manifest['php']['class'])) {
                $className = $manifest['php']['namespace'] . '\\' . $manifest['php']['class'];

                // Try to load file manually to support drop-in without composer dump-autoload
                $classFile = $path . '/' . $manifest['php']['class'] . '.php';
                if (File::exists($classFile)) {
                    require_once $classFile;
                }

                if (class_exists($className) && in_array(NodeInterface::class, class_implements($className))) {
                    $this->register($className);

                    return;
                }
            }
        }

        // 2. Fallback: Scan PHP files in directory
        $files = File::allFiles($path);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // Simple heuristic mapping: src/Nodes/Dir/File.php -> NodeNamespace\Dir\File
            // This assumes standard PSR-4 structure inside Nodes dir if no manifest
            $relativePath = str_replace(realpath(__DIR__ . '/../Nodes') . '/', '', $file->getRealPath());

            // Fix path separators for Windows compatibility
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            $className = 'Voodflow\\Voodflow\\Nodes\\' . str_replace(
                ['/', '.php'],
                ['\\', ''],
                $relativePath
            );

            // Check if class exists and implements NodeInterface
            if (class_exists($className) && in_array(NodeInterface::class, class_implements($className))) {
                $this->register($className);
            }
        }
    }

    /**
     * Register a node class
     */
    public function register(string $nodeClass): void
    {
        if (! in_array(NodeInterface::class, class_implements($nodeClass))) {
            throw new \InvalidArgumentException("$nodeClass must implement NodeInterface");
        }

        $type = $nodeClass::type();
        $this->nodes[$type] = $nodeClass;
    }

    /**
     * Get all registered nodes
     *
     * @return array Map of type => class name
     */
    public function all(): array
    {
        return $this->nodes;
    }

    /**
     * Get a node class by type
     */
    public function get(string $type): ?string
    {
        return $this->nodes[$type] ?? null;
    }

    /**
     * Get metadata formatted for React frontend - grouped by category
     * Returns object (stdClass) when empty to ensure proper JSON encoding
     */
    public function getMetadataForReact(): array | object
    {
        $nodes = $this->all();
        $grouped = [];

        foreach ($nodes as $type => $class) {
            $metadata = $class::metadata();
            $category = $metadata['category'] ?? 'Other';

            // Normalize category names
            $categoryMap = [
                'trigger' => 'Triggers',
                'action' => 'Actions',
                'transform' => 'Transform',
                'flow' => 'Flow Control',
            ];

            $categoryName = $categoryMap[$category] ?? ucfirst($category);

            if (! isset($grouped[$categoryName])) {
                $grouped[$categoryName] = [];
            }

            $grouped[$categoryName][] = [
                'type' => $type,
                'name' => $class::name(),
                'description' => $metadata['description'] ?? '',
                'author' => $metadata['author'] ?? 'Unknown',
                'tier' => $metadata['tier'] ?? 'FREE',
                'icon' => $metadata['icon'] ?? 'heroicon-o-cube',
                'color' => $metadata['color'] ?? 'gray',
                'category' => $categoryName,
            ];
        }

        // Sort categories in preferred order
        $order = ['Triggers', 'Actions', 'Transform', 'Flow Control', 'Other'];
        $sorted = [];

        foreach ($order as $cat) {
            if (isset($grouped[$cat])) {
                $sorted[$cat] = $grouped[$cat];
            }
        }

        // Add any remaining categories
        foreach ($grouped as $cat => $nodes) {
            if (! isset($sorted[$cat])) {
                $sorted[$cat] = $nodes;
            }
        }

        // Ensure it's an object in JSON, not an array
        // This prevents [0,1,2,3,4] when json_encode is called
        if (empty($sorted)) {
            return (object) []; // Empty object
        }

        return $sorted;
    }
}
