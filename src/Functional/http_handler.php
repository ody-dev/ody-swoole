<?php

namespace Ody\Swoole\Functional;

use Closure;
use Swoole\Http\Request;
use Swoole\Http\Response;
use const Ody\Swoole\Functional\SWOOLE_HTTP_REQUEST;
use const Ody\Swoole\Functional\SWOOLE_HTTP_REQUEST_ENDED;
use const Ody\Swoole\Functional\SWOOLE_HTTP_RESPONSE;
use const Siler\Route\DID_MATCH;

/**
 * @template T
 * @param callable(Request, Response): T $handler
 * @return Closure(Request, Response): T
 */
function http_handler(callable $handler): Closure
{
    return
        /**
         * @param Request $request
         * @param Response $response
         * @return mixed
         * @psalm-return T
         */
        function (Request $request, Response $response) use ($handler) {
            Container\set(DID_MATCH, false);
            Container\set(SWOOLE_HTTP_REQUEST_ENDED, false);
            Container\set(SWOOLE_HTTP_REQUEST, $request);
            Container\set(SWOOLE_HTTP_RESPONSE, $response);

            /**
             * @var mixed
             * @psalm-var T
             */
            return $handler($request, $response);
        };
}