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
            $this->error('manifest.json not found. Creating template...');
            $this->createManifestTemplate($nodeDir, $nodeClass);
            $this->info('Please edit manifest.json and run this command again.');
            return self::FAILURE;
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
        $kebabName = $this->toKebabCase($nodeClass);
        
        $template = [
            'name' => $kebabName,
            'display_name' => $nodeClass,
            'version' => '1.0.0',
            'author' => 'Your Name',
            'author_url' => 'https://yourwebsite.com',
            'description' => 'Description of your node',
            'icon' => 'heroicon-o-cube',
            'color' => 'blue',
            'category' => 'action',
            'tier' => 'FREE',
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

        File::put(
            $nodeDir . '/manifest.json',
            json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
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
            'components/' . $nodeClass . '.jsx',
            'dist/' . $kebabName . '.js',
        ];

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
