<?php

namespace Ody\Swoole\Functional;

/**
 *  Attach hooks for Swoole WebSocket server events.
 *  `open` => Called when a client connects to the server.
 *  `close` => Called when a client disconnects from the server.
 *
 * @param array $hooks The hooks to be attached.
 *
 * @return void
 */
function websocket_hooks(array $hooks): void
{
    if (array_key_exists('open', $hooks)) {
        Container\set(SWOOLE_WEBSOCKET_ONOPEN, $hooks['open']);
    }

    if (array_key_exists('close', $hooks)) {
        Container\set(SWOOLE_WEBSOCKET_ONCLOSE, $hooks['close']);
    }
}