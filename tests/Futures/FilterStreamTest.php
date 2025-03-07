<?php declare(strict_types=1);

namespace Ody\Swoole\Tests\Futures;

use Swoole\Coroutine as Co;
use Ody\Swoole\Futures;
use PHPUnit\Framework\TestCase;

class FilterStreamTest extends TestCase
{
    public function testFilterStream()
    {
        Co\run(function () {
            Futures\stream()
                ->filter(fn($val) => $val % 2 === 0)
                ->listen(fn($val) => $this->assertSame(2, $val))
                ->sink(1)
                ->sink(2);
        });
    }
}
