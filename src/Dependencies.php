<?php

namespace Ody\Swoole;

use Composer\InstalledVersions;
use Ody\Core\Exception\PackageNotFoundException;

/**
 * @psalm-api
 */
class Dependencies
{
    public static function check($io)
    {
        if (!InstalledVersions::isInstalled('ody/swoole')) {
            $io->error('Missing dependencies. Please run `composer require ody/swoole` to install the missing dependencies!.' , true);

            return false;
        }

        if (!extension_loaded('swoole')){
            $io->error("The php-swoole extension is not installed! Please run `apt install php8.3-swoole`." , true);

            return false;
        }

        return true;
    }
}