<?php

namespace SlotDemosCascade\Rest;

use SlotDemosCascade\CascadeRouter;

class LaunchRoute
{
    public static function register(): void
    {
        register_rest_route('slot-demos-cascade/v1', '/launch', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'callback'            => [self::class, 'handle'],
            'args'                => [
                'game'      => ['required' => true, 'type' => 'string'],
                'locale'    => ['required' => false, 'type' => 'string', 'default' => 'es'],
                'blacklist' => ['required' => false, 'type' => 'string', 'default' => ''],
                'prefer'    => ['required' => false, 'type' => 'string', 'default' => ''],
            ],
        ]);
    }

    public static function handle(\WP_REST_Request $req)
    {
        // Prevent LiteSpeed/Cloudflare from caching this REST response — cascade
        // results change as we update games-map and source registry.
        nocache_headers();
        // LiteSpeed-specific: tell its cache engine to skip caching this request.
        // Uses both the action API (preferred) and explicit header override.
        if (function_exists('do_action')) {
            do_action('litespeed_control_set_nocache', 'slot-demos-cascade cascade dynamic');
            do_action('litespeed_control_set_private', 'slot-demos-cascade cascade');
        }
        if (!headers_sent()) {
            header('Cache-Control: private, no-cache, no-store, must-revalidate, max-age=0', true);
            header('X-LiteSpeed-Cache-Control: no-cache, no-store, private', true);
            header('X-LiteSpeed-Cache: no-cache', true);
        }

        $game = sanitize_title((string) $req->get_param('game'));
        $locale = sanitize_text_field((string) $req->get_param('locale')) ?: 'es';
        $blacklistRaw = (string) $req->get_param('blacklist');
        $blacklist = array_filter(array_map('trim', explode(',', $blacklistRaw)));
        $prefer = sanitize_text_field((string) $req->get_param('prefer'));

        $router = new CascadeRouter();
        $res = $router->launch($game, $locale, $blacklist, $prefer);

        // Always 200 — Cloudflare replaces 5xx with its canned error page
        return new \WP_REST_Response([
            'ok'                => $res['ok'] ?? false,
            'url'               => $res['url'] ?? null,
            'source'            => $res['source'] ?? null,
            'error'             => $res['error'] ?? null,
            'attempts'          => $res['attempts'] ?? [],
            'available_sources' => $res['available_sources'] ?? [],
        ], 200);
    }
}
