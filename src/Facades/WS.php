<?php

namespace EFive\Ws\Facades;

use EFive\Ws\Routing\ScopedRouter;
use Illuminate\Support\Facades\Facade;

final class WS extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ws.router';
    }

    public static function command(string $cmd, callable|array|string $handler): \EFive\Ws\Routing\Route
    {
        return app('ws.router')->command($cmd, $handler);
    }

    public static function response(string $ret, callable|array|string $handler): \EFive\Ws\Routing\Route
    {
        return app('ws.router')->response($ret, $handler);
    }

    public static function scope(string $path): ScopedRouter
    {
        return new ScopedRouter(app('ws.router'), $path);
    }
}
