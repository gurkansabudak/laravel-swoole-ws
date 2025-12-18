<?php

namespace EFive\Ws;

use Illuminate\Support\ServiceProvider;
use EFive\Ws\Routing\Router;
use EFive\Ws\Channels\ChannelRegistry;
use EFive\Ws\Channels\LaravelChannelAuthorizer;
use EFive\Ws\Messaging\MessageDispatcher;
use EFive\Ws\Server\WebSocketKernel;
use EFive\Ws\Server\ServerFactory;
use EFive\Ws\Stores\MemoryConnectionStore;
use EFive\Ws\Stores\TableConnectionStore;

final class WsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ws.php', 'ws');

        $this->app->singleton('ws.router', fn () => new Router());
        $this->app->singleton(ChannelRegistry::class, fn () => new ChannelRegistry());

        $this->app->singleton(LaravelChannelAuthorizer::class, function ($app) {
            return new LaravelChannelAuthorizer(
                registry: $app->make(ChannelRegistry::class),
                guardName: config('ws.auth.guard', 'api')
            );
        });

        $this->app->singleton(\EFive\Ws\Contracts\ConnectionStore::class, function () {
            return match (config('ws.store.driver', 'table')) {
                'memory' => new MemoryConnectionStore(),
                default  => new TableConnectionStore(config('ws.store.table.size', 4096)),
            };
        });

        $this->app->singleton(MessageDispatcher::class, function ($app) {
            return new MessageDispatcher(
                router: $app->make('ws.router'),
                authorizer: $app->make(LaravelChannelAuthorizer::class),
                store: $app->make(\EFive\Ws\Contracts\ConnectionStore::class),
                globalMiddleware: config('ws.middleware', []),
            );
        });

        $this->app->singleton(WebSocketKernel::class, function ($app) {
            return new WebSocketKernel(
                dispatcher: $app->make(MessageDispatcher::class),
                store: $app->make(\EFive\Ws\Contracts\ConnectionStore::class)
            );
        });

        $this->app->singleton(ServerFactory::class, fn ($app) => new ServerFactory($app->make(WebSocketKernel::class)));
    }

    public function boot(): void
    {
        $this->publishes([__DIR__ . '/../config/ws.php' => config_path('ws.php')], 'ws-config');
        $this->publishes([__DIR__ . '/../routes/ws.php' => base_path('routes/ws.php')], 'ws-routes');
        $this->publishes([__DIR__ . '/../routes/ws_channels.php' => base_path('routes/ws_channels.php')], 'ws-channels');

        $this->loadWsRoutes();
        $this->loadWsChannels();

        if ($this->app->runningInConsole()) {
            $this->commands([
                \EFive\Ws\Commands\StartWsServerCommand::class,
                \EFive\Ws\Commands\StopWsServerCommand::class,
                \EFive\Ws\Commands\ReloadWsServerCommand::class,
                \EFive\Ws\Commands\StatusWsServerCommand::class,
            ]);
        }
    }

    private function loadWsRoutes(): void
    {
        $file = config('ws.routes_file');
        if (is_string($file) && file_exists($file)) {
            require $file;
        }
    }

    private function loadWsChannels(): void
    {
        $file = config('ws.channels_file');
        if (is_string($file) && file_exists($file)) {
            require $file;
        }
    }
}
