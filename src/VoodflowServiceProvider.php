<?php

namespace Voodflow\Voodflow;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Voodflow\Voodflow\Console\Commands\MakeNodeCommand;
use Voodflow\Voodflow\Console\Commands\PackageNodeCommand;
use Voodflow\Voodflow\Console\Commands\TestPackageCommand;
use Voodflow\Voodflow\Console\Commands\MakeSignalNodeCommand;
use Voodflow\Voodflow\Models\ModelIntegration;
use Voodflow\Voodflow\Services\EventRegistrar;
use Voodflow\Voodflow\Services\DynamicNodeLoader;
use Voodflow\Voodflow\Support\EloquentEventMap;
use Voodflow\Voodflow\Support\EventRegistry;
use Voodflow\Voodflow\Support\ModelRegistry;
use Voodflow\Voodflow\Support\PayloadFieldAnalyzer;
use Voodflow\Voodflow\Support\ReverseRelationRegistrar;
use Voodflow\Voodflow\Support\ReverseRelationRegistry;
use Voodflow\Voodflow\Support\ReverseRelationWarmup;
use Voodflow\Voodflow\Testing\TestsFilamentSignal;

class VoodflowServiceProvider extends PackageServiceProvider
{
    public static string $name = 'voodflow';

    public static string $viewNamespace = 'voodflow';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('voodflow/voodflow');
            });

        $configFileName = 'voodflow';

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile($configFileName);
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(EventRegistry::class, fn (): EventRegistry => new EventRegistry);
        $this->app->singleton(ModelRegistry::class, fn (): ModelRegistry => new ModelRegistry);
        $this->app->singleton(ReverseRelationRegistry::class, fn (): ReverseRelationRegistry => new ReverseRelationRegistry);
        $this->app->singleton(ReverseRelationRegistrar::class, fn ($app): ReverseRelationRegistrar => new ReverseRelationRegistrar(
            $app->make(ReverseRelationRegistry::class)
        ));
        $this->app->singleton(ReverseRelationWarmup::class, fn ($app): ReverseRelationWarmup => new ReverseRelationWarmup(
            $app->make(EventRegistry::class),
            $app->make(PayloadFieldAnalyzer::class),
            $app->make(ModelRegistry::class),
            $app->make(ReverseRelationRegistrar::class)
        ));
        $this->app->singleton(EloquentEventMap::class, fn (): EloquentEventMap => new EloquentEventMap);
        $this->app->singleton(DynamicNodeLoader::class, fn (): DynamicNodeLoader => new DynamicNodeLoader);
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/voodflow/{$file->getFilename()}"),
                ], 'voodflow-stubs');
            }

            // Publish JavaScript assets
            $jsPath = __DIR__ . '/../resources/dist/filament-signal.js';
            if (file_exists($jsPath)) {
                $this->publishes([
                    $jsPath => public_path('js/voodflow/voodflow/voodflow-scripts.js'),
                ], 'voodflow-assets');
            }

            // Publish CSS assets
            $cssPath = __DIR__ . '/../resources/dist/filament-signal.css';
            if (file_exists($cssPath)) {
                $this->publishes([
                    $cssPath => public_path('css/voodflow/voodflow/voodflow-styles.css'),
                ], 'voodflow-assets');
            }
        }

        // Testing
        Testable::mixin(new TestsFilamentSignal);

        $this->bootModelIntegrations();

        app()->booted(function (): void {
            app(EventRegistrar::class)->register();
        });
    }

    protected function bootModelIntegrations(): void
    {
        if (! class_exists(ModelIntegration::class)) {
            return;
        }

        try {
            $table = config('voodflow.table_names.model_integrations', 'signal_model_integrations');
            if (! Schema::hasTable($table)) {
                return;
            }
        } catch (\Throwable $exception) {
            return;
        }

        ModelIntegration::query()->get()->each->registerOnBoot();
    }

    protected function getAssetPackageName(): ?string
    {
        return 'voodflow/voodflow';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        $assets = [];

        $cssPath = __DIR__ . '/../resources/dist/filament-signal.css';
        if (file_exists($cssPath)) {
            $assets[] = Css::make('voodflow-styles', $cssPath);
        }

        $jsPath = __DIR__ . '/../resources/dist/filament-signal.js';
        if (file_exists($jsPath)) {
            $assets[] = Js::make('voodflow-scripts', $jsPath);
        }

        return $assets;
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            MakeSignalNodeCommand::class,
            MakeNodeCommand::class,
            PackageNodeCommand::class,
            TestPackageCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        $nodeLoader = app(DynamicNodeLoader::class);
        
        return [
            'dynamicNodeBundles' => $nodeLoader->getInstalledNodeBundles(),
        ];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_voodflow_table',
        ];
    }
}
