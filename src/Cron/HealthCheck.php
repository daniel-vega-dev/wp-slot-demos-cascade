<?php

namespace SlotDemosCascade\Cron;

use SlotDemosCascade\CascadeRouter;
use SlotDemosCascade\Logger;

class HealthCheck
{
    public static function run(): void
    {
        $router = new CascadeRouter();
        $map = $router->gamesMap();
        foreach ($map as $game => $_cfg) {
            // Skip JSON metadata keys (_comment, _help, etc.)
            if (substr((string) $game, 0, 1) === '_') {
                continue;
            }
            $results = $router->checkAll($game);
            foreach ($results as $r) {
                Logger::health([
                    'event'      => 'health',
                    'game'       => $game,
                    'source'     => $r['source'] ?? '?',
                    'status'     => !empty($r['ok']) ? 'ok' : 'fail',
                    'error'      => $r['error'] ?? null,
                    'latency_ms' => $r['latency_ms'] ?? null,
                ]);
            }
            // Be polite — small spacing between provider requests
            usleep(500000); // 0.5 s
        }
    }
}
