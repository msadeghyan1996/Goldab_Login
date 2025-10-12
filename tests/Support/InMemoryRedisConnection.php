<?php

namespace Tests\Support;

class InMemoryRedisConnection
{
    /**
     * @var array<string, string>
     */
    private array $store = [];

    /**
     * @var array<string, int>
     */
    private array $expiry = [];

    public function setex(string $key, int $ttl, string $value): void
    {
        $this->store[$key] = $value;
        $this->expiry[$key] = time() + $ttl;
    }

    public function get(string $key): ?string
    {
        $this->purgeExpired($key);

        return $this->store[$key] ?? null;
    }

    /**
     * @param  array<int, string>|string  $keys
     */
    public function del($keys): void
    {
        foreach ((array) $keys as $key) {
            unset($this->store[$key], $this->expiry[$key]);
        }
    }

    public function incr(string $key): int
    {
        $this->purgeExpired($key);

        $current = (int) ($this->store[$key] ?? 0);
        $current++;

        $this->store[$key] = (string) $current;

        if (! isset($this->expiry[$key])) {
            $this->expiry[$key] = PHP_INT_MAX;
        }

        return $current;
    }

    public function ttl(string $key): int
    {
        $this->purgeExpired($key);

        if (! array_key_exists($key, $this->store)) {
            return -2;
        }

        if (! isset($this->expiry[$key]) || $this->expiry[$key] === PHP_INT_MAX) {
            return -1;
        }

        return max(0, $this->expiry[$key] - time());
    }

    public function expire(string $key, int $ttl): void
    {
        if (array_key_exists($key, $this->store)) {
            $this->expiry[$key] = time() + $ttl;
        }
    }

    private function purgeExpired(string $key): void
    {
        if (! isset($this->expiry[$key])) {
            return;
        }

        if ($this->expiry[$key] <= time()) {
            unset($this->store[$key], $this->expiry[$key]);
        }
    }
}
