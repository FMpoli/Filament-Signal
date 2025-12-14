<?php

namespace Voodflow\Voodflow;


use Voodflow\Voodflow\Models\ModelIntegration;
use Voodflow\Voodflow\Services\SignalEventRegistrar;
use Voodflow\Voodflow\Support\ReverseRelationRegistrar;
use Voodflow\Voodflow\Support\ReverseRelationRegistry;
use Voodflow\Voodflow\Support\ReverseRelationWarmup;
use Voodflow\Voodflow\Support\SignalEloquentEventMap;
use Voodflow\Voodflow\Support\SignalEventRegistry;
use Voodflow\Voodflow\Support\SignalModelRegistry;
use Voodflow\Voodflow\Support\SignalPayloadFieldAnalyzer;
use Voodflow\Voodflow\Support\SignalWebhookTemplate;
use Voodflow\Voodflow\Support\SignalWebhookTemplateRegistry;
use Voodflow\Voodflow\Testing\TestsFilamentSignal;
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

class VoodflowServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-signal';

    public static string $viewNamespace = 'filament-signal';

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
                    ->askToStarRepoOnGitHub('base33/filament-signal');
            });

        $configFileName = 'signal';

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
        $this->app->singleton(SignalWebhookTemplateRegistry::class, fn(): SignalWebhookTemplateRegistry => new SignalWebhookTemplateRegistry);
        $this->app->singleton(SignalEventRegistry::class, fn(): SignalEventRegistry => new SignalEventRegistry);
        $this->app->singleton(SignalModelRegistry::class, fn(): SignalModelRegistry => new SignalModelRegistry);
        $this->app->singleton(ReverseRelationRegistry::class, fn(): ReverseRelationRegistry => new ReverseRelationRegistry);
        $this->app->singleton(ReverseRelationRegistrar::class, fn($app): ReverseRelationRegistrar => new ReverseRelationRegistrar(
            $app->make(ReverseRelationRegistry::class)
        ));
        $this->app->singleton(ReverseRelationWarmup::class, fn($app): ReverseRelationWarmup => new ReverseRelationWarmup(
            $app->make(SignalEventRegistry::class),
            $app->make(SignalPayloadFieldAnalyzer::class),
            $app->make(SignalModelRegistry::class),
            $app->make(ReverseRelationRegistrar::class)
        ));
        $this->app->singleton(SignalEloquentEventMap::class, fn(): SignalEloquentEventMap => new SignalEloquentEventMap);
    }

    public function packageBooted(): void
    {
        $this->registerConfiguredWebhookTemplates();

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
                    $file->getRealPath() => base_path("stubs/filament-signal/{$file->getFilename()}"),
                ], 'filament-signal-stubs');
            }

            // Publish JavaScript assets
            $jsPath = __DIR__ . '/../resources/dist/filament-signal.js';
            if (file_exists($jsPath)) {
                $this->publishes([
                    $jsPath => public_path('js/base33/filament-signal/filament-signal-scripts.js'),
                ], 'filament-signal-assets');
            }

            // Publish CSS assets
            $cssPath = __DIR__ . '/../resources/dist/filament-signal.css';
            if (file_exists($cssPath)) {
                $this->publishes([
                    $cssPath => public_path('css/base33/filament-signal/filament-signal-styles.css'),
                ], 'filament-signal-assets');
            }
        }

        // Testing
        Testable::mixin(new TestsFilamentSignal);

        $this->bootModelIntegrations();

        app()->booted(function (): void {
            app(SignalEventRegistrar::class)->register();
        });
    }

    protected function bootModelIntegrations(): void
    {
        if (!class_exists(ModelIntegration::class)) {
            return;
        }

        try {
            $table = config('signal.table_names.model_integrations', 'signal_model_integrations');
            if (!Schema::hasTable($table)) {
                return;
            }
        } catch (\Throwable $exception) {
            return;
        }

        ModelIntegration::query()->get()->each->registerOnBoot();
    }

    protected function registerConfiguredWebhookTemplates(): void
    {
        $templates = config('signal.webhook_templates', []);

        if (empty($templates)) {
            return;
        }

        $registry = app(SignalWebhookTemplateRegistry::class);

        foreach ($templates as $template) {
            if (is_array($template)) {
                $template = SignalWebhookTemplate::fromArray($template);
            }

            if ($template instanceof SignalWebhookTemplate) {
                $registry->register($template);
            }
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return 'base33/filament-signal';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        $assets = [];

        $cssPath = __DIR__ . '/../resources/dist/filament-signal.css';
        if (file_exists($cssPath)) {
            $assets[] = Css::make('filament-signal-styles', $cssPath);
        }

        $jsPath = __DIR__ . '/../resources/dist/filament-signal.js';
        if (file_exists($jsPath)) {
            $assets[] = Js::make('filament-signal-scripts', $jsPath);
        }

        return $assets;
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            // TODO Phase 2: Uncomment after renaming command files
            // \Voodflow\Voodflow\Console\Commands\VoodflowCommand::class,
            // \Voodflow\Voodflow\Console\Commands\MakeVoodflowNodeCommand::class,
            // \Voodflow\Voodflow\Console\Commands\DebugVoodflow::class,
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
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_signal_table',
        ];
    }
}
