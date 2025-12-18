<?php /** @noinspection ALL */

namespace EFive\Ws\Messaging;

use EFive\Ws\Routing\Router;
use EFive\Ws\Routing\MiddlewarePipeline;
use EFive\Ws\Channels\LaravelChannelAuthorizer;
use EFive\Ws\Contracts\ConnectionStore;

final class MessageDispatcher
{
    public function __construct(
        private readonly Router $router,
        private readonly LaravelChannelAuthorizer $authorizer,
        private readonly ConnectionStore $store,
        private readonly array $globalMiddleware = [],
    ) {}

    public function dispatch(WsContext $ctx): void
    {
        $msg = Protocol::decode($ctx->frame->data);

        $route = $this->router->match($msg->path, $msg->action);
        if (!$route) {
            $ctx->emit('ws.error', ['code' => 'ROUTE_NOT_FOUND']);
            return;
        }

        $pipeline = new MiddlewarePipeline(app());

        $middleware = array_merge($this->globalMiddleware, $route->getMiddleware());

        $pipeline->handle($ctx->withMessage($msg), $middleware, function (WsContext $ctx2) use ($route) {
            $result = app()->call($route->handler, [
                'ctx' => $ctx2,
                'data' => $ctx2->message->data,
            ]);

            if ($result !== null) {
                $ctx2->respond($result);
            }
        });
    }
}
