<?php

namespace SlotDemosCascade\Sources;

/**
 * Source #0 (highest priority) — DirectSource.
 * Returns a hardcoded URL configured per-game in games-map.json.
 * Useful for games where we know a direct provider URL that's stable
 * (e.g. demogamesfree.pragmaticplay.net for Pragmatic Play).
 *
 * Verifies via HEAD request that the host is alive — but doesn't validate
 * game content (we trust the URL was correct when we configured it).
 */
class DirectSource implements SourceInterface
{
    public function id(): string
    {
        return 'direct';
    }

    public function ttl(): int
    {
        return 7 * 24 * 3600; // 7 days — direct URLs rarely change
    }

    public function resolve(array $cfg, string $locale = 'es'): array
    {
        $url = $cfg['url'] ?? null;
        if (!$url) {
            return ['ok' => false, 'error' => 'no_url_configured'];
        }

        // Optional locale rewrite if URL contains lang= or locale= param
        if (!empty($cfg['rewrite_locale'])) {
            $url = preg_replace('/\b(?:lang|locale)=[a-z_]+/i', 'lang=' . $locale, $url);
        }

        $start = microtime(true);
        // Some demo CDNs reject HEAD with 405 → try GET with Range to read 1 byte
        $resp = wp_remote_get($url, [
            'timeout'     => 10,
            'redirection' => 3,
            'headers'     => [
                'Range'      => 'bytes=0-1023',
                'User-Agent' => 'Mozilla/5.0 (compatible; SobreJuegosBot/1.0)',
            ],
        ]);
        $latency = (int) round((microtime(true) - $start) * 1000);

        // DirectSource is trust-based: URL was verified working at config time.
        // We HEAD-check just to detect host outage (DNS failure, complete unreachability).
        // Status codes 403/405/501 mean host is alive but blocks our bot — URL still works
        // in real browsers. Only treat hard network errors and 5xx (except 501) as fail.
        if (is_wp_error($resp)) {
            $err = $resp->get_error_code();
            // DNS / connection refused / timeout — real outage
            return ['ok' => false, 'error' => 'http_' . $err, 'latency_ms' => $latency];
        }
        $code = (int) wp_remote_retrieve_response_code($resp);
        // Acceptable: anything that proves the host is responding
        $acceptable = [200, 206, 301, 302, 303, 307, 308, 401, 403, 405, 410, 451, 501];
        if (!in_array($code, $acceptable, true)) {
            return ['ok' => false, 'error' => 'status_' . $code, 'latency_ms' => $latency];
        }
        return ['ok' => true, 'url' => $url, 'latency_ms' => $latency];
    }
}
