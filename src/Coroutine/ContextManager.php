<?php

namespace Ody\Swoole\Coroutine;

use InvalidArgumentException;
use Swoole\Coroutine;

/**
 * https://swoolelabs.com/blog/isolating-variables-with-coroutine-context
 */
class ContextManager
{
    public static function set(string $key, mixed $value): void
    {
        // Get the context object of the current coroutine
        $context = Coroutine::getContext();
        $context[$key] = $value;
    }

    /**
     * @psalm-api
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Get the current coroutine ID
        $cid = Coroutine::getCid();

        do
        {
            /*
             * Get the context object using the current coroutine
             * ID and check if our key exists, looping through the
             * coroutine tree if we are deep inside sub coroutines.
             */
            if(isset(Coroutine::getContext($cid)[$key]))
            {
                return Coroutine::getContext($cid)[$key];
            }

            // We may be inside a child coroutine, let's check the parent ID for a context
            $cid = Coroutine::getPcid($cid);

        } while ($cid !== -1 && $cid !== false);

        // The requested context variable and value could not be found
        return $default;
    }

    /**
     * @psalm-api
     */
    public function unset(string $key)
    {
        $context = Coroutine::getContext(Coroutine::getCid());
        $context[$key] = null;
    }
}