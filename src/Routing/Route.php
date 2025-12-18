<?php

namespace EFive\Ws\Routing;

use Closure;

final class Route
{
    public readonly string $path;
    public readonly string $action;

    /** @var Closure|string */
    public readonly Closure|string $handler;

    public function __construct(string $path, string $action, callable|array|string $handler)
    {
        $this->path = $path;
        $this->action = $action;

        // Allow "Controller@method" string to pass through
        if (is_string($handler)) {
            $this->handler = $handler;
            return;
        }

        // Normalize callable/array to Closure for safe property typing
        $this->handler = $handler instanceof Closure
            ? $handler
            : Closure::fromCallable($handler);
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
