<?php

namespace Livewire\HydrationMiddleware\PropertyHydrators;

class CollectionHydrator
{
    public $key = 'collections';

    public function hydrate($value)
    {
        return collect($value);        
    }

    public function dehydrate($value)
    {
        return $value->toArray();
    }
}
