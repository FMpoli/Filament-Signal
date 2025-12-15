<?php

namespace Voodflow\Voodflow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeNodeCommand extends Command
{
    protected $signature = 'voodflow:make-node 
                            {name : The name of the node (e.g., SlackNode, DatabaseNode)}
                            {--type=action : Node type: trigger, action, transform, or flow}
                            {--force : Overwrite existing files}';

    protected $description = 'Create a new self-contained Voodflow node';

    public function handle(): int
    {
        $name = $this->argument('name');
        $type = $this->option('type');
        $force = $this->option('force');

        // Validate node name
        if (!Str::endsWith($name, 'Node')) {
            $name .= 'Node';
        }

        $nodeClass = Str::studly($name);
        $nodeDir = base_path("packages/Voodflow/Voodflow/src/Nodes/{$nodeClass}");

        // Check if exists
        if (is_dir($nodeDir) && !$force) {
            $this->error("Node {$nodeClass} already exists! Use --force to overwrite.");
            return self::FAILURE;
        }

        // Create directory structure
        $this->createDirectoryStructure($nodeDir);

        // Generate files
        $this->generateNodeClass($nodeDir, $nodeClass, $type);
        $this->generateReactComponent($nodeDir, $nodeClass, $type);

        $this->info("✅ Node {$nodeClass} created successfully!");
        $this->newLine();
        $this->info("📁 Location: {$nodeDir}");
        $this->info("📝 Files created:");
        $this->line("   - {$nodeClass}.php");
        $this->line("   - components/{$nodeClass}.jsx");
        $this->newLine();
        $this->info("🚀 Next steps:");
        $this->line("   1. Implement execute() method in {$nodeClass}.php");
        $this->line("   2. Customize React component in components/{$nodeClass}.jsx");
        $this->line("   3. Run: npm run build");

        return self::SUCCESS;
    }

    protected function createDirectoryStructure(string $nodeDir): void
    {
        if (!is_dir($nodeDir)) {
            mkdir($nodeDir, 0755, true);
        }

        if (!is_dir($nodeDir . '/components')) {
            mkdir($nodeDir . '/components', 0755, true);
        }
    }

    protected function generateNodeClass(string $nodeDir, string $nodeClass, string $type): void
    {
        $nodeType = Str::snake($nodeClass);
        $nodeName = Str::title(str_replace('_', ' ', Str::snake($nodeClass, ' ')));

        $categoryMap = [
            'trigger' => 'Triggers',
            'action' => 'Actions',
            'transform' => 'Transform',
            'flow' => 'Flow Control',
        ];

        $category = $categoryMap[$type] ?? 'Actions';
        $colorMap = [
            'trigger' => 'success',
            'action' => 'primary',
            'transform' => 'warning',
            'flow' => 'info',
        ];
        $color = $colorMap[$type] ?? 'primary';

        $iconMap = [
            'trigger' => 'heroicon-o-bolt',
            'action' => 'heroicon-o-paper-airplane',
            'transform' => 'heroicon-o-funnel',
            'flow' => 'heroicon-o-arrows-pointing-out',
        ];
        $icon = $iconMap[$type] ?? 'heroicon-o-cube';

        $template = <<<PHP
<?php

namespace Voodflow\\Voodflow\\Nodes\\{$nodeClass};

use Voodflow\\Voodflow\\Contracts\\NodeInterface;
use Voodflow\\Voodflow\\Execution\\ExecutionContext;
use Voodflow\\Voodflow\\Execution\\ExecutionResult;

/**
 * {$nodeName}
 * 
 * Self-contained {$type} node.
 * 
 * @author Voodflow
 * @version 1.0.0
 */
class {$nodeClass} implements NodeInterface
{
    public static function type(): string
    {
        return '{$nodeType}';
    }
    
    public static function name(): string
    {
        return '{$nodeName}';
    }
    
    public static function defaultConfig(): array
    {
        return [
            'label' => '{$nodeName}',
            'description' => '',
            // Add your configuration fields here
            // 'field_name' => 'default_value',
        ];
    }
    
    public static function metadata(): array
    {
        return [
            'author' => 'Voodflow',
            'version' => '1.0.0',
            'color' => '{$color}',
            'icon' => '{$icon}',
            'group' => '{$category}',
            'category' => '{$type}',
            'description' => '{$nodeName} description',
            'positioning' => [
                'input' => true,
                'output' => true,
            ],
        ];
    }
    
    /**
     * Execute the node logic
     */
    public function execute(ExecutionContext \$context): ExecutionResult
    {
        // TODO: Implement your node logic here
        
        // Example: Get config values
        // \$someConfig = \$context->getConfig('field_name', 'default');
        
        // Example: Get input data
        // \$inputData = \$context->input;
        
        // Example: Return success with output
        return ExecutionResult::success([
            'message' => 'Node executed successfully',
            'data' => \$context->input,
        ]);
        
        // Example: Return failure
        // return ExecutionResult::failure('Something went wrong');
    }
    
    /**
     * Validate node configuration
     */
    public function validate(array \$config): array
    {
        \$errors = [];
        
        // TODO: Add your validation logic
        // Example:
        // if (empty(\$config['required_field'])) {
        //     \$errors['required_field'] = 'This field is required';
        // }
        
        return \$errors;
    }
}

PHP;

        file_put_contents($nodeDir . "/{$nodeClass}.php", $template);
    }

    protected function generateReactComponent(string $nodeDir, string $nodeClass, string $type): void
    {
        $template = <<<JSX
import React from 'react';
import { Handle, Position } from 'reactflow';

/**
 * {$nodeClass} React Component
 * 
 * Visual representation of {$nodeClass} in the workflow editor.
 */
const {$nodeClass} = ({ data, selected }) => {
  return (
    <div
      className={\`node-wrapper \${selected ? 'selected' : ''}\`}
      style={{
        border: '2px solid #4F46E5',
        borderRadius: '8px',
        background: 'white',
        padding: '12px',
        minWidth: '200px',
      }}
    >
      {/* Input Handle */}
      <Handle
        type="target"
        position={Position.Left}
        id="input"
        style={{ background: '#4F46E5' }}
      />

      {/* Node Content */}
      <div style={{ textAlign: 'center' }}>
        <div style={{ fontWeight: 'bold', marginBottom: '4px' }}>
          {data.label || '{$nodeClass}'}
        </div>
        {data.description && (
          <div style={{ fontSize: '12px', color: '#666' }}>
            {data.description}
          </div>
        )}
      </div>

      {/* Output Handle */}
      <Handle
        type="source"
        position={Position.Right}
        id="output"
        style={{ background: '#4F46E5' }}
      />
    </div>
  );
};

export default {$nodeClass};

JSX;

        file_put_contents($nodeDir . "/components/{$nodeClass}.jsx", $template);
    }
}
