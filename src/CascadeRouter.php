<?php

namespace SlotDemosCascade;

class CascadeRouter
{
    /** @var Sources\SourceInterface[] */
    private array $sources;

    public function __construct()
    {
        $this->sources = [
            new Sources\DirectSource(),          // #0 — pre-configured direct provider URL
            // Direct provider CDNs (per-provider — auto-built URL from id/slug)
            new Sources\HacksawDirect(),
            new Sources\NolimitDirect(),
            new Sources\RelaxCdnDirect(),
            new Sources\YggdrasilDirect(),
            new Sources\QuickspinDirect(),
            new Sources\WazdanDirect(),
            new Sources\ThunderkickDirect(),
            new Sources\EndorphinaDirect(),
            new Sources\StakelogicDirect(),
            new Sources\PushGamingDirect(),
        ];
    }

    public function gamesMap(): array
    {
        $path = SLOT_DEMOS_CASCADE_DIR . 'data/games-map.json';
        $raw = is_readable($path) ? file_get_contents($path) : '{}';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Return list of source IDs configured for the given game (skip _comment keys).
     */
    public function configuredSources(string $game): array
    {
        $map = $this->gamesMap();
        $cfg = $map[$game] ?? null;
        if (!is_array($cfg)) return [];
        $out = [];
        foreach ($this->sources as $source) {
            $sid = $source->id();
            $key = $this->cfgKeyForSource($sid);
            if (isset($cfg[$key])) {
                $out[] = $sid;
            }
        }
        return $out;
    }

    /**
     * Resolve URL for game with cascade fallback.
     * @param string $prefer  If set, try ONLY this source (no cascade).
     * @return array{ok:bool, url?:string, source?:string, attempts:array, available_sources:array}
     */
    public function launch(string $game, string $locale = 'es', array $blacklist = [], string $prefer = ''): array
    {
        $map = $this->gamesMap();
        $cfg = $map[$game] ?? null;
        $attempts = [];
        $available = $this->configuredSources($game);

        if (!$cfg) {
            return ['ok' => false, 'attempts' => [], 'error' => 'game_not_mapped', 'available_sources' => []];
        }

        // Prefer mode — try only that source (no cascade), but still cache normally.
        if ($prefer !== '') {
            foreach ($this->sources as $source) {
                if ($source->id() !== $prefer) continue;
                $cfgKey = $this->cfgKeyForSource($source->id());
                if (!isset($cfg[$cfgKey])) {
                    return ['ok' => false, 'error' => 'source_not_configured', 'attempts' => [], 'available_sources' => $available];
                }
                // Cache key includes config hash → auto-invalidates when games-map.json changes
                $cfgHash = substr(md5(json_encode($cfg[$cfgKey])), 0, 8);
                $cacheKey = sprintf('launch:%s:%s:%s:%s', $source->id(), $game, $locale, $cfgHash);
                $cached = Cache::get($cacheKey);
                if (is_array($cached) && !empty($cached['url'])) {
                    return ['ok' => true, 'url' => $cached['url'], 'source' => $source->id(),
                            'attempts' => [['source' => $source->id(), 'ok' => true, 'cached' => true]],
                            'available_sources' => $available];
                }
                $res = $source->resolve($cfg[$cfgKey], $locale);
                Logger::launch([
                    'event' => 'launch_prefer', 'game' => $game, 'source' => $source->id(),
                    'status' => !empty($res['ok']) ? 'ok' : 'fail',
                    'error' => $res['error'] ?? null, 'latency_ms' => $res['latency_ms'] ?? null,
                ]);
                if (!empty($res['ok']) && !empty($res['url'])) {
                    Cache::set($cacheKey, ['url' => $res['url']], $source->ttl());
                    return ['ok' => true, 'url' => $res['url'], 'source' => $source->id(),
                            'attempts' => [array_merge(['source' => $source->id()], $res)],
                            'available_sources' => $available];
                }
                return ['ok' => false, 'error' => $res['error'] ?? 'source_failed',
                        'attempts' => [array_merge(['source' => $source->id()], $res)],
                        'available_sources' => $available];
            }
            return ['ok' => false, 'error' => 'unknown_source', 'attempts' => [], 'available_sources' => $available];
        }

        foreach ($this->sources as $source) {
            $sid = $source->id();
            if (in_array($sid, $blacklist, true)) {
                continue;
            }
            $cfgKey = $this->cfgKeyForSource($sid);
            if (!isset($cfg[$cfgKey])) {
                $attempts[] = ['source' => $sid, 'ok' => false, 'error' => 'no_config'];
                continue;
            }

            // Cache key includes config hash → auto-invalidates when games-map.json changes
            $cfgHash = substr(md5(json_encode($cfg[$cfgKey])), 0, 8);
            $cacheKey = sprintf('launch:%s:%s:%s:%s', $sid, $game, $locale, $cfgHash);
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && !empty($cached['url'])) {
                $attempts[] = ['source' => $sid, 'ok' => true, 'cached' => true, 'latency_ms' => 0];
                Logger::launch([
                    'event' => 'launch', 'game' => $game, 'source' => $sid,
                    'status' => 'ok', 'cached' => true, 'url' => $this->shortUrl($cached['url']),
                ]);
                return ['ok' => true, 'url' => $cached['url'], 'source' => $sid,
                        'attempts' => $attempts, 'available_sources' => $available];
            }

            $res = $source->resolve($cfg[$cfgKey], $locale);
            $attempts[] = array_merge(['source' => $sid], $res);

            Logger::launch([
                'event'      => 'launch',
                'game'       => $game,
                'source'     => $sid,
                'status'     => !empty($res['ok']) ? 'ok' : 'fail',
                'error'      => $res['error'] ?? null,
                'latency_ms' => $res['latency_ms'] ?? null,
                'url'        => !empty($res['ok']) ? $this->shortUrl($res['url']) : null,
            ]);

            if (!empty($res['ok']) && !empty($res['url'])) {
                Cache::set($cacheKey, ['url' => $res['url']], $source->ttl());
                return ['ok' => true, 'url' => $res['url'], 'source' => $sid,
                        'attempts' => $attempts, 'available_sources' => $available];
            }
        }

        return ['ok' => false, 'attempts' => $attempts, 'error' => 'all_sources_failed',
                'available_sources' => $available];
    }

