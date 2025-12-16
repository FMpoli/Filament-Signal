<?php

namespace Voodflow\Voodflow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeNodeCommand extends Command
{
    protected $signature = 'voodflow:make-node 
                            {name? : The name of the node (e.g., SlackNode, DatabaseNode)}
                            {--interactive : Run in interactive mode}
                            {--force : Overwrite existing files}';

    protected $description = 'Create a new self-contained Voodflow node';

    public function handle(): int
    {
        $this->info('🎨 Voodflow Node Generator');
        $this->newLine();

        // Collect node information interactively
        $nodeInfo = $this->collectNodeInfo();

        $nodeClass = Str::studly($nodeInfo['name']);
        $nodeDir = base_path("packages/Voodflow/Voodflow/src/Nodes/{$nodeClass}");

        // Check if exists
        if (is_dir($nodeDir) && ! $nodeInfo['force']) {
            $this->error("Node {$nodeClass} already exists! Use --force to overwrite.");

            return self::FAILURE;
        }

        // Create directory structure
        $this->createDirectoryStructure($nodeDir);

        // Generate files
        $this->generateNodeClass($nodeDir, $nodeClass, $nodeInfo);
        $this->generateReactComponent($nodeDir, $nodeClass, $nodeInfo);
        $this->generateManifest($nodeDir, $nodeClass, $nodeInfo);

        $this->newLine();
        $this->info("✅ Node {$nodeClass} created successfully!");
        $this->newLine();
        $this->info("📁 Location: {$nodeDir}");
        $this->info('📝 Files created:');
        $this->line('   - manifest.json');
        $this->line("   - {$nodeClass}.php");
        $this->line("   - components/{$nodeClass}.jsx");
        $this->newLine();
        $this->info('🚀 Next steps:');
        $this->line("   1. Implement execute() method in {$nodeClass}.php");
        $this->line("   2. Customize React component in components/{$nodeClass}.jsx");
        $this->line('   3. Development: npm run dev (hot reload)');
        $this->line('   4. Production: npm run build');

        return self::SUCCESS;
    }

    protected function collectNodeInfo(): array
    {
        $name = $this->argument('name');

        if (! $name) {
            $name = $this->ask('Node name (e.g., SlackNode, DatabaseNode)');
        }

        // Ensure name ends with 'Node'
        if (! Str::endsWith($name, 'Node')) {
            $name .= 'Node';
        }

        $type = $this->choice(
            'Node type',
            ['trigger', 'action', 'transform', 'flow'],
            1 // default to 'action'
        );

        $tier = $this->choice(
            'Node tier',
            ['FREE', 'CORE', 'PRO'],
            1 // default to 'CORE'
        );

        $author = $this->ask('Author name', 'Voodflow');

        $description = $this->ask('Short description', 'Custom node for workflow automation');

        $authorUrl = null;
        $repository = null;
        $license = 'MIT';

        if ($this->confirm('Add optional metadata (author URL, repository)?', false)) {
            $authorUrl = $this->ask('Author URL (optional)');
            $repository = $this->ask('Repository URL (optional)');
            $license = $this->ask('License', 'MIT');
        }

        // Handle configuration
        $hasMultipleOutputs = false;
        $outputHandles = [];

        if ($type === 'flow') {
            $hasMultipleOutputs = $this->confirm('Does this node have multiple outputs (like IF/Switch)?', true);

            if ($hasMultipleOutputs) {
                $numOutputs = (int) $this->ask('How many outputs?', 2);

                for ($i = 0; $i < $numOutputs; $i++) {
                    $handleId = $this->ask("Output #{$i} ID", $i === 0 ? 'true' : 'false');
                    $handleLabel = $this->ask("Output #{$i} Label", ucfirst($handleId));
                    $handleColor = $this->ask("Output #{$i} Color (optional)", $i === 0 ? 'green' : 'red');

                    $outputHandles[] = [
                        'id' => $handleId,
                        'label' => $handleLabel,
                        'color' => $handleColor,
                    ];
                }
            }
        }

        return [
            'name' => $name,
            'type' => $type,
            'tier' => $tier,
            'author' => $author,
            'description' => $description,
            'author_url' => $authorUrl,
            'repository' => $repository,
            'license' => $license,
            'has_multiple_outputs' => $hasMultipleOutputs,
            'output_handles' => $outputHandles,
            'force' => $this->option('force'),
        ];
    }

