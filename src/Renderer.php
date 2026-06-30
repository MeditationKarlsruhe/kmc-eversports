<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class Renderer
{
    /** @param array<string, mixed> $attributes */
    public static function render(array $attributes): string
    {
        $showImage = $attributes['showImages'] ?? true;

        try {
            $json = EversportsClient::fetchActivities();
            $groups = ActivityParser::parse($json);
        } catch (\Throwable $e) {
            error_log('KMC Eversports: ' . $e->getMessage());
            if (defined('WP_DEBUG') && WP_DEBUG) {
                return '<!-- KMC Eversports Error: ' . esc_html($e->getMessage()) . ' -->';
            }
            return '';
        }

        ob_start();
        include dirname(__DIR__) . '/templates/eversports-events.php';
        return (string) ob_get_clean();
    }
}
