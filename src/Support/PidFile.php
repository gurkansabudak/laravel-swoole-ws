<?php

namespace EFive\Ws\Support;

final class PidFile
{
    public static function write(string $path, int $pid): void
    {
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, (string) $pid);
    }

    public static function read(string $path): ?int
    {
        if (!file_exists($path)) return null;
        $pid = (int) trim((string) file_get_contents($path));
        return $pid > 0 ? $pid : null;
    }

    public static function delete(string $path): void
    {
        if (file_exists($path)) @unlink($path);
    }
}
