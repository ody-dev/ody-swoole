<?php

namespace Ody\Swoole;

use Ody\Core\App;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequestFactory
{
    public static function createRequestCallback(App $app): RequestCallback
    {
        return RequestCallback::fromCallable(
            static fn (ServerRequestInterface $request): ResponseInterface => $app->handle($request)
        );
    }
}