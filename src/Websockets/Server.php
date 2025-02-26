<?php
declare(strict_types=1);
namespace Ody\Swoole\Websockets;

use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\Websocket\Server as WsServer;
use Swoole\Http\Request;
use Swoole\Table;

// https://dev.to/robertobutti/websocket-with-php-4k2c
class Server
{
    private WsServer $server;

    public function __construct()
    {
        $this->server = new WsServer(
            config("websocket.host") ?: '0.0.0.0',
            config("websocket.port") ?: '9502',
        );

        // Create an in memory table to track
        // active connections
        $this->createFdsTable();
    }

    public static function init(): self
    {
        return new self();
    }

    public function start(bool $daemonize = false): void
    {
        $this->server->start();
    }

    public function createServer()
    {
        $this->server->on('request', [$this, 'onRequest']);
        $this->server->on('handshake', [$this, 'onHandshake']);
        $this->server->on('message', [$this, 'onMessage']);
        $this->server->on('close', [$this, 'onClose']);
        $this->server->on('disconnect', [$this, 'onDisconnect']);

        return $this;
    }

    /*
     * Loop through all the WebSocket connections to
     * send back a response to all clients. Broadcast
     * a message back to every WebSocket client.
     *
     * https://openswoole.com/docs/modules/swoole-websocket-server-on-request
     */
    public function onRequest(Request $request): void
    {
        foreach($this->server->connections as $fd)
        {
            // Validate a correct WebSocket connection otherwise a push may fail
            if($this->server->isEstablished($fd))
            {
                $clientName = sprintf("Client-%'.06d\n", $fd);
                echo "Pushing event to $clientName...\n";
                $this->server->push($fd, 'From request to clients');
            }
        }
    }

    public function onHandshake(Request $request, Response $response): bool
    {
        $this->validateHandshake($request, $response);

        \Swoole\Event::defer(function () use ($request, $response) {
            echo "Client connected\n";
            $this->onOpen($request, $response);
        });

        return true;
    }

    public function onOpen(Request $request, Response $response)
    {
        $fd = $request->fd;
        $clientName = sprintf("Client-%'.06d\n", $request->fd);
        $this->fds->set((string) $fd, [
            'fd' => $fd,
            'name' => sprintf($clientName)
        ]);
        echo "Connection <{$fd}> open by {$clientName}. Total connections: " . $this->fds->count() . "\n";
    }

    public function onClose(WsServer $server, $fd) {
        $this->fds->del((string) $fd);
        echo "Connection close: {$fd}, total connections: " . $this->fds->count();
    }

    public function onDisconnect(WsServer $server, int $fd) {
        $this->fds->del((string)$fd);
        echo "Disconnect: {$fd}, total connections: " . $this->fds->count() . "\n";
    }

    public function onMessage (WsServer $server, Frame $frame): void
    {
        $sender = $this->fds->get(strval($frame->fd), "name");
        echo "Received from " . $sender . ", message: {$frame->data}" . PHP_EOL;
    }


    private function createFdsTable(): void
    {
        $fds = new Table(1024);
        $fds->column('fd', Table::TYPE_INT, 4);
        $fds->column('name', Table::TYPE_STRING, 16);
        $fds->create();

        $this->fds = $fds;
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

Server::init()
    ->createServer()
    ->start();


