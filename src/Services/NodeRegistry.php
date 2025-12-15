<?php

namespace Voodflow\Voodflow\Services;

use Illuminate\Support\Facades\File;
use Voodflow\Voodflow\Contracts\NodeInterface;

/**
 * Node Registry
 * 
 * Discovers and registers all available workflow nodes.
 * Nodes are auto-discovered from src/Nodes/*/ directories.
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
        
        if (!File::isDirectory($nodesPath)) {
            return;
        }
        
        // Get all PHP files in Nodes directory
        $files = File::allFiles($nodesPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            
            // Build class name from file path
            $relativePath = str_replace($nodesPath . '/', '', $file->getPathname());
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
        if (!in_array(NodeInterface::class, class_implements($nodeClass))) {
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
     * Get all nodes metadata for React
     */
    public function getMetadataForReact(): array
    {
        $metadata = [];
        
        foreach ($this->nodes as $type => $nodeClass) {
            $metadata[$type] = [
                'className' => $nodeClass,
                'type' => $nodeClass::type(),
                'name' => $nodeClass::name(),
                'metadata' => $nodeClass::metadata(),
            ];
        }
        
        return $metadata;
    }
}
