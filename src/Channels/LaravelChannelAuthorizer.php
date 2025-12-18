<?php

namespace EFive\Ws\Channels;

use Illuminate\Support\Facades\Auth;

final class LaravelChannelAuthorizer
{
    public function __construct(
        private readonly ChannelRegistry $registry,
        private readonly string $guardName
    ) {}

    public function authorize(?object $user, string $channel): array
    {
        $match = $this->registry->match($channel);
        if (!$match) return ['ok' => false, 'reason' => 'CHANNEL_NOT_FOUND'];

        [$def, $params] = $match;

        if (!$user) return ['ok' => false, 'reason' => 'UNAUTHENTICATED'];

        $result = ($def->authorizer)($user, ...array_values($params));

        if ($result === true) return ['ok' => true, 'presence' => null];
        if (is_array($result)) return ['ok' => true, 'presence' => $result];

        return ['ok' => false, 'reason' => 'FORBIDDEN'];
    }

    public function resolveUserFromToken(?string $token): ?object
    {
        if (!$token) return null;

        // simplest approach: treat token as a bearer for your API guard
        // You can customize this to Sanctum/Passport/etc.
        return Auth::guard($this->guardName)->setToken($token)->user();
    }
}
