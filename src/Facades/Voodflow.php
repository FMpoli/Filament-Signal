<?php

namespace Voodflow\Voodflow\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Base33\FilamentSignal\FilamentSignal
 */
class Voodflow extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Base33\FilamentSignal\FilamentSignal::class;
    }
}
