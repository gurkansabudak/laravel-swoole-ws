<?php

namespace EFive\Ws\Routing;

final class RouteCollection
{
    /** @var Route[] */
    private array $routes = [];

    public function add(Route $route): void
    {
        $key = $this->key($route->path, $route->action);
        $this->routes[$key] = $route;
    }

    public function match(string $path, string $action): ?Route
    {
        return $this->routes[$this->key($path, $action)] ?? null;
    }

    private function key(string $path, string $action): string
    {
        return $path . '::' . $action;
    }
}
