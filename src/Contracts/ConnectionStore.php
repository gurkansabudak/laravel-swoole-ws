<?php

namespace EFive\Ws\Contracts;

interface ConnectionStore
{
    /** Register a newly connected fd (used for listing connections). */
    public function addFd(int $fd): void;

    /** @return int[] */
    public function allFds(): array;

    public function clearAllFds(): void;

    /** Store connection time in unix seconds (used for ws:list). */
    public function setConnectedAt(int $fd, int $unixSeconds): void;
    public function connectedAt(int $fd): ?int;

    /** Update last-seen time in unix seconds (used for ws:list). */
    public function touch(int $fd, ?int $unixSeconds = null): void;
    public function lastSeenAt(int $fd): ?int;

    public function bindUser(int $fd, int|string $userId): void;
    public function userId(int $fd): int|string|null;

    /** Store handshake token (optional). */
    public function setHandshakeToken(int $fd, ?string $token): void;
    public function handshakeToken(int $fd): ?string;

    public function join(string $room, int $fd): void;
    public function leave(string $room, int $fd): void;

    /** @return int[] */
    public function members(string $room): array;

    /** @return int[] */
    public function fdsForUser(int|string $userId): array;

    public function removeFd(int $fd): void;

    public function setHandshakePath(int $fd, ?string $path): void;

    public function handshakePath(int $fd): ?string;

    /** Set a single metadata key for an fd (stringable values). */
    public function setMeta(int $fd, string $key, string|int|float|bool|null $value): void;

    /** Get a single metadata key for an fd. */
    public function getMeta(int $fd, string $key, mixed $default = null): mixed;

    /** Get all metadata for an fd. */
    public function meta(int $fd): array;

    /** Remove one key or all metadata for an fd. */
    public function forgetMeta(int $fd, ?string $key = null): void;

    /**
     * Find fds by exact match on meta key/value.
     * @return int[]
     */
    public function fdsWhereMeta(string $key, string|int|float|bool $value): array;
}
