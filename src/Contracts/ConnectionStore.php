<?php

namespace EFive\Ws\Contracts;

interface ConnectionStore
{
    public function bindUser(int $fd, int|string $userId): void;
    public function userId(int $fd): int|string|null;

    public function join(string $room, int $fd): void;
    public function leave(string $room, int $fd): void;
    /** @return int[] */
    public function members(string $room): array;

    public function removeFd(int $fd): void;
}
