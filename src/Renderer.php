<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class Renderer
{
    /**
     * @param array<string, mixed> $attributes
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public static function render(array $attributes): string
    {
        $showImage = $attributes['showImages'] ?? true;
        $groupIds = $attributes['groupIds'] ?? [];

        try {
            if (!is_array($groupIds) || $groupIds === []) {
                throw new \RuntimeException('At least one group must be selected.');
            }

            $json = EversportsClient::fetchActivities();
            $groups = array_values(array_filter(
                ActivityParser::parse($json),
                static fn (ClassGroup $group): bool => in_array($group->id, $groupIds, true),
            ));
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
