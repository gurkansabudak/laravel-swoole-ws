<?php

namespace EFive\Ws\Messaging;

final class WsMessage
{
    public function __construct(
        public readonly string $path,
        public readonly string $action,
        public readonly array $data,
        public readonly array $meta,
    ) {}
}
