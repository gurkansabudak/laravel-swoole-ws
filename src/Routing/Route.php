<?php

namespace EFive\Ws\Routing;

use Closure;

final class Route
{
    public readonly string $path;
    public readonly string $action;
    public readonly ?string $cmd;
    public readonly ?string $ret;
    public readonly ?string $scope;

    /** @var Closure|array{0: class-string, 1: string}|string */
    public readonly Closure|array|string $handler;

    public function __construct(string $path, string $action, callable|array|string $handler, ?string $cmd = null, ?string $ret = null, ?string $scope = null,)
    {
        $this->path = $path;
        $this->action = $action;
        $this->cmd = $cmd;
        $this->ret = $ret;
        $this->scope = $scope;

        // Keep Laravel handler formats intact:
        // - "Controller@method" (string)
        // - [Controller::class, 'method'] (array)
        // - Closure
        if (is_string($handler) || is_array($handler) || $handler instanceof Closure) {
            $this->handler = $handler;
            return;
        }

        // For any other callable (rare), normalize to Closure
        $this->handler = Closure::fromCallable($handler);
    }

    private ?string $name = null;
    private array $middleware = [];

    public function name(string $name): self { $this->name = $name; return $this; }
    public function getName(): ?string { return $this->name; }

    public function middleware(array|string $middleware): self
    {
        $this->middleware = array_merge(
            $this->middleware,
            is_array($middleware) ? $middleware : [$middleware]
        );
        return $this;
    }

    public function getMiddleware(): array { return $this->middleware; }
}
