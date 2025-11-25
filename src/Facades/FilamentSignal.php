<?php

namespace Base33\FilamentSignal\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Base33\FilamentSignal\FilamentSignal
 */
class FilamentSignal extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Base33\FilamentSignal\FilamentSignal::class;
    }
}
