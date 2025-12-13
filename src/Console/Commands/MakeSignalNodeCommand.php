<?php

namespace Base33\FilamentSignal\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeSignalNodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'signal:make-node 
                            {name? : The name of the node (e.g., SendEmail, WebhookCall)}
                            {--type= : The type of node (trigger, filter, action)}
                            {--color= : The header color (orange, purple, blue, green, red, gray)}
                            {--icon= : Icon name (bolt, filter, bell, mail, code, webhook)}
                            {--description= : A short description of what the node does}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new Signal workflow node (trigger, filter, or action)';

    /**
     * Node types and their default configurations
     */
    protected array $nodeTypes = [
        'trigger' => [
            'color' => 'orange',
            'icon' => 'bolt',
            'description' => 'Triggers the workflow when an event occurs',
            'hasInputHandle' => false,
            'hasOutputHandle' => true,
        ],
        'filter' => [
            'color' => 'purple',
            'icon' => 'filter',
            'description' => 'Filters data based on conditions',
            'hasInputHandle' => true,
            'hasOutputHandle' => true,
        ],
        'action' => [
            'color' => 'blue',
            'icon' => 'bell',
            'description' => 'Performs an action in the workflow',
            'hasInputHandle' => true,
            'hasOutputHandle' => false,
        ],
    ];

    /**
     * Available colors and their Tailwind classes
     */
    protected array $colors = [
        'orange' => ['bg' => 'bg-orange-500', 'border' => 'border-orange-500', 'handle' => '!bg-orange-500'],
        'purple' => ['bg' => 'bg-purple-500', 'border' => 'border-purple-500', 'handle' => '!bg-purple-500'],
        'blue' => ['bg' => 'bg-blue-600', 'border' => 'border-blue-500', 'handle' => '!bg-blue-600'],
        'green' => ['bg' => 'bg-green-500', 'border' => 'border-green-500', 'handle' => '!bg-green-500'],
        'red' => ['bg' => 'bg-red-500', 'border' => 'border-red-500', 'handle' => '!bg-red-500'],
        'gray' => ['bg' => 'bg-gray-600', 'border' => 'border-gray-500', 'handle' => '!bg-gray-600'],
    ];

    /**
     * Available icons (Heroicons paths)
     */
    protected array $icons = [
        'bolt' => 'M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z',
        'filter' => 'M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z',
        'bell' => 'M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z',
        'mail' => 'M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884zM18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z',
        'code' => 'M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z',
        'webhook' => 'M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('🔧 Signal Node Generator');
        $this->info('========================');
        $this->info('');

        // Get node name
        $name = $this->argument('name') ?? $this->ask('What is the name of your node? (e.g., SendEmail, WebhookCall)');

        if (empty($name)) {
            $this->error('Node name is required.');

            return 1;
        }

        // Validate and format name
        $name = Str::studly($name);
        $nameKebab = Str::kebab($name);
        $nameCamel = Str::camel($name);

        // Get node type
        $type = $this->option('type') ?? $this->choice(
            'What type of node is this?',
            array_keys($this->nodeTypes),
            0
        );

        if (! isset($this->nodeTypes[$type])) {
            $this->error("Invalid node type: {$type}");

            return 1;
        }

        // Get color
        $defaultColor = $this->nodeTypes[$type]['color'];
        $color = $this->option('color') ?? $this->choice(
            'Choose header color',
            array_keys($this->colors),
            array_search($defaultColor, array_keys($this->colors))
        );

        // Get icon
        $defaultIcon = $this->nodeTypes[$type]['icon'];
        $icon = $this->option('icon') ?? $this->choice(
            'Choose an icon',
            array_keys($this->icons),
            array_search($defaultIcon, array_keys($this->icons))
        );

        // Get description
        $description = $this->option('description') ?? $this->ask(
            'Short description (optional)',
            $this->nodeTypes[$type]['description']
        );

        // Get vendor namespace
        $vendor = $this->option('vendor') ?? $this->ask('Vendor namespace (e.g. app, base33)', 'base33');

        // Summary
        $this->info('');
        $this->info('📋 Node Configuration:');
        $this->table(
            ['Property', 'Value'],
            [
                ['Name', $name],
                ['Vendor', $vendor],
                ['Category', $type],
                ['Type ID', strtolower($vendor) . '_' . \Illuminate\Support\Str::snake($name)],
                ['Description', $description],
                ['Color', $color],
                ['Icon', $icon],
            ]
        );

        if (! $this->confirm('Do you wish to continue?', true)) {
            return 0;
        }

        // Generate files
        $nodeTypeId = strtolower($vendor) . '_' . \Illuminate\Support\Str::snake($name);
        $this->generateReactComponent($name, $type, $color, $icon, $description);
        $this->generatePhpHandler($name, $type, $nodeTypeId, $description, $color, $icon);
        $this->updateNodeRegistry($name, $type);

        $this->info('');
        $this->info('✅ Node generated successfully!');
        $this->info('');
        $this->info('Next steps:');
        $this->info("  1. Edit resources/js/components/{$name}Node.jsx to add your UI logic");
        $this->info("  2. Edit src/Nodes/{$name}Node.php to add your backend logic");
        $this->info('  3. Run: npm run build');
        $this->info('');

        return 0;
    }

    /**
     * Generate React component
     */
    protected function generateReactComponent(string $name, string $type, string $color, string $icon, string $description): void
    {
        $nodeConfig = $this->nodeTypes[$type];
        $colorConfig = $this->colors[$color];
        $iconPath = $this->icons[$icon];

        $stub = $this->getReactStub();

        $content = str_replace([
            '{{NAME}}',
            '{{NAME_LOWER}}',
            '{{TYPE}}',
            '{{DESCRIPTION}}',
            '{{COLOR_BG}}',
            '{{COLOR_BORDER}}',
            '{{COLOR_HANDLE}}',
            '{{ICON_PATH}}',
            '{{HAS_INPUT_HANDLE}}',
            '{{HAS_OUTPUT_HANDLE}}',
        ], [
            $name,
            Str::camel($name),
            $type,
            $description,
            $colorConfig['bg'],
            $colorConfig['border'],
            $colorConfig['handle'],
            $iconPath,
            $nodeConfig['hasInputHandle'] ? 'true' : 'false',
            $nodeConfig['hasOutputHandle'] ? 'true' : 'false',
        ], $stub);

        $path = $this->getPackagePath() . "/resources/js/components/{$name}Node.jsx";

        if (file_exists($path)) {
            if (! $this->confirm("{$name}Node.jsx already exists. Overwrite?", false)) {
                $this->warn('Skipped React component.');

                return;
            }
        }

        file_put_contents($path, $content);
        $this->info("Created: resources/js/components/{$name}Node.jsx");
    }

    /**
     * Generate PHP handler class
     */
    protected function generatePhpHandler(string $name, string $type, string $nodeTypeId, string $description, string $color, string $icon): void
    {
        $stub = $this->getPhpStub();
        $nodeConfig = $this->nodeTypes[$type];

        $content = str_replace([
            '{{NAME}}',
            '{{TYPE}}',
            '{{DESCRIPTION}}',
            '{{COLOR}}',
            '{{ICON}}',
            '{{GROUP}}',
            '{{HAS_INPUT}}',
            '{{HAS_OUTPUT}}',
        ], [
            $name,
            $nodeTypeId,
            $description,
            $color,
            'heroicon-o-' . $icon, // Prefix with heroicon-o- as convention
            ucfirst($type) . 's', // e.g. Filters, Actions
            $nodeConfig['hasInputHandle'] ? 'true' : 'false',
            $nodeConfig['hasOutputHandle'] ? 'true' : 'false',
        ], $stub);

        $dir = $this->getPackagePath() . '/src/Nodes';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "{$dir}/{$name}Node.php";

        if (file_exists($path)) {
            if (! $this->confirm("{$name}Node.php already exists. Overwrite?", false)) {
                $this->warn('Skipped PHP handler.');

                return;
            }
        }

        file_put_contents($path, $content);
        $this->info("Created: src/Nodes/{$name}Node.php");
    }

    /**
     * Update node registry file
     */
    protected function updateNodeRegistry(string $name, string $type): void
    {
        $registryPath = $this->getPackagePath() . '/src/Nodes/NodeRegistry.php';

        if (! file_exists($registryPath)) {
            // Create registry if it doesn't exist
            $this->createNodeRegistry($name, $type);

            return;
        }

        $this->info('Note: Remember to register your node in FlowEditor.jsx nodeTypes');
    }

    /**
     * Create initial node registry
     */
    protected function createNodeRegistry(string $name, string $type): void
    {
        $stub = <<<'PHP'
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
        return [
            // Add your nodes here:
            // '{{NAME}}' => \Base33\FilamentSignal\Nodes\{{NAME}}Node::class,
        ];
    }

    /**
     * Get a node class by type
     */
    public static function get(string $type): ?string
    {
        return static::all()[$type] ?? null;
    }
}
PHP;

        $content = str_replace('{{NAME}}', $name, $stub);

        $dir = $this->getPackagePath() . '/src/Nodes';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents("{$dir}/NodeRegistry.php", $content);
        $this->info('Created: src/Nodes/NodeRegistry.php');
    }

    /**
     * Get React component stub
     */
    protected function getReactStub(): string
    {
        return <<<'JSX'
import React, { useState } from 'react';
import { Handle, Position } from 'reactflow';
import ConfirmModal from './ConfirmModal';

/**
 * {{NAME}} Node Component
 * 
 * Type: {{TYPE}}
 * Description: {{DESCRIPTION}}
 * 
 * Generated by: php artisan signal:make-node
 */
const {{NAME}}Node = ({ id, data }) => {
    const [isExpanded, setIsExpanded] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [label, setLabel] = useState(data.label || '{{NAME}}');
    const [description, setDescription] = useState(data.description || '');

    // Save configuration to backend
    const save = (newLabel = label, newDescription = description) => {
        if (data.livewireId && window.Livewire) {
            const component = window.Livewire.find(data.livewireId);
            if (component) {
                component.call('updateNodeConfig', {
                    nodeId: id,
                    label: newLabel,
                    description: newDescription,
                    // Add your custom config fields here
                });
            }
        }
    };

    const handleLabelChange = (value) => {
        setLabel(value);
        save(value, description);
    };

    const handleDescriptionChange = (value) => {
        setDescription(value);
        save(label, value);
    };

    const handleDelete = () => {
        setShowDeleteModal(true);
    };

    const confirmDelete = () => {
        setShowDeleteModal(false);
        if (data.livewireId && window.Livewire) {
            window.Livewire.find(data.livewireId)?.call('deleteNode', id);
        }
    };

    return (
        <>
            <ConfirmModal
                isOpen={showDeleteModal}
                title="Delete {{NAME}}"
                message={`Are you sure you want to delete "${label}"?`}
                onConfirm={confirmDelete}
                onCancel={() => setShowDeleteModal(false)}
            />
            
            <div className={`
                bg-white dark:bg-slate-900
                border-2 rounded-xl
                {{COLOR_BORDER}}
                shadow-lg min-w-[300px] max-w-[400px]
                transition-all duration-200
            `}>
                {/* Input Handle */}
                {{{HAS_INPUT_HANDLE}} && (
                    <Handle
                        type="target"
                        position={Position.Left}
                        className="{{COLOR_HANDLE}} !w-3 !h-3 !border-2 !border-white"
                    />
                )}

                {/* Header */}
                <div className="{{COLOR_BG}} px-4 py-2.5 flex items-center justify-between rounded-t-lg">
                    <div className="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4 text-white">
                            <path fillRule="evenodd" d="{{ICON_PATH}}" clipRule="evenodd" />
                        </svg>
                        <span className="text-xs font-bold text-white uppercase tracking-wider">
                            {label}
                        </span>
                    </div>

                    <div className="flex items-center gap-2">
                        {/* Expand/Collapse */}
                        <button
                            onClick={() => setIsExpanded(!isExpanded)}
                            className="nodrag text-white/80 hover:text-white transition-colors"
                            title={isExpanded ? "Collapse" : "Expand"}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                className={`w-4 h-4 transition-transform ${isExpanded ? 'rotate-180' : ''}`}>
                                <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                            </svg>
                        </button>

                        {/* Delete */}
                        <button
                            onClick={handleDelete}
                            className="nodrag text-white/80 hover:text-white transition-colors"
                            title="Delete"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
                                <path fillRule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clipRule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Body */}
                <div className="p-4">
                    {!isExpanded ? (
                        /* Collapsed View */
                        <div>
                            {description && (
                                <div className="text-slate-500 dark:text-slate-400 text-xs italic mb-2">
                                    {description}
                                </div>
                            )}
                            
                            {!description && (
                                <div className="text-slate-400 dark:text-slate-500 text-sm italic">
                                    Click to configure...
                                </div>
                            )}
                        </div>
                    ) : (
                        /* Expanded View - Edit Form */
                        <div className="nodrag space-y-3">
                            {/* Name */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Name
                                </label>
                                <input
                                    type="text"
                                    value={label}
                                    onChange={(e) => handleLabelChange(e.target.value)}
                                    placeholder="Node name..."
                                    className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                        bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100
                                        focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                />
                            </div>

                            {/* Description */}
                            <div>
                                <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                                    Description
                                </label>
                                <input
                                    type="text"
                                    value={description}
                                    onChange={(e) => handleDescriptionChange(e.target.value)}
                                    placeholder="Optional description..."
                                    className="w-full px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 rounded-md 
                                        bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100
                                        focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                />
                            </div>

                            {/* TODO: Add your custom configuration fields here */}
                            {/* Example:
                            <div>
                                <label className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                                    Custom Field
                                </label>
                                <input type="text" ... />
                            </div>
                            */}
                        </div>
                    )}
                </div>

                {/* Output Handle */}
                {{{HAS_OUTPUT_HANDLE}} && (
                    <Handle
                        type="source"
                        position={Position.Right}
                        className="{{COLOR_HANDLE}} !w-3 !h-3 !border-2 !border-white"
                    />
                )}
            </div>
        </>
    );
};

export default {{NAME}}Node;
JSX;
    }

    /**
     * Get PHP handler stub
     */
    protected function getPhpStub(): string
    {
        return <<<'PHP'
<?php

namespace Base33\FilamentSignal\Nodes;

use Base33\FilamentSignal\Contracts\NodeInterface;

/**
 * {{NAME}} Node Handler
 * 
 * Type: {{TYPE}}
 * Description: {{DESCRIPTION}}
 * 
 * Generated by: php artisan signal:make-node
 */
class {{NAME}}Node implements NodeInterface
{
    /**
     * The node type identifier
     */
    public static function type(): string
    {
        return '{{TYPE}}';
    }

    /**
     * Get the node name
     */
    public static function name(): string
    {
        return '{{NAME}}';
    }

    /**
     * Get the default configuration for this node
     */
    public static function defaultConfig(): array
    {
        return [
            'label' => '{{NAME}}',
            'description' => '',
            // Add your default config here
        ];
    }

    /**
     * Get node metadata
     */
    public static function metadata(): array
    {
        return [
            'author' => 'Base33',
            'version' => '1.0.0',
            'color' => '{{COLOR}}',
            'icon' => '{{ICON}}',
            'group' => '{{GROUP}}',
            'positioning' => [
                'input' => {{HAS_INPUT}},
                'output' => {{HAS_OUTPUT}},
            ],
        ];
    }

    /**
     * Execute the node logic
     * 
     * @param array $input The input data from previous nodes
     * @param array $config The node configuration
     * @return array The output data to pass to next nodes
     */
    public function execute(array $input, array $config): array
    {
        // TODO: Implement your node logic here
        
        // Example:
        // $result = $this->doSomething($input, $config);
        // return ['success' => true, 'data' => $result];
        
        return $input;
    }

    /**
     * Validate the node configuration
     * 
     * @param array $config The node configuration
     * @return array Array of validation errors, empty if valid
     */
    public function validate(array $config): array
    {
        $errors = [];
        
        // TODO: Add your validation logic here
        // Example:
        // if (empty($config['required_field'])) {
        //     $errors[] = 'Required field is missing';
        // }
        
        return $errors;
    }
}
PHP;
    }

    /**
     * Get the package base path (root of the package, not src/)
     */
    protected function getPackagePath(): string
    {
        // __DIR__ = src/Console/Commands
        // dirname(..., 3) = package root
        return dirname(__DIR__, 3);
    }
}
