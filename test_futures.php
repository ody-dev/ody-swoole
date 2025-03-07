<?php

require_once __DIR__ . '/vendor/autoload.php';

use Ody\Futures;
use Swoole\Coroutine;

Coroutine::create(function () {
    $m = Futures\async(function () {
        sleep(3);
        echo "done\n";

        return 1;
    });

    $n = Futures\async(function () {
        sleep(5);
        echo "done\n";

        return 2;
    });

    $o = Futures\async(function () {
        sleep(1);
        echo "done\n";

        return 3;
    });

    $x = Futures\join([$m, $n, $o]);
    print_r($x->await());
});
