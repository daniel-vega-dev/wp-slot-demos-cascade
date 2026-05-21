<?php

namespace SlotDemosCascade\Rest;

use SlotDemosCascade\CascadeRouter;
use SlotDemosCascade\Logger;

class HealthRoute
{
    public static function register(): void
    {
        register_rest_route('slot-demos-cascade/v1', '/health', [
            'methods'             => 'GET',
            'permission_callback' => function () { return current_user_can('manage_options'); },
            'callback'            => [self::class, 'recent'],
            'args' => [
                'limit' => ['required' => false, 'type' => 'integer', 'default' => 200],
            ],
        ]);

        register_rest_route('slot-demos-cascade/v1', '/health/check', [
            'methods'             => 'GET',
            'permission_callback' => function () { return current_user_can('manage_options'); },
            'callback'            => [self::class, 'runCheck'],
            'args' => [
                'game' => ['required' => false, 'type' => 'string'],
            ],
        ]);
    }

    public static function recent(\WP_REST_Request $req)
    {
        $limit = max(10, min(2000, (int) $req->get_param('limit')));
        return [
            'launches' => Logger::readRecent('launches', $limit),
            'health'   => Logger::readRecent('health', $limit),
        ];
    }

    public static function runCheck(\WP_REST_Request $req)
    {
        $router = new CascadeRouter();
        $game = sanitize_title((string) $req->get_param('game'));
        $map = $router->gamesMap();

        if ($game !== '') {
            $games = isset($map[$game]) ? [$game] : [];
        } else {
            // Skip JSON metadata keys (_comment, _help, _no_source_note, etc.)
            $games = array_filter(array_keys($map), function ($k) {
                return is_string($k) && substr($k, 0, 1) !== '_';
            });
        }

        $report = [];
        foreach ($games as $g) {
            $results = $router->checkAll($g);
            $report[$g] = $results;
            foreach ($results as $r) {
                Logger::health([
                    'event'  => 'health',
                    'game'   => $g,
                    'source' => $r['source'] ?? '?',
                    'status' => !empty($r['ok']) ? 'ok' : 'fail',
                    'error'  => $r['error'] ?? null,
                    'latency_ms' => $r['latency_ms'] ?? null,
                ]);
            }
        }

        return ['ok' => true, 'report' => $report];
    }
}
