<?php

namespace EFive\Ws\Channels;

use Closure;

final readonly class ChannelDefinition
{
    public function __construct(
        public string $pattern,
        public Closure $authorizer,
    ) {}
    public static function make(string $pattern, callable $authorizer): self
    {
        return new self(
            $pattern,
            $authorizer instanceof Closure ? $authorizer : $authorizer(...)
        );
    }

    public function match(string $channelName): ?array
    {
        // pattern: private-chat.{chatId}
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^.]+)', preg_quote($this->pattern, '/'));
        $regex = '/^' . str_replace('\.\(\?P', '.(?P', $regex) . '$/';

        if (!preg_match($regex, $channelName, $m)) return null;

        return array_filter($m, fn ($k) => is_string($k), ARRAY_FILTER_USE_KEY);
    }
}
