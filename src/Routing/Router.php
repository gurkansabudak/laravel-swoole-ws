<?php

namespace EFive\Ws\Routing;

final class Router
{
    private RouteCollection $routes;
    private array $groupMiddleware = [];

    /** @var array<string, array<string, Route>> */
    private array $commands = [];

    /** @var array<string, array<string, Route>> */
    private array $responses = [];

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

    public function command(string $cmd, callable|array|string $handler, string $scope = '/'): Route
    {
        $route = new Route(path: '', action: '', handler: $handler, cmd: $cmd, ret: null, scope: $scope);
        $this->commands[$scope][$cmd] = $route;
        return $route;
    }

    public function response(string $ret, callable|array|string $handler, string $scope = '/'): Route
    {
        $route = new Route(path: '', action: '', handler: $handler, cmd: null, ret: $ret, scope: $scope);
        $this->responses[$scope][$ret] = $route;
        return $route;
    }

    public function matchCommand(string $scope, string $cmd): ?Route
    {
        return $this->commands[$scope][$cmd] ?? $this->commands['/'][$cmd] ?? null;
    }

    public function matchResponse(string $scope, string $ret): ?Route
    {
        return $this->responses[$scope][$ret] ?? $this->responses['/'][$ret] ?? null;
    }

}
