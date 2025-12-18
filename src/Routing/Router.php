<?php

namespace EFive\Ws\Routing;

final class Router
{
    private RouteCollection $routes;
    private array $groupMiddleware = [];

    public function __construct()
    {
        $this->routes = new RouteCollection();
    }

    public function route(string $path, string $action, callable|array|string $handler): Route
    {
        $route = new Route($path, $action, $handler);
        $route->middleware($this->groupMiddleware);

        $this->routes->add($route);

        return $route;
    }

    public function middleware(array|string $middleware): self
    {
        $clone = clone $this;
        $clone->groupMiddleware = array_merge(
            $this->groupMiddleware,
            is_array($middleware) ? $middleware : [$middleware]
        );

        return $clone;
    }

    public function group(callable $callback): void
    {
        $callback();
    }

    public function match(string $path, string $action): ?Route
    {
        return $this->routes->match($path, $action);
    }

    // Channels API passthrough (optional convenience)
    public function channel(string $pattern, callable $authorizer): void
    {
        app(\EFive\Ws\Channels\ChannelRegistry::class)->define($pattern, $authorizer);
    }
}
