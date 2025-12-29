<?php

namespace EFive\Ws\Commands;

use EFive\Ws\Contracts\ConnectionStore;
use Illuminate\Console\Command;
use EFive\Ws\Server\ServerFactory;
use EFive\Ws\Support\PidFile;

final class StartWsServerCommand extends Command
{
    protected $signature = 'ws:start {--d|daemon : Run as daemon}';
    protected $description = 'Start the Swoole WebSocket server';

    public function handle(ServerFactory $factory): int
    {
        app(ConnectionStore::class)->clearAllFds();

        $server = $factory->make();

        if ($this->option('daemon')) {
            $settings = config('ws.server', []);
            $settings['daemonize'] = 1;
            $server->set($settings);
        }

        $pidFile = config('ws.pid_file');
        $server->on('Start', function () use ($pidFile) {
            PidFile::write($pidFile, getmypid());
        });

        $this->info('WS server starting on ' . config('ws.host') . ':' . config('ws.port'));
        $server->start();

        return self::SUCCESS;
    }
}
