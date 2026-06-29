<?php

/**
 * Plugin Name: KMC Eversports
 * Description: Displays Eversports class schedules on the KMC WordPress site.
 * Version: 2.0.0
 * Requires PHP: 8.2
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

add_action('init', function (): void {
    add_shortcode('eversports-events', function (array $atts): string {
        $atts = shortcode_atts(['group-ids' => '', 'show-image' => 'true'], $atts);
        $groupIds = array_values(array_filter(array_map('trim', explode(',', (string) $atts['group-ids']))));

        try {
            $client = new \Kmc\Eversports\EversportsClient();
            $json = $client->fetchActivities($groupIds);

            $parser = new \Kmc\Eversports\ActivityParser();
            $groups = $parser->parse($json);

            return '<pre>' . esc_html(print_r($groups, true)) . '</pre>';
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                return '<pre>KMC Eversports Fehler: ' . esc_html($e->getMessage()) . '</pre>';
            }
            return '';
        }
    });
});
