<?php

namespace Ody\Swoole\Functional;

use Swoole\WebSocket\Server as WebsocketServer;
use function Ody\Swoole\Functional\push;
use const Ody\Swoole\Functional\SWOOLE_WEBSOCKET_SERVER;

/**
 *  Broadcasts a message to every websocket client.
 *
 * @param string $message
 */
function broadcast(string $message): void
{
    if (!Container\has(SWOOLE_WEBSOCKET_SERVER)) {
        throw new OutOfBoundsException('There is no server to broadcast.');
    }

    /** @var WebsocketServer $server */
    $server = Container\get(SWOOLE_WEBSOCKET_SERVER);

    /**
     * @psalm-suppress MissingPropertyType
     * @var int $fd
     */
    foreach ($server->connections as $fd) {
        push($message, $fd);
    }
}