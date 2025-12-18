<?php

namespace EFive\Ws\Messaging;

use EFive\Ws\Support\Json;

final class Protocol
{
    public static function decode(string $raw): WsMessage
    {
        $arr = Json::decodeArray($raw);

        $cmd = isset($arr['cmd']) ? (string) $arr['cmd'] : null;
        $ret = isset($arr['ret']) ? (string) $arr['ret'] : null;

        return new WsMessage(
            path: (string) ($arr['path'] ?? ''),
            action: (string) ($arr['action'] ?? ''),
            data: is_array($arr['data'] ?? null) ? $arr['data'] : [],
            meta: is_array($arr['meta'] ?? null) ? $arr['meta'] : [],
            cmd: $cmd !== '' ? $cmd : null,
            ret: $ret !== '' ? $ret : null,
            payload: $arr,
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

    public static function encodeResponse(mixed $payload, array $meta = []): string
    {
        return self::encodeEvent('ws.response', ['payload' => $payload], $meta);
    }

    public static function encodeError(string $code, array $meta = [], array $extra = []): string
    {
        return self::encodeEvent('ws.error', array_merge(['code' => $code], $extra), $meta);
    }

    public static function encodeCmd(string $cmd, array $payload = []): string
    {
        return Json::encode(array_merge(['cmd' => $cmd], $payload));
    }

    public static function encodeRet(string $ret, bool $result, array $payload = []): string
    {
        return Json::encode(array_merge(['ret' => $ret, 'result' => $result], $payload));
    }
}
