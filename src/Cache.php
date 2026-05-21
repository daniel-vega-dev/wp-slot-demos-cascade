<?php

namespace SlotDemosCascade;

class Cache
{
    public static function get(string $key)
    {
        return get_transient(self::prefix($key));
    }

    public static function set(string $key, $value, int $ttl): bool
    {
        return set_transient(self::prefix($key), $value, $ttl);
    }

    public static function delete(string $key): bool
    {
        return delete_transient(self::prefix($key));
    }

    private static function prefix(string $key): string
    {
        return 'jd_' . substr(md5($key), 0, 24);
    }
}
