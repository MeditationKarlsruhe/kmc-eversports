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
        $atts = shortcode_atts(['show-image' => 'true'], $atts);

        $json = \Kmc\Eversports\EversportsClient::fetchActivities();
        $groups = \Kmc\Eversports\ActivityParser::parse($json);

        return '<pre>' . esc_html(print_r($groups, true)) . '</pre>';
    });
});
