<?php

namespace Livewire;

class PropertyHydratorManager
{
    protected $handlers = [];

    public function register($type, $handler)
    {
        $this->handlers[$type] = $handler;
    }
   
    public function getByKey($key)
    {
        foreach ($this->handlers as $handler) {
            $theHandler = is_string($handler) ? new $handler : $handler;

            if ($theHandler->key === $key) return $theHandler;
        }
    }

    public function getByType($type)
    {
        if (! isset($this->handlers[$type])) return; 

        return is_string($this->handlers[$type]) ? new $this->handlers[$type] : $this->handlers[$type];
    }
}
