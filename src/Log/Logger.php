<?php

namespace Ody\Swoole\Log;

use Carbon\Carbon;
use Exception;
use Swoole\Http\Request;

class Logger
{
    protected string $info = "   \033[1mINFO\033[0m ";

    final public function __construct() {}

    /**
     * @throws Exception
     * string $type
     * $request
     */
    public static function logRequestToConsole(string $type, Request $request): void
    {
        $logger = (new static());
        $url = $logger->getUrl($request);
        $method = $logger->getMethod($request);
        $time = $logger->getTimestamp($request);

        $type = match($type) {
            "info" => $logger->info(),
            default => throw new Exception("{$type} not implemented"),
        };

        echo "{$type} {$time} {$method} {$url}\n";
    }

    private function info(): string
    {
        return $this->info;
    }

    private function getUrl(Request $request): string
    {
        return "{$request->server['remote_addr']}:{$request->server['server_port']}{$request->server['request_uri']}";
    }

    private function getMethod(Request $request): string
    {
        return "\033[1m{$request->getMethod()}\033[0m";
    }

    private function getTimestamp(Request $request): string
    {
        return Carbon::parse($request->server['request_time_float'])->toString();
    }
}