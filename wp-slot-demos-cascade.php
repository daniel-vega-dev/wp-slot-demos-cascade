<?php
/**
 * Plugin Name: Slot Demos Cascade
 * Plugin URI: https://github.com/USER/wp-slot-demos-cascade
 * Description: WordPress plugin that loads slot game demos directly from provider CDNs with a configurable cascade fallback chain, health logging, REST API and an admin dashboard.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Slot Demos Cascade Contributors
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: slot-demos-cascade
 *
 * @package SlotDemosCascade
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SLOT_DEMOS_CASCADE_VERSION', '1.0.0');
define('SLOT_DEMOS_CASCADE_FILE', __FILE__);
define('SLOT_DEMOS_CASCADE_DIR', plugin_dir_path(__FILE__));
define('SLOT_DEMOS_CASCADE_URL', plugin_dir_url(__FILE__));

spl_autoload_register(function ($class) {
    if (strpos($class, 'SlotDemosCascade\\') !== 0) {
        return;
    }
    $rel = str_replace(['SlotDemosCascade\\', '\\'], ['', '/'], $class);
    $path = SLOT_DEMOS_CASCADE_DIR . 'src/' . $rel . '.php';
    if (is_readable($path)) {
        require_once $path;
    }
});

add_action('plugins_loaded', function () {
    \SlotDemosCascade\Bootstrap::init();
});

register_activation_hook(__FILE__, [\SlotDemosCascade\Bootstrap::class, 'onActivate']);
register_deactivation_hook(__FILE__, [\SlotDemosCascade\Bootstrap::class, 'onDeactivate']);
