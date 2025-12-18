<?php

namespace EFive\Ws\Server;

use Swoole\WebSocket\Server;

final class ServerFactory
{
    public function __construct(private readonly WebSocketKernel $kernel) {}

    public function make(): Server
    {
        if (!class_exists(Server::class)) {
            throw new \RuntimeException('Swoole is not installed/enabled.');
        }

        $server = new Server(config('ws.host'), config('ws.port'));

        $server->set(config('ws.server', []));

        $server->on('Open', [$this->kernel, 'onOpen']);
        $server->on('Message', [$this->kernel, 'onMessage']);
        $server->on('Close', [$this->kernel, 'onClose']);

        return $server;
    }
}
