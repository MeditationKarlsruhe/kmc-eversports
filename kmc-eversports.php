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

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'kmc-eversports',
        plugin_dir_url(__FILE__) . 'assets/css/kmc-eversports.css',
        [],
        '1.0.0'
    );
});

add_action('init', function (): void {
    add_shortcode('eversports-events', function (array $atts): string {
        $atts = shortcode_atts(['show-image' => 'true'], $atts);
        $showImage = $atts['show-image'] === 'true';

        $json = \Kmc\Eversports\EversportsClient::fetchActivities();
        $groups = \Kmc\Eversports\ActivityParser::parse($json);

        ob_start();
        include __DIR__ . '/templates/eversports-events.php';
        return (string) ob_get_clean();
    });

    register_block_type(__DIR__ . '/block.json', [
        'render_callback' => function (array $attributes): string {
            $showImage = $attributes['showImages'] ?? true;

            $json = \Kmc\Eversports\EversportsClient::fetchActivities();
            $groups = \Kmc\Eversports\ActivityParser::parse($json);

            ob_start();
            include __DIR__ . '/templates/eversports-events.php';
            return (string) ob_get_clean();
        },
    ]);
});
