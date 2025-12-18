<?php

namespace EFive\Ws\Stores;

use Swoole\Table;
use EFive\Ws\Contracts\ConnectionStore;

final class TableConnectionStore implements ConnectionStore
{
    private Table $fdToUser;
    private Table $roomMembers;

    public function __construct(int $size = 4096)
    {
        $this->fdToUser = new Table($size);
        $this->fdToUser->column('user_id', Table::TYPE_STRING, 64);
        $this->fdToUser->create();

        $this->roomMembers = new Table($size * 4);
        $this->roomMembers->column('room', Table::TYPE_STRING, 128);
        $this->roomMembers->column('fd', Table::TYPE_INT);
        $this->roomMembers->create();
    }

    public function bindUser(int $fd, int|string $userId): void
    {
        $this->fdToUser->set((string) $fd, ['user_id' => (string) $userId]);
    }

    public function userId(int $fd): int|string|null
    {
        $row = $this->fdToUser->get((string) $fd);
        return $row ? $row['user_id'] : null;
    }

    public function join(string $room, int $fd): void
    {
        $key = $this->rmKey($room, $fd);
        $this->roomMembers->set($key, ['room' => $room, 'fd' => $fd]);
    }

    public function leave(string $room, int $fd): void
    {
        $this->roomMembers->del($this->rmKey($room, $fd));
    }

    public function members(string $room): array
    {
        $fds = [];
        foreach ($this->roomMembers as $row) {
            if ($row['room'] === $room) $fds[] = (int) $row['fd'];
        }
        return $fds;
    }

    public function removeFd(int $fd): void
    {
        $this->fdToUser->del((string) $fd);
        // remove membership rows for this fd
        foreach ($this->roomMembers as $key => $row) {
            if ((int) $row['fd'] === $fd) $this->roomMembers->del($key);
        }
    }

    private function rmKey(string $room, int $fd): string
    {
        return sha1($room . ':' . $fd);
    }
}
