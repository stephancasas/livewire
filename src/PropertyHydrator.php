<?php

namespace Livewire;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void register($propertyTypeClass, $hydratorClass)
 *
 * @see \Livewire\HydrationMiddleware\HydratePublicProperties
 */
class PropertyHydrator extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'livewire.property-manager';
    }
}
