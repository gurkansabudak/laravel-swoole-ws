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
    private Table $fdToPath;
    private Table $fdMeta;

    public function __construct(int $size = 4096)
    {
        $this->fdToUser = new Table($size);
        $this->fdToUser->column('user_id', Table::TYPE_STRING, 64);
        $this->fdToUser->create();

        $this->fdToToken = new Table($size);
        $this->fdToToken->column('token', Table::TYPE_STRING, 512);
        $this->fdToToken->create();

        $this->fdToPath = new Table($size);
        $this->fdToPath->column('path', Table::TYPE_STRING, 256);
        $this->fdToPath->create();

        $this->fdMeta = new Table($size);
        $this->fdMeta->column('connected_at', Table::TYPE_INT);
        $this->fdMeta->column('last_seen_at', Table::TYPE_INT);
        $this->fdMeta->column('meta_json', Table::TYPE_STRING, 2048);
        $this->fdMeta->create();

        $this->roomMembers = new Table($size * 4);
        $this->roomMembers->column('room', Table::TYPE_STRING, 128);
        $this->roomMembers->column('fd', Table::TYPE_INT);
        $this->roomMembers->create();

        $this->userFds = new Table($size * 2);
        $this->userFds->column('user_id', Table::TYPE_STRING, 64);
        $this->userFds->column('fd', Table::TYPE_INT);
        $this->userFds->create();
    }

    public function addFd(int $fd): void
    {
        $now = time();
        $this->fdMeta->set((string) $fd, ['connected_at' => $now, 'last_seen_at' => $now, 'meta_json' => '',]);
    }

    public function allFds(): array
    {
        $fds = [];
        foreach ($this->fdMeta as $key => $row) {
            $fds[] = (int) $key;
        }
        sort($fds);
        return $fds;
    }

    public function clearAllFds(): void
    {
        foreach ($this->fdMeta as $key => $row) {
            $this->fdMeta->del((string) $key);
        }
    }

    public function setConnectedAt(int $fd, int $unixSeconds): void
    {
        $key = (string) $fd;
        $row = $this->fdMeta->get($key) ?: [
            'connected_at' => $unixSeconds,
            'last_seen_at' => $unixSeconds,
            'meta_json' => '',
        ];
        $row['connected_at'] = $unixSeconds;
        $this->fdMeta->set($key, $row);
    }

    public function connectedAt(int $fd): ?int
    {
        $row = $this->fdMeta->get((string) $fd);
        return $row ? (int) $row['connected_at'] : null;
    }

    public function touch(int $fd, ?int $unixSeconds = null): void
    {
        $key = (string) $fd;
        $row = $this->fdMeta->get($key);
        if (! $row) {
            $this->addFd($fd);
            $row = $this->fdMeta->get($key);
        }

        $row['last_seen_at'] = $unixSeconds ?? time();
        $this->fdMeta->set($key, $row);
    }

    public function lastSeenAt(int $fd): ?int
    {
        $row = $this->fdMeta->get((string) $fd);
        return $row ? (int) $row['last_seen_at'] : null;
    }

    public function bindUser(int $fd, int|string $userId): void
    {
        $this->addFd($fd);
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

        $this->forgetMeta($fd);

        $this->fdToUser->del((string) $fd);
        $this->fdToToken->del((string) $fd);
        $this->fdToPath->del((string) $fd);
        $this->fdMeta->del((string) $fd);

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

    public function setHandshakePath(int $fd, ?string $path): void
    {
        $key = (string) $fd;

        if ($path === null || $path === '') {
            $this->fdToPath->del($key);
            return;
        }

        $this->fdToPath->set($key, ['path' => $path]);
    }

    public function handshakePath(int $fd): ?string
    {
        $row = $this->fdToPath->get((string) $fd);
        return $row ? (string) $row['path'] : null;
    }

    private function readMetaRow(int $fd): array
    {
        $row = $this->fdMeta->get((string) $fd);
        if (! $row) return [];
        $json = (string) ($row['meta_json'] ?? '');
        if ($json === '') return [];
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function writeMetaRow(int $fd, array $meta): void
    {
        // Ensure fdMeta exists (addFd if needed)
        $key = (string) $fd;
        $row = $this->fdMeta->get($key);
        if (! $row) {
            $this->addFd($fd);
            $row = $this->fdMeta->get($key) ?: ['connected_at' => time(), 'last_seen_at' => time(), 'meta_json' => ''];
        }

        $row['meta_json'] = empty($meta) ? '' : json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->fdMeta->set($key, $row);
    }

    private function normalizeMetaValue(string|int|float|bool|null $value): ?string
    {
        if ($value === null) return null;
        if (is_bool($value)) return $value ? '1' : '0';
        return (string) $value;
    }

    public function setMeta(int $fd, string $key, string|int|float|bool|null $value): void
    {
        $key = trim($key);
        if ($key === '') return;

        $meta = $this->readMetaRow($fd);

        $v = $this->normalizeMetaValue($value);
        if ($v === null) {
            unset($meta[$key]);
            $this->writeMetaRow($fd, $meta);
            return;
        }

        $meta[$key] = $v;
        $this->writeMetaRow($fd, $meta);
    }

    public function getMeta(int $fd, string $key, mixed $default = null): mixed
    {
        $meta = $this->readMetaRow($fd);
        return $meta[$key] ?? $default;
    }

    public function meta(int $fd): array
    {
        return $this->readMetaRow($fd);
    }

    public function forgetMeta(int $fd, ?string $key = null): void
    {
        if ($key === null) {
            $this->writeMetaRow($fd, []);
            return;
        }

        $meta = $this->readMetaRow($fd);
        unset($meta[$key]);
        $this->writeMetaRow($fd, $meta);
    }

    /** @return int[] */
    public function fdsWhereMeta(string $key, string|int|float|bool $value): array
    {
        $v = $this->normalizeMetaValue($value);
        if ($v === null) return [];

        $fds = [];
        foreach ($this->fdMeta as $fdKey => $row) {
            $fd = (int) $fdKey;
            $json = (string) ($row['meta_json'] ?? '');
            if ($json === '') continue;

            $meta = json_decode($json, true);
            if (! is_array($meta)) continue;

            if (isset($meta[$key]) && (string) $meta[$key] === $v) {
                $fds[] = $fd;
            }
        }

        sort($fds);
        return $fds;
    }
}
