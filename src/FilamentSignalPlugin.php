<?php

namespace Base33\FilamentSignal;

use Base33\FilamentSignal\Filament\Resources\SignalActionLogResource;
use Base33\FilamentSignal\Filament\Resources\SignalTemplateResource;
use Base33\FilamentSignal\Filament\Resources\SignalTriggerResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentSignalPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-signal';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            SignalTemplateResource::class,
            SignalTriggerResource::class,
            SignalActionLogResource::class,
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
