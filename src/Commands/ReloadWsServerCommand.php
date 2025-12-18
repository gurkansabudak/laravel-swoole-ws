<?php

namespace EFive\Ws\Commands;

use Illuminate\Console\Command;
use EFive\Ws\Support\PidFile;

final class ReloadWsServerCommand extends Command
{
    protected $signature = 'ws:reload';
    protected $description = 'Reload the Swoole WebSocket server workers';

    public function handle(): int
    {
        $pid = PidFile::read(config('ws.pid_file'));

        if (!$pid) {
            $this->warn('No pid file found.');
            return self::FAILURE;
        }

        if (!function_exists('posix_kill')) {
            $this->error('posix extension is required to reload by pid.');
            return self::FAILURE;
        }

        posix_kill($pid, SIGUSR1);
        $this->info("Reload signal sent (pid: {$pid}).");

        return self::SUCCESS;
    }
}
