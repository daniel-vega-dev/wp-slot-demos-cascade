<?php

namespace SlotDemosCascade\Admin;

use SlotDemosCascade\CascadeRouter;
use SlotDemosCascade\Logger;

class HealthScreen
{
    public static function register(): void
    {
        add_menu_page(
            'Demos',
            'Demos',
            'manage_options',
            'slot-demos-cascade-health',
            [self::class, 'render'],
            'dashicons-controls-play',
            27 // after Push (26)
        );
        add_submenu_page(
            'slot-demos-cascade-health',
            'Health',
            'Health',
            'manage_options',
            'slot-demos-cascade-health',
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('forbidden');
        }
        $router = new CascadeRouter();
        $map = $router->gamesMap();
        $health = Logger::readRecent('health', 1500);

        // Build matrix: game → source → most recent status
        $matrix = [];
        foreach ($health as $row) {
            $g = $row['game'] ?? null;
            $s = $row['source'] ?? null;
            if (!$g || !$s) continue;
            if (isset($matrix[$g][$s])) continue; // first seen = most recent because logs are reverse-chrono
            $matrix[$g][$s] = $row;
        }

        $sources = ['direct'];

        echo '<div class="wrap"><h1>Demo Health</h1>';
        echo '<p>Cascade order: <strong>' . esc_html(implode(' → ', $sources)) . '</strong>. Cron runs daily at 04:00 UTC.</p>';
        echo '<p><a class="button button-primary" href="' . esc_url(rest_url('slot-demos-cascade/v1/health/check')) . '" target="_blank">Run check now (all games)</a></p>';

        echo '<table class="widefat fixed striped"><thead><tr>';
        echo '<th>Game</th>';
        foreach ($sources as $s) echo '<th>' . esc_html($s) . '</th>';
        echo '<th>Latest URL</th></tr></thead><tbody>';

        foreach ($map as $game => $_cfg) {
            echo '<tr><td><code>' . esc_html($game) . '</code></td>';
            foreach ($sources as $s) {
                $row = $matrix[$game][$s] ?? null;
                if (!$row) {
                    echo '<td><span style="color:#ccc">—</span></td>';
                    continue;
                }
                $color = $row['status'] === 'ok' ? '#46b450' : '#dc3232';
                $title = ($row['error'] ?? '') . ' (' . ($row['latency_ms'] ?? '?') . 'ms)';
                echo '<td><span title="' . esc_attr($title) . '" style="color:' . $color . ';font-weight:bold">●</span></td>';
            }
            $launches = array_filter(Logger::readRecent('launches', 200), function ($r) use ($game) {
                return ($r['game'] ?? null) === $game && !empty($r['url']);
            });
            $latest = $launches ? reset($launches) : null;
            echo '<td><code style="font-size:11px;color:#666">' . esc_html($latest['url'] ?? '') . '</code></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        echo '<h2>Recent launch attempts (latest 50)</h2>';
        echo '<table class="widefat striped"><thead><tr><th>Time (UTC)</th><th>Game</th><th>Source</th><th>Status</th><th>Latency</th><th>Error / URL</th></tr></thead><tbody>';
        $latestLaunches = array_slice(Logger::readRecent('launches', 50), 0, 50);
        foreach ($latestLaunches as $r) {
            $color = ($r['status'] ?? '') === 'ok' ? '#46b450' : '#dc3232';
            echo '<tr>';
            echo '<td>' . esc_html($r['ts'] ?? '') . '</td>';
            echo '<td><code>' . esc_html($r['game'] ?? '') . '</code></td>';
            echo '<td>' . esc_html($r['source'] ?? '') . '</td>';
            echo '<td><span style="color:' . $color . '">' . esc_html($r['status'] ?? '') . '</span></td>';
            echo '<td>' . esc_html((string)($r['latency_ms'] ?? '')) . 'ms</td>';
            $detail = $r['status'] === 'ok' ? ($r['url'] ?? '') : ($r['error'] ?? '');
            echo '<td><code style="font-size:11px">' . esc_html($detail) . '</code></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }
}
