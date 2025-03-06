<?php

namespace Ody\Swoole\Server;

class ServerType
{
    public const HTTP_SERVER = \Swoole\Http\Server::class;

    public const WS_SERVER = \Swoole\WebSocket\Server::class;

    public const SCHEDULER = 'scheduler';
}