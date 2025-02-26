<?php
declare(strict_types=1);
namespace Ody\Swoole\Websocket;

use AllowDynamicProperties;

class Server
{
    private $server;

    public function start(bool $daemonize = false): void
    {
        $this->server->start();
    }

    public function createServer()
    {
        $this->server = new \Swoole\Websocket\Server("0.0.0.0", 9502);
        $this->server->on('open', function (\Swoole\WebSocket\Server $server, $request) {
            echo "server: open success with fd{$request->fd}\n";
        });

        $this->server->on('handshake', function ($request, $response) {
            $secWebSocketKey = $request->header['sec-websocket-key'];
            $patten          = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
            if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
                $response->end();
                return false;
            }
            echo $request->header['sec-websocket-key'] . PHP_EOL;
            echo $request->header['Authorization'] . PHP_EOL;
            $key = base64_encode(sha1(
                $request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
                true
            ));

            $headers = [
                'Upgrade'               => 'websocket',
                'Connection'            => 'Upgrade',
                'Sec-WebSocket-Accept'  => $key,
                'Sec-WebSocket-Version' => '13',
                'Set-Cookie' => 'BIDUPSID=C0B14AA47C188A3F0FF53676502EA0B7; expires=Thu, 31-Dec-37 23:55:55 GMT; max-age=2147483647; path=/; domain=localhost',
                'X-asdf' => 'asdf'
            ];

            // WebSocket connection to 'ws://127.0.0.1:9502/'
            // failed: Error during WebSocket handshake:
            // Response must not include 'Sec-WebSocket-Protocol' header if not present in request: websocket
            if (isset($request->header['sec-websocket-protocol'])) {
                $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
            }

            foreach ($headers as $key => $val) {
                $response->header($key, $val);
            }

            $response->status(101);
            $response->end();
        });

        $this->server->on('message', function (\Swoole\WebSocket\Server $server, $frame) {
            echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
            $server->push($frame->fd, "this is server");
        });

        $this->server->on('close', function ($server, $fd) {
            echo "client {$fd} closed\n";
        });

        return $this;
    }
}

(new Server())->createServer()->start();


