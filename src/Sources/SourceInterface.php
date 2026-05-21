<?php

namespace SlotDemosCascade\Sources;

interface SourceInterface
{
    public function id(): string;

    public function ttl(): int;

    /**
     * Resolve the launch URL for the given game config slice.
     * @param array $cfg  Source-specific config from games-map.json
     * @param string $locale  Two-letter locale, default es
     * @return array{ok:bool, url?:string, error?:string, latency_ms?:int}
     */
    public function resolve(array $cfg, string $locale = 'es'): array;
}
