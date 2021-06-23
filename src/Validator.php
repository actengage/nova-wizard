<?php

namespace Actengage\Wizard;

use Illuminate\Support\Str;

class Validator
{
    protected $stack;

    protected $withs;

    public function __construct($withs = [], $stack = [])
    {
        $this->withs = collect($withs); 
        $this->stack = collect($stack);
    }

    public function with(callable $callback)
    {
        $this->withs[] = $callback;

        return $this;
    }

    public function is(callable $callback)
    {
        $this->stack[] = ['is', $callback];

        return $this;
    }

    public function not(callable $callback)
    {
        $this->stack[] = ['not', $callback];

        return $this;
    }

    public function or(callable $callback)
    {
        $this->stack[] = ['or', $callback];

        return $this;
    }
    

    public function orNot(callable $callback)
    {
        $this->stack[] = ['or not', $callback];

        return $this;
    }

    public function arguments($args)
    {
        $withs = $this->withs->map(function($callback) use ($args) {
            return $callback(...$args);
        });

        return array_merge($args, $withs->all());
    }

    public function call(...$args)
    {
        $handler = $this->handler(...$args);

        return $handler(...$args);
    }

    public function handler()
    {
        return function(...$args) {
            $args = $this->arguments($args);
            
            $handlers = collect($this->stack)->map(function($definition) use ($args) {
                [ $key, $callback ] = $definition;

                $method = $this->handlerMethodName($key);

                return $this->$method($callback);
            });

            return $handlers->reduce(function($carry, $fn) use ($args) {
                return $fn($carry, $args);
            }, true);
        };
    }

    protected function handleIsCallback(callable $callback)
    {
        return function($carry, $args) use ($callback) {
            return $carry && !!$callback(...$args);
        };
    }

    protected function handleNotCallback(callable $callback)
    {
        return function($carry, $args) use ($callback) {
            return $carry && !$callback(...$args);
        };
    }

    protected function handleOrCallback(callable $callback)
    {
        return function($carry, $args) use ($callback) {
            return $carry ?: $callback(...$args);
        };
    }

    protected function handleOrNotCallback(callable $callback)
    {
        return function($carry, $args) use ($callback) {
            return $carry ?: !$callback(...$args);
        };
    }

    protected function handlerMethodName($key)
    {
        return Str::camel('handle ' . $key . ' callback');
    }
}