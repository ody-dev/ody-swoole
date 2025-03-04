<?php

namespace Ody\Swoole;

use Swoole\Coroutine\Channel;

class ChannelManger
{
    use Singleton;
    private $list = [];

    function add($name,$size = 1024):void
    {
        if(!isset($this->list[$name])){
            $chan = new Channel($size);
            $this->list[$name] = $chan;
        }
    }

    function get($name):?Channel
    {
        if(isset($this->list[$name])){
            return $this->list[$name];
        }else{
            return null;
        }
    }
}