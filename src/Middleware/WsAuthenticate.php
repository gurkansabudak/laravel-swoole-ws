<?php

namespace EFive\Ws\Middleware;

use Closure;
use EFive\Ws\Messaging\WsContext;
use EFive\Ws\Channels\LaravelChannelAuthorizer;

final class WsAuthenticate
{
    public function handle(WsContext $ctx, Closure $next): mixed
    {
        // If already authenticated earlier (or in handshake), skip
        if ($ctx->user !== null) {
            return $next($ctx);
        }

        $tokenKey = (string) config('ws.auth.token_input_key', 'auth');

        // From message meta: meta.auth
        $token = null;
        if ($ctx->message?->meta && array_key_exists($tokenKey, $ctx->message->meta)) {
            $token = (string) $ctx->message->meta[$tokenKey];
        }

        // Allow auth token sent by handshake query (saved in store by onOpen)
        if (!$token) {
            $token = $ctx->store->handshakeToken($ctx->fd());
        }

        if (!$token) {
            $ctx->emit('ws.error', ['code' => 'UNAUTHENTICATED']);
            return null;
        }

        $resolver = config('ws.auth.resolver');
        if (is_callable($resolver)) {
            $user = $resolver($token);
        } else {
            $user = app(LaravelChannelAuthorizer::class)->resolveUserFromToken($token);
        }

        if (!$user) {
            $ctx->emit('ws.error', ['code' => 'UNAUTHENTICATED']);
            return null;
        }

        // Bind user for later use (rooms/presence/etc.)
        $ctx->store->bindUser($ctx->fd(), $user->id);

        return $next($ctx->withUser($user));
    }
}
