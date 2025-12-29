<?php

namespace EFive\Ws\Stores;

use EFive\Ws\Contracts\ConnectionStore;

/**
 * Simple in-process store. Useful for development and single-worker servers.
 */
final class MemoryConnectionStore implements ConnectionStore
{
    /** @var array<int,true> */
    private array $fds = [];
    /** @var array<int,int|string> */
    private array $fdToUser = [];
    /** @var array<int,string> */
    private array $fdToToken = [];
    /** @var array<int,string> */
    private array $fdToPath = [];
    /** @var array<string,array<int,true>> */
    private array $roomMembers = [];
    /** @var array<string,array<int,true>> */
    private array $userFds = [];
    /** @var array<int,int> */
    private array $connectedAt = [];
    /** @var array<int,int> */
    private array $lastSeenAt = [];

    public function addFd(int $fd): void
    {
        $this->fds[$fd] = true;
        $now = time();
        $this->connectedAt[$fd] = $now;
        $this->lastSeenAt[$fd] = $now;
    }

    public function allFds(): array
    {
        $fds = array_keys($this->fds);
        sort($fds);
        return $fds;
    }

    public function clearAllFds(): void
    {
        $this->fds = [];
        $this->connectedAt = [];
        $this->lastSeenAt = [];
    }

    public function setConnectedAt(int $fd, int $unixSeconds): void
    {
        $this->connectedAt[$fd] = $unixSeconds;
        $this->fds[$fd] = true;
    }

    public function connectedAt(int $fd): ?int
    {
        return $this->connectedAt[$fd] ?? null;
    }

    public function touch(int $fd, ?int $unixSeconds = null): void
    {
        $this->lastSeenAt[$fd] = $unixSeconds ?? time();
        $this->fds[$fd] = true;
    }

    public function lastSeenAt(int $fd): ?int
    {
        return $this->lastSeenAt[$fd] ?? null;
    }

    public function bindUser(int $fd, int|string $userId): void
    {
        $this->addFd($fd);
        $this->fdToUser[$fd] = $userId;
        $this->userFds[(string) $userId][$fd] = true;
    }

    public function userId(int $fd): int|string|null
    {
        return $this->fdToUser[$fd] ?? null;
    }

    public function setHandshakeToken(int $fd, ?string $token): void
    {
        if ($token === null || $token === '') {
            unset($this->fdToToken[$fd]);
            return;
        }
        $this->fdToToken[$fd] = $token;
    }

    public function handshakeToken(int $fd): ?string
    {
        return $this->fdToToken[$fd] ?? null;
    }

    public function join(string $room, int $fd): void
    {
        $this->roomMembers[$room][$fd] = true;
    }

    public function leave(string $room, int $fd): void
    {
        unset($this->roomMembers[$room][$fd]);
        if (isset($this->roomMembers[$room]) && empty($this->roomMembers[$room])) {
            unset($this->roomMembers[$room]);
        }
    }

    public function members(string $room): array
    {
        return isset($this->roomMembers[$room]) ? array_keys($this->roomMembers[$room]) : [];
    }

    public function fdsForUser(int|string $userId): array
    {
        $k = (string) $userId;
        return isset($this->userFds[$k]) ? array_keys($this->userFds[$k]) : [];
    }

    public function removeFd(int $fd): void
    {
        unset($this->fds[$fd], $this->fdToToken[$fd], $this->fdToPath[$fd], $this->connectedAt[$fd], $this->lastSeenAt[$fd]);

        $uid = $this->fdToUser[$fd] ?? null;
        unset($this->fdToUser[$fd]);

        // rooms
        foreach ($this->roomMembers as $room => $members) {
            if (isset($members[$fd])) {
                unset($this->roomMembers[$room][$fd]);
                if (empty($this->roomMembers[$room])) {
                    unset($this->roomMembers[$room]);
                }
            }
        }

        // user->fds
        if ($uid !== null) {
            $k = (string) $uid;
            unset($this->userFds[$k][$fd]);
            if (isset($this->userFds[$k]) && empty($this->userFds[$k])) {
                unset($this->userFds[$k]);
            }
        }
    }

    public function setHandshakePath(int $fd, ?string $path): void
    {
        if ($path === null || $path === '') {
            unset($this->fdToPath[$fd]);
            return;
        }
        $this->fdToPath[$fd] = $path;
    }

    public function handshakePath(int $fd): ?string
    {
        return $this->fdToPath[$fd] ?? null;
    }
}
