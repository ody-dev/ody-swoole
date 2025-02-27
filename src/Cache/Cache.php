<?php

namespace Ody\Swoole\Cache;


use DateInterval;
use InvalidArgumentException;
use Swoole\Table;
use Psr\SimpleCache\CacheInterface;

class Cache implements CacheInterface
{
    /**
     * Swoole table for cache
     *
     * @var Table
     */
    protected Table $table;

    /**
     * Constructor
     *
     * @param int $size the rows number of the table
     */
    public function __construct(int $size = 1024)
    {
        $this->table = new Table($size);
        $this->table->column('key', Table::TYPE_STRING, 64);
        $this->table->column('value', Table::TYPE_STRING, 64);
        $this->table->create();
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param string  $default Default value to return if the key does not exist.
     *
     * @return mixed value of the item from the cache, or $default in case of cache miss.
     *
     */
    public function get(string $key, mixed $default = null): string
    {
        $this->validateKey($key);
        $key = $this->table->get($key, 'value');

        if ($key == null) {
            return $default;
        }

        return $key;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key   The key of the item to store.
     * @param mixed                  $value The value of the item to store, must be serializable.
     * @param DateInterval|int|null $ttl   Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     */
    public function set(string $key, mixed $value, DateInterval|int $ttl = null): bool
    {
        $this->validateKey($key);
        return $this->table->set($key, ['key'=>$key,'value'=>$value]);
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     */
    public function delete(string $key):bool
    {
        $this->validateKey($key);
        return $this->table->del($key);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear() : bool
    {
        $flag = false;
        foreach ($this->table as $k => $item) {
            $flag = $this->table->del($k);
            if (!$flag) {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys    A list of keys that can obtained in a single operation.
     * @param mixed|null $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     *
     */
    public function getMultiple(iterable $keys, mixed $default = null) : iterable
    {
        $result = [];

        foreach ($keys as $k) {
            $this->validateKey($k);
            if ($v = $this->table->get($k, 'value')) {
                $result[$k] = $this->table->get($k, 'value');
            }
        }

        return $result;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param DateInterval|int|null $ttl    Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     */
    public function setMultiple(iterable $values, DateInterval|int $ttl = null) : bool
    {
        foreach ($values as $k => $v) {
            $flag = false;
            $this->validateKey($k);
            $flag = $this->table->set($k, ['key'=>$k,'value'=>$v]);
            if (!$flag) {
                return false;
            }
        }
        return true;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     */
    public function deleteMultiple(iterable $keys) : bool
    {
        foreach ($keys as $k) {
            $this->validateKey($k);
            $flag = false;
            $flag = $this->table->del($k);
            if (!$flag) {
                return false;
            }
        }
        return true;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     */
    public function has(string $key) : bool
    {
        $this->validateKey($key);
        return $this->table->exist($key);
    }

    /**
     * @param mixed $key
     */
    private function validateKey(mixed $key): void
    {
        var_dump($key);
        if (!is_string($key) || $key === '' || strpbrk($key, '{}()/\@:')) {
            throw new InvalidArgumentException('Invalid key value.');
        }
    }
}