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
                // Key by Full Class Name to support multiple nodes of same type
                $nodes[$fullClass] = $fullClass;
            }
        }
        
        return $nodes;
    }

    /**
     * Get a node class by type (returns first match if multiple exist - primarily for back compat)
     * Or by Full Class Name
     */
    public static function get(string $identifier): ?string
    {
        $all = static::all();
        
        // Exact match (Class Name)
        if (isset($all[$identifier])) {
            return $all[$identifier];
        }

        // Search by type (Backwards compatibility / Generic types)
        foreach ($all as $class) {
            if ($class::type() === $identifier) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Get all node metadata keyed by Class Name
     */
    public static function getMetadataMap(): array
    {
        $map = [];
        foreach (static::all() as $className => $class) {
            // Use short class name as easier identifier if needed, or stick to FQCN
            // We use the 'type' in frontend to map to React components.
            // But we need to distinguish them.
            // Frontend 'availableNodes' list should primarily come from this map.
            
            $map[$className] = [
                'name' => $class::name(),
                'type' => $class::type(), // visual/logic type (filter, trigger, etc)
                'className' => $className, // unique identifier
                'metadata' => $class::metadata(),
                'defaultConfig' => $class::defaultConfig(),
            ];
        }
        return $map;
    }
}