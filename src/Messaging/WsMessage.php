<?php

namespace EFive\Ws\Messaging;

final readonly class WsMessage
{
    public function __construct(
        public string $path,
        public string $action,
        public array  $data,
        public array  $meta,
    ) {}
}
