<?php

namespace EFive\Ws\Commands;

use Illuminate\Console\Command;
use EFive\Ws\Support\PidFile;

final class StatusWsServerCommand extends Command
{
    protected $signature = 'ws:status';
    protected $description = 'Show WS server status';

    public function handle(): int
    {
        $pid = PidFile::read(config('ws.pid_file'));

        if (!$pid) {
            $this->line('WS server: stopped');
            return self::SUCCESS;
        }

        $running = function_exists('posix_kill') ? @posix_kill($pid, 0) : null;

        $this->line('WS server pid: ' . $pid);
        $this->line('WS server running: ' . ($running ? 'yes' : 'unknown'));

        return self::SUCCESS;
    }
}
