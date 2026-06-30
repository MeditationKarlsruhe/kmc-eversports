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
        (string) filemtime(__DIR__ . '/assets/css/kmc-eversports.css')
    );
});

add_action('init', function (): void {
    register_block_type(__DIR__ . '/block.json', [
        'render_callback' => [\Kmc\Eversports\Renderer::class, 'render'],
    ]);
});
