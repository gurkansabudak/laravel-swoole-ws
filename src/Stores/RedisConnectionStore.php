<?php

namespace EFive\Ws\Stores;

use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use EFive\Ws\Contracts\ConnectionStore;

final class RedisConnectionStore implements ConnectionStore
{
    private Connection $redis;
    private string $prefix;
    private int $ttl;

    public function __construct(string $connection = 'default', string $prefix = 'ws:', int $ttlSeconds = 86400)
    {
        $this->redis = Redis::connection($connection);
        $this->prefix = $prefix;
        $this->ttl = $ttlSeconds;
    }

    public function bindUser(int $fd, int|string $userId): void
    {
        $this->redis->setex($this->k("fd:{$fd}:user"), $this->ttl, (string) $userId);
        $this->redis->sadd($this->k("user:{$userId}:fds"), $fd);
        $this->redis->expire($this->k("user:{$userId}:fds"), $this->ttl);
    }

    public function userId(int $fd): int|string|null
    {
        $v = $this->redis->get($this->k("fd:{$fd}:user"));
        return $v !== null ? (string) $v : null;
    }

    public function setHandshakeToken(int $fd, ?string $token): void
    {
        $key = $this->k("fd:{$fd}:token");
        if ($token === null || $token === '') {
            $this->redis->del($key);
            return;
        }
        $this->redis->setex($key, $this->ttl, $token);
    }

    public function handshakeToken(int $fd): ?string
    {
        $v = $this->redis->get($this->k("fd:{$fd}:token"));
        return $v !== null ? (string) $v : null;
    }

    public function join(string $room, int $fd): void
    {
        $this->redis->sadd($this->k("room:{$room}:fds"), $fd);
        $this->redis->expire($this->k("room:{$room}:fds"), $this->ttl);

        $this->redis->sadd($this->k("fd:{$fd}:rooms"), $room);
        $this->redis->expire($this->k("fd:{$fd}:rooms"), $this->ttl);
    }

    public function leave(string $room, int $fd): void
    {
        $this->redis->srem($this->k("room:{$room}:fds"), $fd);
        $this->redis->srem($this->k("fd:{$fd}:rooms"), $room);
    }

    public function members(string $room): array
    {
        $raw = $this->redis->smembers($this->k("room:{$room}:fds")) ?? [];
        return array_values(array_map('intval', $raw));
    }

    public function fdsForUser(int|string $userId): array
    {
        $raw = $this->redis->smembers($this->k("user:{$userId}:fds")) ?? [];
        return array_values(array_map('intval', $raw));
    }

    public function removeFd(int $fd): void
    {
        $userId = $this->userId($fd);

        // remove fd from rooms
        $rooms = $this->redis->smembers($this->k("fd:{$fd}:rooms")) ?? [];
        foreach ($rooms as $room) {
            $this->redis->srem($this->k("room:{$room}:fds"), $fd);
        }

        // remove from user fds
        if ($userId !== null) {
            $this->redis->srem($this->k("user:{$userId}:fds"), $fd);
        }

        // cleanup fd keys
        $this->redis->del(
            $this->k("fd:{$fd}:rooms"),
            $this->k("fd:{$fd}:user"),
            $this->k("fd:{$fd}:token"),
            $this->k("fd:{$fd}:path")
        );
    }

    private function k(string $suffix): string
    {
        return $this->prefix . $suffix;
    }

    public function setHandshakePath(int $fd, ?string $path): void
    {
        $key = $this->k("fd:{$fd}:path");

        if ($path === null || $path === '') {
            $this->redis->del($key);
            return;
        }

        $this->redis->setex($key, $this->ttl, $path);
    }

    public function handshakePath(int $fd): ?string
    {
        $value = $this->redis->get($this->k("fd:{$fd}:path"));
        return $value !== null ? (string) $value : null;
    }
}
