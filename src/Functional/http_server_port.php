<?php

namespace Ody\Swoole\Functional;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\Server\Port as ServerPort;

/**
 * Creates an HTTP server from a server port.
 *
 * @param Server $server
 * @param callable(Request, Response):mixed $handler
 * @param int $port
 * @param string $host
 *
 * @return ServerPort
 */
function http_server_port(Server $server, callable $handler, int $port = 80, string $host = '0.0.0.0'): ServerPort
{
    /**
     * @psalm-suppress UndefinedConstant
     * @var int $sock_type
     */
    $sock_type = SWOOLE_SOCK_TCP;

    /** @var ServerPort $server_port */
    $server_port = $server->addlistener($host, $port, $sock_type);
    $server_port->set(['open_http_protocol' => true]);
    $server_port->on('request', http_handler($handler));

    return $server_port;
}