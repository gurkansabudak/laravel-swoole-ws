<?php

namespace EFive\Ws\Channels;

final class ChannelDefinition
{
    public function __construct(
        public readonly string $pattern,
        public readonly callable $authorizer
    ) {}

    public function match(string $channelName): ?array
    {
        // pattern: private-chat.{chatId}
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^.]+)', preg_quote($this->pattern, '/'));
        $regex = '/^' . str_replace('\.\(\?P', '.(?P', $regex) . '$/';

        if (!preg_match($regex, $channelName, $m)) return null;

        return array_filter($m, fn ($k) => is_string($k), ARRAY_FILTER_USE_KEY);
    }
}
