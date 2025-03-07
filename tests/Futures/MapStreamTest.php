<?php declare(strict_types=1);

namespace Ody\Swoole\Tests\Futures;

use Swoole\Coroutine as Co;
use Ody\Swoole\Futures;
use PHPUnit\Framework\TestCase;

class MapStreamTest extends TestCase
{
    public function testMapStream()
    {
        Co\run(function () {
            Futures\stream()
                ->map(fn($val) => $val * $val)
                ->listen(fn($val) => $this->assertSame(4, $val))
                ->sink(2);
        });
    }
}
