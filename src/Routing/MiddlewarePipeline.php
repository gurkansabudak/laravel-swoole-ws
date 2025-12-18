<?php

namespace EFive\Ws\Routing;

use Closure;
use Illuminate\Contracts\Container\Container;

final class MiddlewarePipeline
{
    public function __construct(private readonly Container $container) {}

    /**
     * @param array<int, string|callable> $middleware
     */
    public function handle(mixed $passable, array $middleware, Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($middleware),
            fn ($next, $mw) => fn ($p) => $this->callMiddleware($mw, $p, $next),
            $destination
        );

        return $pipeline($passable);
    }

    private function callMiddleware(string|callable $mw, mixed $passable, Closure $next): mixed
    {
        if (is_callable($mw)) {
            return $mw($passable, $next);
        }

        // supports "throttle:20,1" style
        [$name, $params] = array_pad(explode(':', $mw, 2), 2, '');
        $params = $params === '' ? [] : explode(',', $params);

        $aliases = (array) config('ws.middleware_aliases', []);
        if (isset($aliases[$name])) {
            $name = $aliases[$name];
        }

        $instance = $this->container->make($name);

        return $instance->handle($passable, $next, ...$params);
    }
}
