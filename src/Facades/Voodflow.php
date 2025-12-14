<?php

namespace Voodflow\Voodflow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Voodflow\Voodflow\FilamentSignal
 */
class FilamentSignal extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Voodflow\Voodflow\FilamentSignal::class;
    }
}
