<?php
declare(strict_types=1);
namespace Ody\Swoole\Websockets;

use Swoole\Table;

// https://dev.to/robertobutti/websocket-with-php-4k2c
class Server
{
    private $server;

    public function start(bool $daemonize = false): void
    {
        $this->server->start();
    }

    public function createServer()
    {
        $fds = $this->createFdsTable();

        $this->server = new \Swoole\Websocket\Server("0.0.0.0", 9502);
        $this->server->on('open', function (\Swoole\WebSocket\Server $server, $request) use ($fds) {
            $fd = $request->fd;
            $clientName = sprintf("Client-%'.06d\n", $request->fd);
            $fds->set((string) $fd, [
                'fd' => $fd,
                'name' => sprintf($clientName)
            ]);
            echo "Connection <{$fd}> open by {$clientName}. Total connections: " . $fds->count() . "\n";
            foreach ($fds as $key => $value) {
                if ($key == $fd) {
                    $server->push((int) $request->fd, "Welcome {$clientName}, there are " . $fds->count() . " connections");
                } else {
                    $server->push((int) $key, "A new client ({$clientName}) is joining to the party");
                }
            }
        });

        $this->server->on('request', function ($request, $response) {
            var_dump($request);

            /*
             * Loop through all the WebSocket connections to
             * send back a response to all clients. Broadcast
             * a message back to every WebSocket client.
             *
             * https://openswoole.com/docs/modules/swoole-websocket-server-on-request
             */
            foreach($this->server->connections as $fd)
            {
                // Validate a correct WebSocket connection otherwise a push may fail
                if($this->server->isEstablished($fd))
                {
                    var_dump($fd);
                    $this->server->push($fd, 'from request');
                }
            }
        });

//        $this->server->on('handshake', function ($request, $response) {
//            $secWebSocketKey = $request->header['sec-websocket-key'];
//            $patten          = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
//            if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
//                $response->end();
//                return false;
//            }
//            echo $request->header['sec-websocket-key'] . PHP_EOL;
//            $key = base64_encode(sha1(
//                $request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
//                true
//            ));
//
//            $headers = [
//                'Upgrade'               => 'websocket',
//                'Connection'            => 'Upgrade',
//                'Sec-WebSocket-Accept'  => $key,
//                'Sec-WebSocket-Version' => '13',
//                'Set-Cookie' => 'BIDUPSID=C0B14AA47C188A3F0FF53676502EA0B7; expires=Thu, 31-Dec-37 23:55:55 GMT; max-age=2147483647; path=/; domain=localhost',
//                'X-asdf' => 'asdf'
//            ];
//
//            // WebSocket connection to 'ws://127.0.0.1:9502/'
//            // failed: Error during WebSocket handshake:
//            // Response must not include 'Sec-WebSocket-Protocol' header if not present in request: websocket
//            if (isset($request->header['sec-websocket-protocol'])) {
//                $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
//            }
//
//            foreach ($headers as $key => $val) {
//                $response->header($key, $val);
//            }
//
//            $response->status(101);
//            $response->end();
//        });

        $this->server->on('message', function (\Swoole\WebSocket\Server $server, $frame) use ($fds) {
            $sender = $fds->get(strval($frame->fd), "name");
            var_dump($sender);
            echo "Received from " . $sender . ", message: {$frame->data}" . PHP_EOL;
            foreach ($fds as $key => $value) {
                if ($key == $frame->fd) {
                    $server->push((int) $frame->fd, "Message sent");
                } else {
                    $server->push((int) $key,  "FROM: {$sender} - MESSAGE: " . $frame->data);
                }
            }
        });

        $this->server->on('close', function ($server, $fd) use ($fds) {
            $fds->del((string)$fd);
            echo "Connection close: {$fd}, total connections: " . $fds->count();
        });

        $this->server->on('Disconnect', function (\Swoole\WebSocket\Server $server, int $fd) use ($fds) {
            $fds->del((string)$fd);
            echo "Disconnect: {$fd}, total connections: " . $fds->count() . "\n";
        });


        return $this;
    }

    private function createFdsTable(): Table
    {
        $fds = new Table(1024);
        $fds->column('fd', Table::TYPE_INT, 4);
        $fds->column('name', Table::TYPE_STRING, 16);
        $fds->create();

        return $fds;
    }
}

(new Server())->createServer()->start();