    protected function createDirectoryStructure(string $nodeDir): void
    {
        if (! is_dir($nodeDir)) {
            mkdir($nodeDir, 0755, true);
        }

        if (! is_dir($nodeDir . '/components')) {
            mkdir($nodeDir . '/components', 0755, true);
        }
    }

    protected function generateNodeClass(string $nodeDir, string $nodeClass, array $nodeInfo): void
    {
        $nodeType = Str::snake($nodeClass);
        $nodeName = Str::title(str_replace('_', ' ', Str::snake($nodeClass, ' ')));

        $categoryMap = [
            'trigger' => 'Triggers',
            'action' => 'Actions',
            'transform' => 'Transform',
            'flow' => 'Flow Control',
        ];

        $category = $categoryMap[$nodeInfo['type']] ?? 'Actions';

        $colorMap = [
            'trigger' => 'orange',
            'action' => 'blue',
            'transform' => 'purple',
            'flow' => 'yellow',
        ];
        $color = $colorMap[$nodeInfo['type']] ?? 'blue';

        $iconMap = [
            'trigger' => 'heroicon-o-bolt',
            'action' => 'heroicon-o-paper-airplane',
            'transform' => 'heroicon-o-funnel',
            'flow' => 'heroicon-o-arrows-pointing-out',
        ];
        $icon = $iconMap[$nodeInfo['type']] ?? 'heroicon-o-cube';

        // Build positioning configuration
        $positioningCode = $this->buildPositioningCode($nodeInfo);

        // Build optional metadata
        $optionalMetadata = '';
        if ($nodeInfo['author_url']) {
            $optionalMetadata .= "\n            'author_url' => '{$nodeInfo['author_url']}',";
        }
        if ($nodeInfo['repository']) {
            $optionalMetadata .= "\n            'repository' => '{$nodeInfo['repository']}',";
        }
        if ($nodeInfo['license']) {
            $optionalMetadata .= "\n            'license' => '{$nodeInfo['license']}',";
        }
        if ($nodeInfo['tier'] === 'PRO') {
            $optionalMetadata .= "\n            'requires_license' => true,";
        }

        $template = <<<PHP
<?php

namespace Voodflow\\Voodflow\\Nodes\\{$nodeClass};

use Voodflow\\Voodflow\\Contracts\\NodeInterface;
use Voodflow\\Voodflow\\Execution\\ExecutionContext;
use Voodflow\\Voodflow\\Execution\\ExecutionResult;

/**
 * {$nodeName}
 * 
 * {$nodeInfo['description']}
 * 
 * @author {$nodeInfo['author']}
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
        ];
    }
    
    public static function metadata(): array
    {
        return [
            'author' => '{$nodeInfo['author']}',
            'version' => '1.0.0',
            'tier' => '{$nodeInfo['tier']}',
            'color' => '{$color}',
            'icon' => '{$icon}',
            'group' => '{$category}',
            'category' => '{$nodeInfo['type']}',
            'description' => '{$nodeInfo['description']}',{$optionalMetadata}
            
            {$positioningCode}
            
            'data_flow' => [
                'accepts_input' => true,
                'produces_output' => true,
                'output_schema' => 'passthrough',
            ],
        ];
    }
    
    /**
     * Execute the node logic
     */
    public function execute(ExecutionContext \$context): ExecutionResult
    {
        // TODO: Implement your node logic here
        
        // Get input data from previous node
        \$inputData = \$context->input;
        
        // Get configuration
        // \$config = \$context->getConfig('field_name', 'default');
        
        // Process and return output
        return ExecutionResult::success(\$inputData);
        
        // For nodes with multiple outputs:
        // return ExecutionResult::success(\$data)->toOutput('handle_id');
    }
    
    /**
     * Validate node configuration
     */
    public function validate(array \$config): array
    {
        \$errors = [];
        
        // TODO: Add validation logic
        
        return \$errors;
    }
}

PHP;

