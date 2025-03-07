<?php declare(strict_types=1);

namespace Ody\Swoole\Tests\Futures;

use Swoole\Coroutine as Co;
use Ody\Swoole\Futures;
use PHPUnit\Framework\TestCase;

class ThenFutureTest extends TestCase
{
    public function testAwait()
    {
        Co\run(function () {
            $future = Futures\async(fn() => 2)
                ->then(fn(int $i) => Futures\async(fn() => $i + 3))
                ->then(fn(int $i) => Futures\async(fn() => $i * 4))
                ->then(fn(int $i) => Futures\async(fn() => $i - 5));

            $this->assertSame(15, $future->await());
        });
    }
}
