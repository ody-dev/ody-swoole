<?php
namespace Ody\Swoole\Functional;

use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as WebsocketServer;

/**
 * Returns a Swoole\WebSocket\Server.
 *
 * @template T
 *
 * @param callable(Frame, WebsocketServer): T $handler The handler to call on each message.
 * @param int $port The port binding (defaults to 9502).
 * @param string $host The host binding (defaults to 0.0.0.0).
 *
 * @return WebsocketServer
 *
 */
function websocket(callable $handler, int $port = 9502, string $host = '0.0.0.0'): WebsocketServer
{
    $server = new WebsocketServer($host, $port);
    Container\set(SWOOLE_WEBSOCKET_SERVER, $server);

    $server->on('open', static function (WebsocketServer $server, Request $request): void {
        /** @var callable|null $onopen */
        $onopen = Container\get(SWOOLE_WEBSOCKET_ONOPEN);

        if (is_callable($onopen)) {
            $onopen($request, $server);
        }
    });

    $server->on(
        'message',
        /**
         * @param WebsocketServer $server
         * @param Frame $frame
         *
         * @return mixed
         * @psalm-return T
         */
        static function (WebsocketServer $server, Frame $frame) use ($handler) {
            return $handler($frame, $server);
        }
    );

    $server->on('close', function (WebsocketServer $server, int $fd): void {
        /** @var callable|null $onclose */
        $onclose = Container\get(SWOOLE_WEBSOCKET_ONCLOSE);

        if (is_callable($onclose)) {
            $onclose($fd, $server);
        }
    });

    return $server;
}