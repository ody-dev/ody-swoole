<?php

namespace Ody\Swoole\Functions;

use Swoole\Http\Server as HttpServer;
use Swoole\Http2\Request;
use Swoole\Http2\Response;
use function Ody\Swoole\Functional\http_handler;

/**
 * Returns a Swoole HTTP/2 server.
 *
 * @template T
 * @param string $certFile Path to the certificate file.
 * @param string $keyFile Path to the key file.
 * @param callable(Request, Response): T $handler The callable to call on each request.
 * @param int $port The port binding (defaults to 9501).
 * @param string $host The host binding (defaults to 0.0.0.0).
 * @return HttpServer
 */
function http2(string $certFile, string $keyFile, callable $handler, int $port = 9501, string $host = '0.0.0.0'): HttpServer
{
    $server = new HttpServer($host, $port, SWOOLE_BASE, SWOOLE_SOCK_TCP | SWOOLE_SSL);
    $server->set(['ssl_cert_file' => $certFile, 'ssl_key_file' => $keyFile]);
    $server->on('request', http_handler($handler));

    return $server;
}