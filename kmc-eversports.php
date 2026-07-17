<?php

/**
 * Plugin Name: KMC Eversports
 * Description: Displays Eversports class schedules on the KMC WordPress site.
 * Version: 2.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * Author: Kadampa Meditationszentrum Karlsruhe
 * Author URI: https://meditation-karlsruhe.de
 * License: Proprietary
 * Update URI: https://github.com/MeditationKarlsruhe/kmc-eversports/
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

$updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/MeditationKarlsruhe/kmc-eversports/',
    __FILE__,
    'kmc-eversports'
);
$updateChecker->getVcsApi()->enableReleaseAssets();

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'kmc-eversports',
        plugin_dir_url(__FILE__) . 'assets/css/kmc-eversports.css',
        [],
        (string) filemtime(__DIR__ . '/assets/css/kmc-eversports.css')
    );
});

\Kmc\Eversports\AdminPage::register();
\Kmc\Eversports\GroupsEndpoint::register();

add_action('init', function (): void {
    register_block_type(__DIR__ . '/block.json', [
        'render_callback' => [\Kmc\Eversports\Renderer::class, 'render'],
    ]);
});
