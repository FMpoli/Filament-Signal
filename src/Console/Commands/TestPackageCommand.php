<?php

namespace Voodflow\Voodflow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class TestPackageCommand extends Command
{
    protected $signature = 'voodflow:test-package {package : Path to the package ZIP file}';

    protected $description = 'Test and validate a Voodflow node package';

    public function handle(): int
    {
        $packagePath = $this->argument('package');
        
        // Support relative paths
        if (!str_starts_with($packagePath, '/')) {
            $packagePath = base_path($packagePath);
        }

        if (!File::exists($packagePath)) {
            $this->error("Package not found: {$packagePath}");
            return self::FAILURE;
        }

        $this->info('🧪 Testing Voodflow Node Package');
        $this->line('   Package: ' . basename($packagePath));
        $this->newLine();

        // Extract to temp directory
        $tempDir = sys_get_temp_dir() . '/voodflow-test-' . uniqid();
        
        if (!$this->extractPackage($packagePath, $tempDir)) {
            return self::FAILURE;
        }

        // Find the node directory (should be the only directory in temp)
        $dirs = File::directories($tempDir);
        if (count($dirs) !== 1) {
            $this->error('Invalid package structure. Expected single root directory.');
            File::deleteDirectory($tempDir);
            return self::FAILURE;
        }

        $nodeDir = $dirs[0];
        $nodeName = basename($nodeDir);

        $this->info("📦 Node: {$nodeName}");
        $this->newLine();

        // Run tests
        $passed = 0;
        $failed = 0;

        // Test 1: Manifest exists and is valid
        if ($this->testManifest($nodeDir)) {
            $passed++;
        } else {
            $failed++;
        }

        // Test 2: PHP class exists
        if ($this->testPhpClass($nodeDir)) {
            $passed++;
        } else {
            $failed++;
        }

        // Test 3: JavaScript bundle exists
        if ($this->testJsBundle($nodeDir)) {
            $passed++;
        } else {
            $failed++;
        }

        // Test 4: Bundle is valid JavaScript
        if ($this->testBundleValidity($nodeDir)) {
            $passed++;
        } else {
            $failed++;
        }

        // Show package contents
        $this->newLine();
        $this->info('📁 Package Contents:');
        $this->showPackageContents($nodeDir);

        // Cleanup
        File::deleteDirectory($tempDir);

        // Summary
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($failed === 0) {
            $this->info("✅ All tests passed! ({$passed}/{$passed})");
            $this->info('📦 This package is ready for distribution!');
            return self::SUCCESS;
        } else {
            $this->error("❌ Some tests failed ({$passed} passed, {$failed} failed)");
            $this->warn('⚠️  Fix the issues before distributing this package.');
            return self::FAILURE;
        }
    }

    protected function extractPackage(string $packagePath, string $tempDir): bool
    {
        $this->line('→ Extracting package...');

        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== true) {
            $this->error('  ✗ Failed to open ZIP file');
            return false;
        }

        if (!$zip->extractTo($tempDir)) {
            $this->error('  ✗ Failed to extract ZIP file');
            $zip->close();
            return false;
        }

        $zip->close();
        $this->info('  ✓ Package extracted');
        return true;
    }

    protected function testManifest(string $nodeDir): bool
    {
        $this->line('→ Testing manifest.json...');

        $manifestPath = $nodeDir . '/manifest.json';
        
        if (!File::exists($manifestPath)) {
            $this->error('  ✗ manifest.json not found');
            return false;
        }

        $manifest = json_decode(File::get($manifestPath), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('  ✗ Invalid JSON: ' . json_last_error_msg());
            return false;
        }

        // Check required fields
        $required = ['name', 'version', 'author', 'tier', 'php', 'javascript'];
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($manifest[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            $this->error('  ✗ Missing required fields: ' . implode(', ', $missing));
            return false;
        }

        $this->info('  ✓ Manifest is valid');
        $this->line('    Name: ' . $manifest['name']);
        $this->line('    Version: ' . $manifest['version']);
        $this->line('    Tier: ' . $manifest['tier']);
        $this->line('    Author: ' . $manifest['author']);
        
        return true;
    }

    protected function testPhpClass(string $nodeDir): bool
    {
        $this->line('→ Testing PHP class...');

        $manifest = json_decode(File::get($nodeDir . '/manifest.json'), true);
        $phpClass = $manifest['php']['class'] ?? null;

        if (!$phpClass) {
            $this->error('  ✗ PHP class not specified in manifest');
            return false;
        }

        $phpFile = $nodeDir . '/' . $phpClass . '.php';
        
        if (!File::exists($phpFile)) {
            $this->error("  ✗ PHP file not found: {$phpClass}.php");
            return false;
        }

        $content = File::get($phpFile);
        
        // Basic validation
        if (!str_contains($content, "class {$phpClass}")) {
            $this->error("  ✗ Class {$phpClass} not found in PHP file");
            return false;
        }

        $this->info("  ✓ PHP class exists: {$phpClass}.php");
        return true;
    }

    protected function testJsBundle(string $nodeDir): bool
    {
        $this->line('→ Testing JavaScript bundle...');

        $manifest = json_decode(File::get($nodeDir . '/manifest.json'), true);
        $bundlePath = $manifest['javascript']['bundle'] ?? null;

        if (!$bundlePath) {
            $this->error('  ✗ Bundle path not specified in manifest');
            return false;
        }

        $fullBundlePath = $nodeDir . '/' . $bundlePath;
        
        if (!File::exists($fullBundlePath)) {
            $this->error("  ✗ Bundle not found: {$bundlePath}");
            return false;
        }

        $size = File::size($fullBundlePath);
        $sizeKb = round($size / 1024, 2);
        
        $this->info("  ✓ Bundle exists: {$bundlePath} ({$sizeKb} KB)");
        return true;
    }

    protected function testBundleValidity(string $nodeDir): bool
    {
        $this->line('→ Testing bundle validity...');

        $manifest = json_decode(File::get($nodeDir . '/manifest.json'), true);
        $bundlePath = $nodeDir . '/' . $manifest['javascript']['bundle'];
        
        $content = File::get($bundlePath);
        
        // Check if it looks like JavaScript
        if (strlen($content) < 10) {
            $this->error('  ✗ Bundle is too small (likely empty)');
            return false;
        }

        // Check for common JS patterns
        $hasValidJs = str_contains($content, 'function') || 
                     str_contains($content, '=>') ||
                     str_contains($content, 'var ') ||
                     str_contains($content, 'const ') ||
                     str_contains($content, 'let ');

        if (!$hasValidJs) {
            $this->warn('  ⚠ Bundle might not contain valid JavaScript');
            return false;
        }

        $this->info('  ✓ Bundle appears to be valid JavaScript');
        return true;
    }

    protected function showPackageContents(string $nodeDir): void
    {
        $files = File::allFiles($nodeDir);
        
        foreach ($files as $file) {
            $relativePath = str_replace($nodeDir . '/', '', $file->getPathname());
            $size = $file->getSize();
            $sizeKb = $size > 1024 ? round($size / 1024, 2) . ' KB' : $size . ' B';
            
            $icon = match(true) {
                str_ends_with($relativePath, '.json') => '📄',
                str_ends_with($relativePath, '.php') => '🐘',
                str_ends_with($relativePath, '.jsx') => '⚛️ ',
                str_ends_with($relativePath, '.js') => '📦',
                str_ends_with($relativePath, '.md') => '📝',
                default => '📄',
            };
            
            $this->line("   {$icon} {$relativePath} ({$sizeKb})");
        }
    }
}
