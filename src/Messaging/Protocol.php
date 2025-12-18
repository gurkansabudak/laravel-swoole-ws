<?php

namespace EFive\Ws\Messaging;

use EFive\Ws\Support\Json;

final class Protocol
{
    public static function decode(string $raw): WsMessage
    {
        $arr = Json::decodeArray($raw);

        return new WsMessage(
            path: (string) ($arr['path'] ?? '/'),
            action: (string) ($arr['action'] ?? ''),
            data: (array) ($arr['data'] ?? []),
            meta: (array) ($arr['meta'] ?? []),
        );
    }

    public static function encodeEvent(string $event, array $data = [], array $meta = []): string
    {
        return Json::encode([
            'event' => $event,
            'data' => $data,
            'meta' => $meta,
        ]);
    }
}
