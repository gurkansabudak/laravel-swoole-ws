<?php

namespace EFive\Ws\Messaging;

use EFive\Ws\Support\Json;

final class Protocol
{
    public static function decode(string $raw): WsMessage
    {
        $arr = Json::decodeArray($raw);

        return new WsMessage(
            path: (string)($arr['path'] ?? '/'),
            action: (string)($arr['action'] ?? ''),
            data: is_array($arr['data'] ?? null) ? $arr['data'] : [],
            meta: is_array($arr['meta'] ?? null) ? $arr['meta'] : [],
        );
    }

    public static function encode(string $event, array $data = [], array $meta = []): string
    {
        return Json::encode([
            'event' => $event,
            'data' => $data,
            'meta' => $meta,
        ]);
    }
}
