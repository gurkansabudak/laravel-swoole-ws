<?php

namespace EFive\Ws\Contracts;

interface ConnectionStore
{
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
}
