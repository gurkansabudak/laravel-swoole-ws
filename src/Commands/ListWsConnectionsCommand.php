<?php

namespace EFive\Ws\Commands;

use EFive\Ws\Contracts\ConnectionStore;
use Illuminate\Console\Command;

final class ListWsConnectionsCommand extends Command
{
    protected $signature = 'ws:list
                            {--count : Output only the number of active connections}
                            {--json : Output JSON instead of a table}';

    protected $description = 'List active WebSocket connections (FDs)';

    public function handle(ConnectionStore $store): int
    {
        $fds = $store->allFds();

        if ($this->option('count')) {
            $this->line((string) count($fds));
            return self::SUCCESS;
        }

        if ($this->option('json')) {
            $out = [];
            foreach ($fds as $fd) {
                $out[] = $this->rowForFd($store, $fd);
            }
            $this->line(json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        if (empty($fds)) {
            $this->info('No active WebSocket connections.');
            return self::SUCCESS;
        }

        $rows = [];
        $i = 1;
        foreach ($fds as $fd) {
            $row = $this->rowForFd($store, $fd);
            $rows[] = [
                $i++,
                $row['fd'],
                $row['scope'],
                $row['user'],
                $row['connected'],
            ];
        }

        $this->info('Active WebSocket connections:');
        $this->newLine();
        $this->table(['#', 'FD', 'Scope', 'User', 'Connected'], $rows);
        $this->newLine();
        $this->info('Total: ' . count($fds));

        return self::SUCCESS;
    }

    /** @return array{fd:int,scope:string,user:string,connected:string,connected_at:int|null,last_seen_at:int|null} */
    private function rowForFd(ConnectionStore $store, int $fd): array
    {
        $scope = $store->handshakePath($fd) ?? '-';

        $uid = $store->userId($fd);
        $user = $uid === null || $uid === '' ? 'guest' : ('user#' . $uid);

        $connectedAt = $store->connectedAt($fd);
        $connected = $connectedAt ? $this->formatAge(time() - $connectedAt) : '-';

        return [
            'fd' => $fd,
            'scope' => $scope,
            'user' => $user,
            'connected' => $connected,
            'connected_at' => $connectedAt,
            'last_seen_at' => $store->lastSeenAt($fd),
        ];
    }

    private function formatAge(int $seconds): string
    {
        if ($seconds < 0) {
            $seconds = 0;
        }

        $minutes = intdiv($seconds, 60);
        $sec = $seconds % 60;

        if ($minutes < 60) {
            return sprintf('%dm %02ds', $minutes, $sec);
        }

        $hours = intdiv($minutes, 60);
        $min = $minutes % 60;

        if ($hours < 24) {
            return sprintf('%dh %02dm', $hours, $min);
        }

        $days = intdiv($hours, 24);
        $h = $hours % 24;
        return sprintf('%dd %02dh', $days, $h);
    }
}
