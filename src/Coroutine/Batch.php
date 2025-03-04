<?php

namespace Ody\Swoole\Coroutine;

use Swoole\Coroutine;

class Batch
{
    /**
     * Execute multiple tasks concurrently and wait all tasks to be finished.
     *
     * @param array $tasks
     * @param float $timeout
     * @return array
     */
    public static function execute(array $tasks, float $timeout = -1): array
    {
        $wg = new WaitGroup(count($tasks));
        foreach ($tasks as $id => $task) {
            Coroutine::create(function () use ($wg, &$tasks, $id, $task) {
                $tasks[$id] = null;
                $tasks[$id] = $task();
                $wg->done();
            });
        }
        $wg->wait($timeout);
        return $tasks;
    }
}