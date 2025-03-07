<?php

declare(strict_types=1);

namespace Ody\Swoole\Tests\Declarative;

class SwooleHttpRequestMock
{
    public $server;
    public $header;

    public function __construct(string $method, string $uri, array $header = [])
    {
        $this->server = [
            'request_method' => $method,
            'request_uri' => $uri
        ];

        $this->header = $header;
    }
}
