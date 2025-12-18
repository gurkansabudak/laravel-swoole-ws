<?php

namespace EFive\Ws\Messaging;

use EFive\Ws\Routing\Router;
use EFive\Ws\Routing\MiddlewarePipeline;
use EFive\Ws\Channels\LaravelChannelAuthorizer;
use EFive\Ws\Contracts\ConnectionStore;

final class MessageDispatcher
{
    public function __construct(
        private readonly Router                   $router,
        private readonly LaravelChannelAuthorizer $authorizer,
        private readonly ConnectionStore          $store,
        private readonly array                    $globalMiddleware = [],
    )
    {
    }

    public function dispatch(WsContext $ctx): void
    {
        $msg = Protocol::decode((string)$ctx->frame->data);

        $scope = $ctx->store->handshakePath($ctx->fd()) ?? '/';

        // 1) ret routes
        if (!empty($msg->ret)) {
            $route = $this->router->matchResponse($scope, $msg->ret);
            if (!$route) {
                $ctx->emit('ws.error', ['code' => 'ROUTE_NOT_FOUND', 'type' => 'ret', 'ret' => $msg->ret]);
                return;
            }

            $this->runRoute($ctx, $msg, $route, $msg->payload);
            return;
        }

        // 2) cmd routes
        if (!empty($msg->cmd)) {
            $route = $this->router->matchCommand($scope, $msg->cmd);
            if (!$route) {
                $ctx->emit('ws.error', ['code' => 'ROUTE_NOT_FOUND', 'type' => 'cmd', 'cmd' => $msg->cmd, 'scope' => $scope]);
                return;
            }

            $this->runRoute($ctx, $msg, $route, $msg->payload);
            return;
        }

        // 3) legacy route(path/action)
        $route = $this->router->match($msg->path, $msg->action);
        if (!$route) {
            $ctx->emit('ws.error', ['code' => 'ROUTE_NOT_FOUND', 'type' => 'route', 'path' => $msg->path, 'action' => $msg->action]);
            return;
        }

        $this->runRoute($ctx, $msg, $route, $msg->data);
    }

    private function runRoute(WsContext $ctx, WsMessage $msg, $route, array $data): void
    {
        $pipeline = new MiddlewarePipeline(app());

        $middleware = array_merge($this->globalMiddleware, $route->getMiddleware());

        $pipeline->handle($ctx->withMessage($msg), $middleware, function (WsContext $ctx2) use ($route, $data) {
            $result = app()->call($route->handler, [
                'ctx' => $ctx2,
                'data' => $data,
            ]);

            if ($result !== null) {
                $ctx2->respond($result);
            }
        });
    }
}
