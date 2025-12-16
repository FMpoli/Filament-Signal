<?php

namespace Voodflow\Voodflow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PackageNodeCommand extends Command
{
    protected $signature = 'voodflow:package-node {node : The node class name (e.g., EmailNode)}';

    protected $description = 'Package a node for distribution';

    public function handle(): int
    {
        $nodeClass = $this->argument('node');
        $nodeDir = __DIR__ . '/../../Nodes/' . $nodeClass;

        if (! File::isDirectory($nodeDir)) {
            $this->error("Node directory not found: {$nodeDir}");
            return self::FAILURE;
        }

        // Check for manifest.json
        $manifestPath = $nodeDir . '/manifest.json';
        if (! File::exists($manifestPath)) {
            $this->error('manifest.json not found. Creating interactive manifest...');
            $this->newLine();
            $this->createManifestTemplate($nodeDir, $nodeClass);
            $this->newLine();
            $this->info('📦 Continuing with package creation...');
            $this->newLine();
        }

        // Validate manifest
        $manifest = json_decode(File::get($manifestPath), true);
        if (! $this->validateManifest($manifest)) {
            return self::FAILURE;
        }

        // Build the JavaScript bundle
        $this->info('Building JavaScript bundle...');
        if (! $this->buildJavaScriptBundle($nodeDir, $nodeClass)) {
            $this->error('Failed to build JavaScript bundle');
            return self::FAILURE;
        }

        // Create package
        $this->info('Creating package...');
        $zipPath = $this->createPackage($nodeDir, $nodeClass, $manifest);

        if ($zipPath) {
            $this->info("✅ Package created successfully: {$zipPath}");
            $this->info("📦 You can now distribute this package!");
            
            if ($manifest['tier'] === 'PREMIUM' || $manifest['tier'] === 'PRO') {
                $this->warn('⚠️  This is a paid node. Make sure to set up licensing on Anystack.');
            }
            
            return self::SUCCESS;
        }

        $this->error('Failed to create package');
        return self::FAILURE;
    }

