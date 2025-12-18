<?php

namespace EFive\Ws\Stores;

use Swoole\Table;
use EFive\Ws\Contracts\ConnectionStore;

final class TableConnectionStore implements ConnectionStore
{
    private Table $fdToUser;
    private Table $fdToToken;
    private Table $roomMembers;
    private Table $userFds;

    public function __construct(int $size = 4096)
    {
        $this->fdToUser = new Table($size);
        $this->fdToUser->column('user_id', Table::TYPE_STRING, 64);
        $this->fdToUser->create();

        $this->fdToToken = new Table($size);
        $this->fdToToken->column('token', Table::TYPE_STRING, 512);
        $this->fdToToken->create();

        $this->roomMembers = new Table($size * 4);
        $this->roomMembers->column('room', Table::TYPE_STRING, 128);
        $this->roomMembers->column('fd', Table::TYPE_INT);
        $this->roomMembers->create();

        $this->userFds = new Table($size * 2);
        $this->userFds->column('user_id', Table::TYPE_STRING, 64);
        $this->userFds->column('fd', Table::TYPE_INT);
        $this->userFds->create();
    }

    public function bindUser(int $fd, int|string $userId): void
    {
        $this->fdToUser->set((string) $fd, ['user_id' => (string) $userId]);
        $this->userFds->set($this->ufKey($userId, $fd), ['user_id' => (string) $userId, 'fd' => $fd]);
    }

    public function userId(int $fd): int|string|null
    {
        $row = $this->fdToUser->get((string) $fd);
        return $row ? $row['user_id'] : null;
    }

    public function setHandshakeToken(int $fd, ?string $token): void
    {
        $key = (string) $fd;

        if ($token === null || $token === '') {
            $this->fdToToken->del($key);
            return;
        }

        $this->fdToToken->set($key, ['token' => $token]);
    }

    public function handshakeToken(int $fd): ?string
    {
        $row = $this->fdToToken->get((string) $fd);
        return $row ? (string) $row['token'] : null;
    }

    public function join(string $room, int $fd): void
    {
        $this->roomMembers->set($this->rmKey($room, $fd), ['room' => $room, 'fd' => $fd]);
    }

    public function leave(string $room, int $fd): void
    {
        $this->roomMembers->del($this->rmKey($room, $fd));
    }

    public function members(string $room): array
    {
        $fds = [];
        foreach ($this->roomMembers as $row) {
            if ($row['room'] === $room) {
                $fds[] = (int) $row['fd'];
            }
        }
        return $fds;
    }

    public function fdsForUser(int|string $userId): array
    {
        $fds = [];
        foreach ($this->userFds as $row) {
            if ($row['user_id'] === (string) $userId) {
                $fds[] = (int) $row['fd'];
            }
        }
        return $fds;
    }

    public function removeFd(int $fd): void
    {
        $userId = $this->userId($fd);

        $this->fdToUser->del((string) $fd);
        $this->fdToToken->del((string) $fd);

        // remove membership rows for this fd
        foreach ($this->roomMembers as $key => $row) {
            if ((int) $row['fd'] === $fd) {
                $this->roomMembers->del($key);
            }
        }

        // remove from user->fds index
        if ($userId !== null) {
            $this->userFds->del($this->ufKey($userId, $fd));
        }
    }

    private function rmKey(string $room, int $fd): string
    {
        return sha1($room . ':' . $fd);
    }

    private function ufKey(int|string $userId, int $fd): string
    {
        return sha1((string) $userId . ':' . $fd);
    }
}
