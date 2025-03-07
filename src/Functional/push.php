<?php

namespace Ody\Swoole\Functional;

use OutOfBoundsException;
use Swoole\WebSocket\Server as WebsocketServer;
use const Ody\Swoole\Functional\SWOOLE_WEBSOCKET_SERVER;

/**
 * Pushes a message to a specific websocket client.
 *
 * @param string $message
 * @param int $fd
 *
 * @return void
 */
function push(string $message, int $fd): void
{
    if (!Container\has(SWOOLE_WEBSOCKET_SERVER)) {
        throw new OutOfBoundsException('There is no server to push.');
    }

    /** @var WebsocketServer $server */
    $server = Container\get(SWOOLE_WEBSOCKET_SERVER);
    $server->push($fd, $message);
}