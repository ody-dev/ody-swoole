<?php

namespace Ody\Swoole;

use Swoole\Table;

class RateLimiter
{
    private Table $table;

    public function __construct()
    {
        // Initialize the Swoole Table
        $this->table = new Table(1024);
        $this->table->column('id', Table::TYPE_INT);
        $this->table->column('count', Table::TYPE_INT); // Counter for requests
        $this->table->column('timestamp', Table::TYPE_INT); // Last reset timestamp
        $this->table->create();
    }

    public function isRateLimited(string $serviceKey, string $identifier, int $limit, int $period): bool
    {
        $key = "{$serviceKey}:{$identifier}";
        $now = time();

        // Attempt to get the current count and timestamp
        $data = $this->table->get($key);
        if ($data === false || ($data['timestamp'] + $period) <= $now) {
            // If the key does not exist or the period has expired, reset the count and timestamp
            $this->table->set($key, ['count' => 1, 'timestamp' => $now]);
            return false; // Not rate limited
        } else {
            // If within the period, increment the count
            if ($data['count'] < $limit) {
                // Update the count atomically
                $this->table->incr($key, 'count');
                return false; // Not rate limited
            } else {
                return true; // Rate limited
            }
        }
    }
}