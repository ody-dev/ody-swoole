<?php declare(strict_types=1);

namespace Ody\Swoole\Tests\Futures;

use PHPUnit\Framework\TestCase;
use Ody\Swoole\Futures;
use function Swoole\Coroutine\run;
use function Ody\Swoole\Futures\async;

class JoinFutureTest extends TestCase
{
    public function testAwait()
    {
        run(function () {
            $future = Futures\join([
                async(fn(): string => 'foo'),
                async(fn(): string => 'bar'),
                async(fn(): string => 'baz'),
            ]);

            $this->assertSame(['foo', 'bar', 'baz'], $future->await());
        });
    }
}