    protected function createManifestTemplate(string $nodeDir, string $nodeClass): void
    {
        $this->info('📝 Creating manifest for ' . $nodeClass);
        $this->newLine();
        
        $kebabName = $this->toKebabCase($nodeClass);
        
        // Basic Info
        $displayName = $this->ask('Display name', $nodeClass);
        $description = $this->ask('Description', "Description of {$nodeClass}");
        $author = $this->ask('Author name', 'Your Name');
        $authorUrl = $this->ask('Author URL', 'https://yourwebsite.com');
        $version = $this->ask('Version', '1.0.0');
        
        // Category
        $category = $this->choice(
            'Category',
            ['action', 'trigger', 'flow-control', 'data', 'integration', 'utility'],
            0
        );
        
        // Tier
        $tier = $this->choice(
            'Tier (pricing)',
            ['FREE', 'CORE', 'PRO', 'PREMIUM'],
            0
        );
        
        // Icon
        $this->info('💡 Tip: Use Heroicons (e.g., heroicon-o-envelope, heroicon-o-cube)');
        $icon = $this->ask('Icon', 'heroicon-o-cube');
        
        // Color
        $color = $this->choice(
            'Color',
            ['blue', 'green', 'red', 'yellow', 'purple', 'pink', 'indigo', 'gray'],
            0
        );
        
        $template = [
            'name' => $kebabName,
            'display_name' => $displayName,
            'version' => $version,
            'author' => $author,
            'author_url' => $authorUrl,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
            'category' => $category,
            'tier' => $tier,
            'voodflow' => [
                'min_version' => '1.0.0',
            ],
            'php' => [
                'class' => $nodeClass,
                'namespace' => "Voodflow\\Voodflow\\Nodes\\{$nodeClass}",
            ],
            'javascript' => [
                'component' => $nodeClass,
                'bundle' => "dist/{$kebabName}.js",
            ],
        ];
        
        // Licensing (only for paid tiers)
        if (in_array($tier, ['PRO', 'PREMIUM'])) {
            $this->newLine();
            $this->warn('⚠️  This is a paid node. License configuration required.');
            
            $licenseType = $this->choice(
                'License type',
                ['commercial', 'subscription'],
                0
            );
            
            $requiresActivation = $this->confirm('Requires activation?', true);
            
            if ($requiresActivation) {
                $anystackProductId = $this->ask('Anystack Product ID (e.g., prod_abc123)');
                $validationUrl = $this->ask(
                    'License validation URL',
                    'https://api.anystack.sh/v1/licenses/validate'
                );
                
                $template['license'] = [
                    'type' => $licenseType,
                    'requires_activation' => true,
                    'anystack_product_id' => $anystackProductId,
                    'validation_url' => $validationUrl,
                ];
            } else {
                $template['license'] = [
                    'type' => $licenseType,
                    'requires_activation' => false,
                ];
            }
        }
        
        // Config Schema
        if ($this->confirm('Add configuration fields?', false)) {
            $this->info('💡 You can add more fields later by editing manifest.json');
            $template['config_schema'] = [];
            
            while (true) {
                $fieldName = $this->ask('Field name (leave empty to finish)');
                if (empty($fieldName)) {
                    break;
                }
                
                $fieldLabel = $this->ask('Field label');
                $fieldType = $this->choice(
                    'Field type',
                    ['string', 'number', 'boolean', 'select', 'textarea'],
                    0
                );
                
                $required = $this->confirm('Required?', false);
                $encrypted = $this->confirm('Encrypted? (for sensitive data)', false);
                
                $template['config_schema'][$fieldName] = [
                    'type' => $fieldType,
                    'label' => $fieldLabel,
                    'required' => $required,
                ];
                
                if ($encrypted) {
                    $template['config_schema'][$fieldName]['encrypted'] = true;
                }
                
                $description = $this->ask('Field description (optional)');
                if ($description) {
                    $template['config_schema'][$fieldName]['description'] = $description;
                }
            }
        }
        
        // Distribution options
        $this->newLine();
        $this->info('📦 Distribution Options');
        $includeSource = $this->choice(
            'Include JSX source code in package?',
            [
                'yes' => 'Yes - Open source (recommended for FREE/CORE)',
                'no' => 'No - Bundle only (recommended for PREMIUM)',
            ],
            $tier === 'FREE' || $tier === 'CORE' ? 'yes' : 'no'
        );
        
        $template['distribution'] = [
            'include_source' => $includeSource === 'yes',
        ];

        File::put(
            $nodeDir . '/manifest.json',
            json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        
        $this->newLine();
        $this->info('✅ Manifest created successfully!');
        $this->line('   Location: ' . $nodeDir . '/manifest.json');
    }

    protected function validateManifest(array $manifest): bool
    {
        $required = ['name', 'version', 'author', 'tier', 'php', 'javascript'];
        
        foreach ($required as $field) {
            if (! isset($manifest[$field])) {
                $this->error("Missing required field in manifest: {$field}");
                return false;
            }
        }

        $validTiers = ['FREE', 'CORE', 'PRO', 'PREMIUM'];
        if (! in_array($manifest['tier'], $validTiers)) {
            $this->error("Invalid tier. Must be one of: " . implode(', ', $validTiers));
            return false;
        }

        if (in_array($manifest['tier'], ['PRO', 'PREMIUM']) && ! isset($manifest['license'])) {
            $this->error('Paid nodes must include a license section in manifest');
            return false;
        }

        return true;
    }

    protected function buildJavaScriptBundle(string $nodeDir, string $nodeClass): bool
    {
        $componentPath = $nodeDir . '/components/' . $nodeClass . '.jsx';
        
        if (! File::exists($componentPath)) {
            $this->error("Component not found: {$componentPath}");
            return false;
        }

        // Create dist directory
        $distDir = $nodeDir . '/dist';
        if (! File::isDirectory($distDir)) {
            File::makeDirectory($distDir, 0755, true);
        }

        $kebabName = $this->toKebabCase($nodeClass);
        $bundlePath = $distDir . '/' . $kebabName . '.js';

        // Use esbuild to create standalone bundle
        $esbuildCmd = sprintf(
            'npx esbuild %s --bundle --format=iife --global-name=%s --outfile=%s --minify',
            escapeshellarg($componentPath),
            escapeshellarg($nodeClass),
            escapeshellarg($bundlePath)
        );

        exec($esbuildCmd, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('esbuild failed: ' . implode("\n", $output));
            return false;
        }

        return File::exists($bundlePath);
    }

    protected function createPackage(string $nodeDir, string $nodeClass, array $manifest): ?string
    {
        $kebabName = $manifest['name'];
        $version = $manifest['version'];
        $packageName = "{$kebabName}-{$version}.zip";
        $packagePath = storage_path("app/voodflow-packages/{$packageName}");

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($packagePath));

        $zip = new ZipArchive();
        if ($zip->open($packagePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        // Add files to zip
        $files = [
            'manifest.json',
            $nodeClass . '.php',
            'dist/' . $kebabName . '.js',
        ];
        
        // Include JSX source if specified in manifest
        $includeSource = $manifest['distribution']['include_source'] ?? true;
        if ($includeSource) {
            $files[] = 'components/' . $nodeClass . '.jsx';
            $this->line('   Including JSX source (open-source)');
        } else {
            $this->line('   Bundle only (no source code)');
        }

        // Add README if exists
        if (File::exists($nodeDir . '/README.md')) {
            $files[] = 'README.md';
        }

        foreach ($files as $file) {
            $fullPath = $nodeDir . '/' . $file;
            if (File::exists($fullPath)) {
                $zip->addFile($fullPath, $kebabName . '/' . $file);
            }
        }

        $zip->close();

        return $packagePath;
    }

    protected function toKebabCase(string $string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
    }
}
