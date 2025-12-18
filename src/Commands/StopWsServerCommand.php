<?php

namespace EFive\Ws\Commands;

use Illuminate\Console\Command;
use EFive\Ws\Support\PidFile;

final class StopWsServerCommand extends Command
{
    protected $signature = 'ws:stop';
    protected $description = 'Stop the Swoole WebSocket server';

    public function handle(): int
    {
        $pidFile = config('ws.pid_file');
        $pid = PidFile::read($pidFile);

        if (!$pid) {
            $this->warn('No pid file found.');
            return self::FAILURE;
        }

        if (!function_exists('posix_kill')) {
            $this->error('posix extension is required to stop by pid.');
            return self::FAILURE;
        }

        posix_kill($pid, SIGTERM);
        PidFile::delete($pidFile);

        $this->info("Stopped WS server (pid: {$pid}).");
        return self::SUCCESS;
    }
}
