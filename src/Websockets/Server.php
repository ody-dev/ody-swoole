<?php
declare(strict_types=1);
namespace Ody\Swoole\Websockets;

use Ody\Swoole\Coroutine\ContextManager;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\Websocket\Server as WsServer;
use Swoole\Http\Request;
use Swoole\Table;

// https://dev.to/robertobutti/websocket-with-php-4k2c
class Server
{
    private static WsServer $server;

    public static function init(): self
    {
        return new self();
    }

    public function start(bool $daemonize = false): void
    {
        static::$server->start();
    }

    public function createServer(string $host = null, int $port = null): static
    {
        static::$server = new WsServer(
            $host ?: config('websockets.host'),
            $port ?: config('websockets.port'),
        );

        $this->createFdsTable();

        $callbacks = config('websockets.callbacks');
        foreach ($callbacks as $event => $callback) {
            static::$server->on($event, [$callback[0], $callback[1]]);
        }

        return $this;
    }

    /*
     * Loop through all the WebSocket connections to
     * send back a response to all clients. Broadcast
     * a message back to every WebSocket client.
     *
     * https://openswoole.com/docs/modules/swoole-websocket-server-on-request
     */
    public static function onRequest(Request $request,  Response $response): void
    {
        // Handle incoming requests
        // TODO: Implement routes

        echo "Received request:\n";
        foreach(static::$server->connections as $fd)
        {
            // Validate a correct WebSocket connection otherwise a push may fail
            if(static::$server->isEstablished($fd))
            {
                $clientName = sprintf("Client-%'.06d\n", $fd);
                echo "Pushing event to $clientName...\n";
                static::$server->push($fd, json_encode(['key' => 'value']));
            }
        }
    }

    public static function onHandshake(Request $request, Response $response): bool
    {
        echo "onHandshake \n";
        $key = $request->header['sec-websocket-key'] ?? '';

        if (!preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $key)) {
            echo "Handshake failed (1)\n";
            $response->end();
            return false;
        }

        if (strlen(base64_decode($key)) !== 16) {
            $response->end();
            echo "Handshake failed (2)\n";
            return false;
        }

        $response->header('Upgrade', 'websocket');
        $response->header('Connection', 'Upgrade');
        $response->header(
            'Sec-WebSocket-Accept',
            base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true))
        );
        $response->header('Sec-WebSocket-Version', '13');

        $protocol = $request->header['sec-websocket-protocol'] ?? null;

        if ($protocol !== null) {
            $response->header('Sec-WebSocket-Protocol', $protocol);
        }

        $response->status(101);
        $response->end();
        echo "Handshake done\n";

        \Swoole\Event::defer(function () use ($request, $response) {
            echo "Client connected\n";
            self::onOpen($request, $response);
        });

        return true;
    }

    public static function onOpen(Request $request, Response $response): void
    {
        $fd = $request->fd;
        $clientName = sprintf("Client-%'.06d\n", $request->fd);
        static::$server->table->set((string) $fd, [
            'fd' => $fd,
            'name' => sprintf($clientName)
        ]);
        echo "Connection <{$fd}> open by {$clientName}. Total connections: " . static::$server->table->count() . "\n";
    }

    public static function onClose(WsServer $server, $fd): void
    {
        static::$server->table->del((string) $fd);
        echo "Connection close: {$fd}, total connections: " . static::$server->table->count();
    }

    public static function onDisconnect(WsServer $server, int $fd): void
    {
        static::$server->table->del((string) $fd);
        echo "Disconnect: {$fd}, total connections: " . static::$server->table->count() . "\n";
    }

    public static function onMessage (WsServer $server, Frame $frame): void
    {
        echo "onMessage";
        $sender = static::$server->table->get(strval($frame->fd), "name");
        echo "Received from " . $sender . ", message: {$frame->data}" . PHP_EOL;
    }


    private function createFdsTable(): void
    {
        $fds = new Table(1024);
        $fds->column('fd', Table::TYPE_INT, 4);
        $fds->column('name', Table::TYPE_STRING, 16);
        $fds->create();

        static::$server->table = $fds;
    }

    private function validateHandshake($request, $response): void
    {
        $key = $request->header['sec-websocket-key'] ?? '';

        if (!preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $key)) {
            echo "Handshake failed (1)\n";
            $response->end();
            return;
        }

        if (strlen(base64_decode($key)) !== 16) {
            $response->end();
            echo "Handshake failed (2)\n";
            return;
        }

        $response->header('Upgrade', 'websocket');
        $response->header('Connection', 'Upgrade');
        $response->header(
            'Sec-WebSocket-Accept',
            base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true))
        );
        $response->header('Sec-WebSocket-Version', '13');

        $protocol = $request->header['sec-websocket-protocol'] ?? null;

        if ($protocol !== null) {
            $response->header('Sec-WebSocket-Protocol', $protocol);
        }

        $response->status(101);
        $response->end();
        echo "Handshake done\n";
    }
}