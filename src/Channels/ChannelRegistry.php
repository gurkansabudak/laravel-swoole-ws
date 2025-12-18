<?php

namespace EFive\Ws\Channels;

final class ChannelRegistry
{
    /** @var ChannelDefinition[] */
    private array $channels = [];

    public function define(string $pattern, callable $authorizer): void
    {
        $this->channels[] = ChannelDefinition::make($pattern, $authorizer);
    }

    public function match(string $name): ?array
    {
        foreach ($this->channels as $def) {
            $params = $def->match($name);
            if ($params !== null) return [$def, $params];
        }
        return null;
    }
}
