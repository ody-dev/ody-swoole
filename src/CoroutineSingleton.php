<?php

namespace Ody\Swoole;

use Swoole\Coroutine;

trait CoroutineSingleton
{
    private static $instance = [];

    /**
     * @param mixed ...$args
     * @return static
     */
    public static function getInstance(...$args)
    {
        $cid = Coroutine::getCid();
        if(!isset(static::$instance[$cid])){
            static::$instance[$cid] = new static(...$args);
            if($cid > 0){
                Coroutine::defer(function ()use($cid){
                    unset(static::$instance[$cid]);
                });
            }
        }
        return static::$instance[$cid];
    }

    function destroy(int $cid = null)
    {
        if($cid === null){
            $cid = Coroutine::getCid();
        }
        unset(static::$instance[$cid]);
    }
}