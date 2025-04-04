<?php

namespace Ody\Swoole\HotReload;

use Ody\Logger\StreamLogger;
use Ody\Server\State\HttpServerState;

/**
 * @psalm-api
 */
class Watcher
{
    protected array $paths;

    protected $logger;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
        $this->logger = new StreamLogger('php://stdout');
    }

    public function start(): void
    {
        $serverState = HttpServerState::getInstance();
        while (
            !$serverState->httpServerIsRunning()
        ) {
            sleep(2);
        }

        while (
            $serverState->httpServerIsRunning()
        ) {
            foreach ($this->paths as $path) {
                $this->check(base_path($path));
            }
            sleep(2);
        }
    }

    protected function check(string $dir): void
    {
        static $last_mtime;

        if (!$last_mtime) {
            $last_mtime = time();
        }

        clearstatcache();

        if (!is_dir($dir)) {
            if (!is_file($dir)) {
                return;
            }
            $iterator = [new \SplFileInfo($dir)];
        } else {
            $dir_iterator = new \RecursiveDirectoryIterator(
                $dir,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS
            );
            $iterator = new \RecursiveIteratorIterator($dir_iterator);
        }

        foreach ($iterator as $file) {
            if (is_dir($file)) {
                continue;
            }

            if ($last_mtime < $file->getMTime()) {
                $var = 0;
                exec('"'.PHP_BINARY . '" -l ' . $file, $out, $var);
                $last_mtime = $file->getMTime();
                if ($var) {
                    continue;
                }

                $serverState = HttpServerState::getInstance();
                if ($serverState->httpServerIsRunning()) {
                    posix_kill($serverState->getManagerProcessId(), SIGUSR1);
                    posix_kill($serverState->getMasterProcessId(), SIGUSR1);
                }

                $this->logger->info("{$file->getFilename()} has been changed, server reloaded.");

                break;
            }
        }
    }
}