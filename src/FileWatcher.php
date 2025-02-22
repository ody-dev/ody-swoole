<?php

namespace Ody\Swoole;

interface FileWatcher
{
    public function addFilePath(string $path): void;

    /**
     * @return string[]
     */
    public function readChanges(): array;
}