<?php

namespace Base33\FilamentSignal\Nodes;

/**
 * Registry of available Signal workflow nodes.
 * 
 * Add your custom nodes here after generating them with:
 * php artisan signal:make-node
 */
class NodeRegistry
{
    /**
     * Get all registered node classes
     */
    public static function all(): array
    {
        $nodes = [];
        
        // Auto-discover nodes in this directory
        foreach (glob(__DIR__ . '/*Node.php') as $file) {
            $filename = basename($file, '.php');
            
            // Skip self reference (e.g. if explicitly excluded) or generic files
            if ($filename === 'NodeRegistry') {
                continue;
            }
            
            $fullClass = __NAMESPACE__ . '\\' . $filename;
            
            if (class_exists($fullClass) && in_array(\Base33\FilamentSignal\Contracts\NodeInterface::class, class_implements($fullClass))) {
                // Key by node type (e.g. 'trigger', 'filter')
                $nodes[$fullClass::type()] = $fullClass;
            }
        }
        
        return $nodes;
    }

    /**
     * Get a node class by type
     */
    public static function get(string $type): ?string
    {
        return static::all()[$type] ?? null;
    }

    /**
     * Get all node metadata keyed by node type (class name)
     */
    public static function getMetadataMap(): array
    {
        $map = [];
        foreach (static::all() as $name => $class) {
            $map[$name] = [
                'name' => $class::name(),
                'type' => $class::type(),
                'class' => $class,
                'metadata' => $class::metadata(),
                'defaultConfig' => $class::defaultConfig(),
            ];
        }
        return $map;
    }
}