<?php

namespace EFive\Ws\Server;

use Swoole\WebSocket\Server;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use EFive\Ws\Messaging\MessageDispatcher;
use EFive\Ws\Messaging\WsContext;
use EFive\Ws\Contracts\ConnectionStore;
use EFive\Ws\Channels\LaravelChannelAuthorizer;

final class WebSocketKernel
{
    public function __construct(
        private readonly MessageDispatcher $dispatcher,
        private readonly ConnectionStore $store,
    ) {}

    public function onOpen(Server $server, Request $request): void
    {
        $fd = (int) $request->fd;
        $key = (string) config('ws.auth.handshake_query_key', 'token');

        $token = null;
        if (isset($request->get) && is_array($request->get) && isset($request->get[$key])) {
            $token = (string) $request->get[$key];
        }

        $this->store->setHandshakeToken($fd, $token);
    }

    public function onMessage(Server $server, Frame $frame): void
    {
        $ctx = new WsContext($server, $frame, $this->store);

        // Example: resolve auth token from message meta
        // (you can also implement ws.auth middleware)
        $this->dispatcher->dispatch($ctx);
    }

    public function onClose(Server $server, int $fd): void
    {
        $this->store->removeFd($fd);
    }
}
