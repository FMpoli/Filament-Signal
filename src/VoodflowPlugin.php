<?php

namespace Voodflow\Voodflow;

use Voodflow\Voodflow\Filament\Resources\SignalExecutionResource;
use Voodflow\Voodflow\Filament\Resources\SignalModelIntegrationResource;
use Voodflow\Voodflow\Filament\Resources\SignalWorkflowResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class VoodflowPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-signal';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            SignalWorkflowResource::class,
            SignalExecutionResource::class,
            SignalModelIntegrationResource::class,
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
