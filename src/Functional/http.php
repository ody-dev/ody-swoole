<?php

namespace Ody\Swoole\Functional;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server as HttpServer;
use function Ody\Swoole\Functional\http_handler;

/**
 * Returns a Swoole HTTP server.
 *
 * @template T
 * @param callable(Request, Response): T $handler The callable to call on each request.
 * @param int $port The port binding (defaults to 9501).
 * @param string $host The host binding (defaults to 0.0.0.0).
 * @return HttpServer
 */
function http(callable $handler, int $port = 9501, string $host = '0.0.0.0'): HttpServer
{
    $server = new HttpServer($host, $port);
    $server->on('request', http_handler($handler));

    return $server;
}