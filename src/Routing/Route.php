<?php

namespace EFive\Ws\Routing;

final class Route
{
    public function __construct(
        public readonly string $path,
        public readonly string $action,
        public readonly callable|array|string $handler
    ) {}

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
