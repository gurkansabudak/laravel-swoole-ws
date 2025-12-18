<?php

namespace EFive\Ws\Builtin;

use EFive\Ws\Messaging\WsContext;
use EFive\Ws\Channels\LaravelChannelAuthorizer;

final class SubscriptionsController
{
    public function subscribe(WsContext $ctx, array $data, LaravelChannelAuthorizer $auth): array
    {
        $channel = (string) ($data['channel'] ?? '');
        if ($channel === '') {
            $ctx->emit('ws.error', ['code' => 'INVALID_CHANNEL']);
            return ['ok' => false];
        }

        $res = $auth->authorize($ctx->user, $channel);

        if (!$res['ok']) {
            $ctx->emit('ws.error', ['code' => $res['reason'] ?? 'FORBIDDEN']);
            return ['ok' => false];
        }

        $ctx->join($channel);

        // Presence info (if provided by authorizer)
        if (!empty($res['presence'])) {
            $ctx->emit('ws.presence.joined', [
                'channel' => $channel,
                'member' => $res['presence'],
            ]);
        }

        return ['ok' => true, 'channel' => $channel];
    }

    public function unsubscribe(WsContext $ctx, array $data): array
    {
        $channel = (string) ($data['channel'] ?? '');
        if ($channel === '') {
            $ctx->emit('ws.error', ['code' => 'INVALID_CHANNEL']);
            return ['ok' => false];
        }

        $ctx->leave($channel);

        return ['ok' => true, 'channel' => $channel];
    }
}
