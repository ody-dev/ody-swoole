<?php

use Ody\Swoole\Coroutine\ContextManager;
use Ody\Swoole\Http;

define('PROJECT_PATH' , realpath('./tests'));

class HttpTest extends \PHPUnit\Framework\TestCase
{
    public function testHttpClassInitialises()
    {
        $http = new Http();
        $this->assertInstanceOf(Http::class, $http);
    }
}