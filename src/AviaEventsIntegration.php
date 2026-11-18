<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class AviaEventsIntegration
{
    public const SHORTCODE = 'av_eversports_events';

    public const GROUPS_UNAVAILABLE_LABEL =
        'Kurse konnten nicht geladen werden – API-Token unter Einstellungen → KMC Eversports prüfen';

    public static function register(): void
    {
        add_filter('avia_load_shortcodes', [self::class, 'addElementFolder'], 10);
        add_filter('avf_preview_window_css_files', [self::class, 'addPreviewStylesheet']);
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    public static function addElementFolder(array $paths): array
    {
        $paths[] = dirname(__DIR__) . '/enfold/';

        return $paths;
    }

    /**
     * @param array<string, int> $stylesheets
     * @return array<string, int>
     */
    public static function addPreviewStylesheet(array $stylesheets): array
    {
        $pluginUrl = plugin_dir_url(dirname(__DIR__) . '/kmc-eversports.php');
        $stylesheets[$pluginUrl . 'assets/css/kmc-eversports.css'] = 1;

        return $stylesheets;
    }

    /**
     * @return array<string, string>
     */
    public static function groupOptions(): array
    {
        try {
            $groups = GroupsParser::parse(EversportsClient::fetchGroups());
        } catch (\Throwable $e) {
            error_log('KMC Eversports: ' . $e->getMessage());
            return [self::GROUPS_UNAVAILABLE_LABEL => ''];
        }

        return self::toOptions($groups);
    }

    /**
     * @param list<GroupSummary> $groups
     * @return array<string, string>
     */
    public static function toOptions(array $groups): array
    {
        $options = [];
        foreach ($groups as $group) {
            $name = trim($group->name);
            $label = array_key_exists($name, $options)
                ? $name . ' (' . substr($group->id, 0, 8) . ')'
                : $name;
            $options[$label] = $group->id;
        }

        return $options;
    }

    /**
     * @param array<int|string, mixed> $atts
     * @return array{showImages: bool, groupIds: list<string>}
     */
    public static function toBlockAttributes(array $atts): array
    {
        $courseGroups = is_string($atts['course_groups'] ?? null) ? $atts['course_groups'] : '';
        $showImages = is_string($atts['show_images'] ?? null) ? $atts['show_images'] : 'yes';

        return [
            'showImages' => $showImages !== 'no',
            'groupIds' => self::parseGroupIds($courseGroups),
        ];
    }

    /**
     * @return list<string>
     */
    private static function parseGroupIds(string $value): array
    {
        $trimmed = array_map('trim', explode(',', $value));

        return array_values(array_filter(
            $trimmed,
            static fn (string $groupId): bool => $groupId !== '',
        ));
    }
}
