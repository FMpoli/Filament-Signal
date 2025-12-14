<?php

namespace Voodflow\Voodflow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Voodflow\Voodflow\FilamentSignal
 */
class Voodflow extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Voodflow\Voodflow\FilamentSignal::class;
    }
}