        file_put_contents($nodeDir . "/{$nodeClass}.php", $template);
    }

    protected function buildPositioningCode(array $nodeInfo): string
    {
        if (! $nodeInfo['has_multiple_outputs']) {
            return "'positioning' => [
                'input' => true,
                'output' => true,
            ],";
        }

        $outputsCode = '';
        foreach ($nodeInfo['output_handles'] as $handle) {
            $colorLine = $handle['color'] ? "\n                        'color' => '{$handle['color']}'," : '';
            $outputsCode .= "
                    [
                        'id' => '{$handle['id']}',
                        'type' => 'main',
                        'label' => '{$handle['label']}',{$colorLine}
                    ],";
        }

        return "'positioning' => [
                'inputs' => [
                    [
                        'id' => 'main',
                        'type' => 'main',
                        'label' => 'Input',
                        'required' => true,
                    ]
                ],
                'outputs' => [{$outputsCode}
                ],
            ],";
    }

    protected function generateReactComponent(string $nodeDir, string $nodeClass, array $nodeInfo): void
    {
        $hasMultipleOutputs = $nodeInfo['has_multiple_outputs'];

        // Determine color based on type
        $colorMap = [
            'trigger' => 'orange',
            'action' => 'blue',
            'transform' => 'purple',
            'flow' => 'yellow',
        ];
        $color = $colorMap[$nodeInfo['type']] ?? 'blue';

        // Icon SVG based on type
        $iconMap = [
            'trigger' => '<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />',
            'action' => '<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />',
            'transform' => '<path fillRule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clipRule="evenodd" />',
            'flow' => '<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />',
        ];
        $iconSvg = $iconMap[$nodeInfo['type']] ?? '<path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />';

        // Build output handles code
        $outputHandlesCode = '';
        $addNodeButtonCode = '';

        if ($hasMultipleOutputs) {
            // Multiple outputs - no AddNodeButton
            foreach ($nodeInfo['output_handles'] as $index => $handle) {
                $topPosition = 30 + ($index * 40);
                $outputHandlesCode .= "\n                <Handle
                    id=\"{$handle['id']}\"
                    type=\"source\"
                    position={Position.Right}
                    className=\"!bg-{$color}-500 !w-3 !h-3 !border-2 !border-white\"
                    style={{ top: '{$topPosition}%', right: '-6px' }}
                />";
            }
        } else {
            // Single output with AddNodeButton
            $outputHandlesCode = "
                <Handle
                    type=\"source\"
                    position={Position.Right}
                    className={\"!bg-{$color}-500 !w-3 !h-3 !border-2 !border-white \" + (!isOutputConnected ? \"opacity-0\" : \"opacity-100\")}
                    style={{ right: '-6px', top: '50%' }}
                />";

            $addNodeButtonCode = "
                {/* Add Button */}
                {!isOutputConnected && (
                    <div className=\"absolute right-0 top-1/2 translate-x-1/2 -translate-y-1/2 z-10\">
                        <AddNodeButton
                            onAddNode={handleAddConnectedNode}
                            sourceNodeId={id}
                            livewireId={data.livewireId}
                            availableNodes={data.availableNodes}
                            color=\"{$color}\"
                        />
                    </div>
                )}";
        }

        $template = <<<JSX
import React, { useState } from 'react';
import { Handle, Position, useEdges, useReactFlow } from 'reactflow';
import ConfirmModal from '../../../../resources/js/components/ConfirmModal';
import AddNodeButton from '../../../../resources/js/components/AddNodeButton';
import VoodflowLogo from '../../../../resources/js/components/VoodflowLogo';

/**
 * {$nodeClass} React Component
 * 
 * {$nodeInfo['description']}
 * 
 * @author {$nodeInfo['author']}
 * @version 1.0.0
 * @see https://voodflow.com
 */
const {$nodeClass} = ({ id, data }) => {
    const { setNodes } = useReactFlow();
    const edges = useEdges();

    const [isExpanded, setIsExpanded] = useState(data.isNew || false);
    const [label, setLabel] = useState(data.label || '{$nodeInfo['name']}');
    const [description, setDescription] = useState(data.description || '');
    const [showDeleteModal, setShowDeleteModal] = useState(false);

    // Check connections
    const isConnected = edges.some(edge => edge.target === id);
    const isOutputConnected = edges.some(edge => edge.source === id);

    // Handle collapse and update isNew
    const handleCollapse = () => {
        setIsExpanded(false);
        if (data.isNew) {
            setNodes((nds) => nds.map((node) => {
                if (node.id === id) {
                    return {
                        ...node,
                        data: {
                            ...node.data,
                            isNew: false
                        }
                    };
                }
                return node;
            }));
        }
    };

    // Save configuration to backend
    const save = (newLabel = label, newDescription = description) => {
        if (data.livewireId && window.Livewire) {
            const component = window.Livewire.find(data.livewireId);
            if (component) {
                component.call('updateNodeConfig', {
                    nodeId: id,
                    label: newLabel,
                    description: newDescription,
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

    // Handle adding a connected node
    const handleAddConnectedNode = (nodeType, sourceNodeId) => {
        if (!data.livewireId || !window.Livewire) return;
        const component = window.Livewire.find(data.livewireId);
        if (!component) return;
        component.call('createGenericNode', { type: nodeType, sourceNodeId: sourceNodeId });
        handleCollapse();
    };

    return (
        <>
            <ConfirmModal
                isOpen={showDeleteModal}
                title="Delete {$nodeInfo['name']}"
                message={"Are you sure you want to delete \"" + label + "\"?"}
                onConfirm={confirmDelete}
                onCancel={() => setShowDeleteModal(false)}
            />

            <div className={"relative bg-white dark:bg-slate-900 border-2 rounded-xl " + (isConnected ? "border-{$color}-500" : "border-slate-300 dark:border-slate-600") + " shadow-lg min-w-[300px] max-w-[420px] transition-all duration-200"}>
                <Handle
                    type="target"
                    position={Position.Left}
                    className="!bg-{$color}-500 !w-3 !h-3 !border-2 !border-white"
                />

                {/* Header */}
                <div className="bg-{$color}-500 px-4 py-2.5 flex items-center justify-between rounded-t-lg">
                    <div className="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4 text-white">
                            {$iconSvg}
                        </svg>
                        <span className="text-xs font-bold text-white uppercase tracking-wider">
                            {label}
                        </span>
                    </div>

                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => setIsExpanded(!isExpanded)}
                            className="nodrag text-white/80 hover:text-white transition-colors"
                            title={isExpanded ? "Collapse" : "Expand"}
                            disabled={!isConnected}
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                className={"w-4 h-4 transition-transform " + (isExpanded ? "rotate-180" : "") + " " + (!isConnected ? "opacity-50" : "")}>
                                <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                            </svg>
                        </button>
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
                    {!isConnected ? (
                        <div className="text-center py-4">
                            <div className="text-slate-400 dark:text-slate-500 text-sm mb-2 flex justify-center opacity-50">
                                <VoodflowLogo width={60} height={60} />
                            </div>
                            <div className="text-{$color}-500 font-medium text-sm">Connect data</div>
                        </div>
                    ) : !isExpanded ? (
                        /* Collapsed View */
                        <div>
                            {description && (
                                <div className="text-slate-500 dark:text-slate-400 text-xs italic mb-2">
                                    {description}
                                </div>
                            )}
                            <div className="text-slate-400 dark:text-slate-500 italic text-sm">
                                Click to configure
                            </div>
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
                                        focus:ring-2 focus:ring-{$color}-500 focus:border-{$color}-500 outline-none"
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
                                        focus:ring-2 focus:ring-{$color}-500 focus:border-{$color}-500 outline-none"
                                />
                            </div>

                            {/* TODO: Add your custom configuration fields here */}
                            <div className="text-xs text-slate-400 italic text-center py-2">
                                Add your configuration fields here
                            </div>
                        </div>
                    )}
                </div>

                {/* Output Handle(s) */}{$outputHandlesCode}
{$addNodeButtonCode}
            </div>
        </>
    );
};


export default {$nodeClass};

JSX;

        file_put_contents($nodeDir . "/components/{$nodeClass}.jsx", $template);
    }

    protected function generateManifest(string $nodeDir, string $nodeClass, array $nodeInfo): void
    {
        $kebabName = \Illuminate\Support\Str::kebab($nodeClass);
        $namespace = "Voodflow\\Voodflow\\Nodes\\{$nodeClass}";

        $manifest = [
            'name' => $kebabName,
            'display_name' => $nodeInfo['name'], // Using name provided by user (e.g. MyNode)
            'version' => '1.0.0',
            'author' => $nodeInfo['author'],
            'description' => $nodeInfo['description'],
            'category' => $nodeInfo['type'],
            'tier' => $nodeInfo['tier'],
            'php' => [
                'class' => $nodeClass,
                'namespace' => $namespace,
            ],
            'javascript' => [
                'component' => $nodeClass,
                'bundle' => "dist/{$kebabName}.js",
            ],
        ];

        if ($nodeInfo['author_url']) {
            $manifest['author_url'] = $nodeInfo['author_url'];
        }
        if ($nodeInfo['repository']) {
            $manifest['repository'] = $nodeInfo['repository'];
        }
        if ($nodeInfo['license']) {
            $manifest['license'] = ['type' => $nodeInfo['license']];
        }

        file_put_contents($nodeDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
