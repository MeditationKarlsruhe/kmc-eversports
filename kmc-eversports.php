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
    add_shortcode('eversports-events', function (): string {
        return '<p>KMC Eversports Plugin aktiv.</p>';
    });
});
