<?php
namespace Ody\Swoole;

trait Singleton
{
    private static $instance;

    /**
     * @param mixed ...$args
     * @return static
     */
    static function getInstance(...$args): static
    {
        if(!isset(static::$instance)){
            static::$instance = new static(...$args);
        }
        return static::$instance;
    }
}