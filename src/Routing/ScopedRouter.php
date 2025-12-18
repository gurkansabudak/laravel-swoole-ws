<?php

namespace EFive\Ws\Routing;

final readonly class ScopedRouter
{
    public function __construct(private Router $router, private string $scope) {}

    public function command(string $cmd, callable|array|string $handler): Route
    {
        return $this->router->command($cmd, $handler, $this->scope);
    }

    public function response(string $ret, callable|array|string $handler): Route
    {
        return $this->router->response($ret, $handler, $this->scope);
    }
}
