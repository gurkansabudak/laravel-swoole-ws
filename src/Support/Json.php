<?php

namespace EFive\Ws\Support;

use JsonException;

final class Json
{
    /** @return array<string, mixed> */
    public static function decodeArray(string $json): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return [];
        }

        return is_array($data) ? $data : [];
    }

    /** @param mixed $data */
    public static function encode(mixed $data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return '{}';
        }
    }
}
