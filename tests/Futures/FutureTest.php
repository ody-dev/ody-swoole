<?php declare(strict_types=1);

namespace Ody\Swoole\Tests\Futures;

use PHPUnit\Framework\TestCase;
use function Swoole\Coroutine\run;
use function Ody\Swoole\Futures\async;

class FutureTest extends TestCase
{
    public function testAwait()
    {
        run(function () {
            $future = async(fn(): string => 'foo');
            $this->assertSame('foo', $future->await());
        });
    }
}
