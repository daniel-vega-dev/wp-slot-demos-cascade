<?php

namespace SlotDemosCascade\Sources;

/**
 * WazdanDirect — direct Wazdan demo CDN.
 * Auto-generated from /tmp/new-aggregators.json on 2026-05-07.
 */
class WazdanDirect implements SourceInterface
{
    public function id(): string { return 'wazdan'; }
    public function ttl(): int { return 604800; }

    public function resolve(array $cfg, string $locale = 'es'): array
    {
        $required = ['id'];
        foreach ($required as $f) {
            if (empty($cfg[$f])) return ['ok' => false, 'error' => 'missing_' . $f];
        }
        $url = 'https://gamelaunch.wazdan.com/demo-demo/gamelauncher?lang={locale}&platform=desktop&mode=demo&license=wazdan&game={id}';
        $url = str_replace('{locale}', rawurlencode($locale), $url);
        foreach ($required as $f) {
            $url = str_replace('{' . $f . '}', rawurlencode((string) $cfg[$f]), $url);
        }
        $start = microtime(true);
        $resp = wp_remote_get($url, [
            'timeout' => 10, 'redirection' => 3,
            'headers' => [
                'Range' => 'bytes=0-1023',
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
                'Sec-Fetch-Dest' => 'iframe',  // some providers (Wazdan) need this to not redirect
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'cross-site',
            ],
        ]);
        $latency = (int) round((microtime(true) - $start) * 1000);
        if (is_wp_error($resp)) return ['ok' => false, 'error' => 'http_' . $resp->get_error_code(), 'latency_ms' => $latency];
        $code = (int) wp_remote_retrieve_response_code($resp);
        // Accept any host-alive response — actual game error pages return 200, browser will load and play
        $accept = [200, 206, 301, 302, 303, 307, 308, 401, 403, 405];
        if (!in_array($code, $accept, true)) return ['ok' => false, 'error' => 'status_' . $code, 'latency_ms' => $latency];
        return ['ok' => true, 'url' => $url, 'latency_ms' => $latency];
    }
}
