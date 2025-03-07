<?php
require_once __DIR__ . '/vendor/autoload.php';

use Ody\Swoole\Declarative\Response;
use Ody\Swoole\Declarative\Route;
use Ody\Swoole\Declarative\Swoole;

$handler = function () {
    Route\get('/', fn() => Response\json('Hello, World!'));
    Route\get('/test', fn() => Response\json('test!'));
};

$port = 8000;
echo "Listening on port $port\n";
Swoole\http($handler, $port)->start();