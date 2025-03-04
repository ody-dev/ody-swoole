<?php

namespace Ody\Swoole;
class Event extends Container
{
    function set($key, $item)
    {
        if(is_callable($item)){
            return parent::set($key, $item);
        }else{
            return false;
        }
    }

    /**
     * @param $event
     * @param mixed ...$args
     * @return mixed|null
     * @throws \Throwable
     */
    public function hook($event, ...$args)
    {
        $call = $this->get($event);
        if(is_callable($call)){
            return call_user_func($call,...$args);
        }else{
            return null;
        }
    }
}