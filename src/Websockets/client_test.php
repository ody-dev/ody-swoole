<?php
use Ody\Swoole\Websockets\Client;
use function Swoole\Coroutine\run;

require_once "../../vendor/autoload.php";

// https://github.com/lessmore92/swoole-websocket-client
run(function () {
    $webSocketClient = new \Ody\Swoole\Websockets\Client('ws://127.0.0.1:9502');
    $webSocketClient->push('{"event":"pusher:subscribe","data":{"auth":"fdsfsdfdsfdsfsdf","channel":"msgs"}}');
    $webSocketClient->recv();

    while ($webSocketClient->client->connected)
    {
        $data = $webSocketClient->recv();
        var_dump($data);
    }
});