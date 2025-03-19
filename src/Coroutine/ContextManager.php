<?php

namespace Ody\Swoole\Coroutine;

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

    /**
     * Clear all context variables for the current coroutine
     *
     * @return void
     */
    public static function clear(): void
    {
        $cid = Coroutine::getCid();
        if ($cid !== -1 && $cid !== false) {
            $context = Coroutine::getContext($cid);

            // Reset all keys in the context
            foreach ($context as $key => $value) {
                $context[$key] = null;
                unset($context[$key]);
            }
        }
    }

    /**
     * Delete a specific key from the current coroutine context
     *
     * @param string $key The key to delete
     * @return void
     */
    public static function delete(string $key): void
    {
        $context = Coroutine::getContext();
        unset($context[$key]);
    }

    /**
     * Get all keys in the current coroutine context
     *
     * @return array List of all keys
     */
    public static function getAllKeys(): array
    {
        $context = Coroutine::getContext();
        return array_keys((array)$context);
    }
}