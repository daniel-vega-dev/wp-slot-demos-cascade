<?php

namespace SlotDemosCascade;

class Bootstrap
{
    public static function init(): void
    {
        add_action('rest_api_init', [Rest\LaunchRoute::class, 'register']);
        add_action('rest_api_init', [Rest\HealthRoute::class, 'register']);

        ContentInjector::register();

        // Theme functions.php blocks REST for non-logged-in users; allow public /launch.
        // /health* stays gated by its own permission_callback (manage_options).
        add_filter('rest_authentication_errors', [self::class, 'allowPublicLaunch'], 5);

        add_action('slot_demos_cascade_daily_health', [Cron\HealthCheck::class, 'run']);
        if (!wp_next_scheduled('slot_demos_cascade_daily_health')) {
            wp_schedule_event(strtotime('tomorrow 04:00 UTC'), 'daily', 'slot_demos_cascade_daily_health');
        }

        if (is_admin()) {
            add_action('admin_menu', [Admin\HealthScreen::class, 'register']);
        }
    }

    public static function onActivate(): void
    {
        Logger::ensureLogDir();
        if (!wp_next_scheduled('slot_demos_cascade_daily_health')) {
            wp_schedule_event(strtotime('tomorrow 04:00 UTC'), 'daily', 'slot_demos_cascade_daily_health');
        }
    }

    public static function onDeactivate(): void
    {
        wp_clear_scheduled_hook('slot_demos_cascade_daily_health');
    }

    public static function allowPublicLaunch($result)
    {
        if (true === $result || is_wp_error($result)) {
            return $result;
        }
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        if (strpos($uri, '/wp-json/slot-demos-cascade/v1/launch') !== false) {
            return true;
        }
        return $result;
    }
}
