<?php

namespace Tests\Support;

use Illuminate\Contracts\Redis\Factory;

class InMemoryRedisFactory implements Factory
{
    private InMemoryRedisConnection $connection;

    public function __construct()
    {
        $this->connection = new InMemoryRedisConnection;
    }

    public function connection($name = null): InMemoryRedisConnection
    {
        return $this->connection;
    }
}
