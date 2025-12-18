<?php

namespace EFive\Ws\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use EFive\Ws\WsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [WsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ws.builtin_routes.enabled', true);
        $app['config']->set('ws.builtin_routes.path', '/ws');
    }
}