    /**
     * Health-check a single game across all configured sources without using cache.
     * @return array<int, array<string,mixed>>
     */
    public function checkAll(string $game, string $locale = 'es'): array
    {
        $map = $this->gamesMap();
        $cfg = $map[$game] ?? null;
        $out = [];
        if (!$cfg) {
            return $out;
        }
        foreach ($this->sources as $source) {
            $sid = $source->id();
            $cfgKey = $this->cfgKeyForSource($sid);
            if (!isset($cfg[$cfgKey])) {
                $out[] = ['source' => $sid, 'ok' => false, 'error' => 'no_config'];
                continue;
            }
            $res = $source->resolve($cfg[$cfgKey], $locale);
            $out[] = array_merge(['source' => $sid], $res);
        }
        return $out;
    }

    private function cfgKeyForSource(string $id): string
    {
        return [
            'direct'       => 'direct',
            'hacksaw'      => 'hacksaw',
            'nolimit'      => 'nolimit',
            'relax-cdn'    => 'relax_cdn',
            'yggdrasil'    => 'yggdrasil',
            'quickspin'    => 'quickspin',
            'wazdan'       => 'wazdan',
            'thunderkick'  => 'thunderkick',
            'endorphina'   => 'endorphina',
            'stakelogic'   => 'stakelogic',
            'push-gaming'  => 'push_gaming',
        ][$id] ?? $id;
    }

    private function shortUrl(string $url): string
    {
        return strlen($url) > 200 ? substr($url, 0, 200) . '...' : $url;
    }
}
