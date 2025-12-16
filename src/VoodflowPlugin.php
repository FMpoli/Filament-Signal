<?php

namespace Voodflow\Voodflow;

// use Voodflow\Voodflow\Filament\Resources\ExecutionResource;
// use Voodflow\Voodflow\Filament\Resources\ModelIntegrationResource;
// use Voodflow\Voodflow\Filament\Resources\TemplateResource;
// use Voodflow\Voodflow\Filament\Resources\TriggerResource;
// use Voodflow\Voodflow\Filament\Resources\WorkflowResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Voodflow\Voodflow\Filament\Resources\ExecutionResource;
use Voodflow\Voodflow\Filament\Resources\ModelIntegrationResource;
use Voodflow\Voodflow\Filament\Resources\WorkflowResource;

class VoodflowPlugin implements Plugin
{
    public function getId(): string
    {
        return 'voodflow';
    }

    // public function register(Panel $panel): void
    // {
    //     $panel->resources([
    //         TriggerResource::class,
    //         WorkflowResource::class,
    //         ExecutionResource::class,
    //         TemplateResource::class,
    //         ModelIntegrationResource::class,
    //     ]);
    // }

    public function register(Panel $panel): void
    {
        $panel->resources([
            WorkflowResource::class,
            ExecutionResource::class,
            ModelIntegrationResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
