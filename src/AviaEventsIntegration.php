<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class AviaEventsIntegration
{
    public const SHORTCODE = 'av_eversports_events';

    public const POPUP_AJAX_ACTION = 'avia_ajax_' . self::SHORTCODE;

    public const GROUPS_UNAVAILABLE_TITLE = 'Kurse konnten nicht geladen werden';

    public const GROUPS_UNAVAILABLE_DESCRIPTION =
        'Bitte den API-Token unter Einstellungen → KMC Eversports prüfen. '
        . 'Die bisher gespeicherte Kursauswahl bleibt beim Speichern erhalten.';

    public const UNKNOWN_GROUP_LABEL = 'Nicht mehr aktiver Kurs';

    public const COURSE_NAMES_FIELD = 'course_names';

    public const COURSE_PICKER_SCRIPT_HANDLE = 'kmc-eversports-avia-course-picker';

    private const COURSE_PICKER_SCRIPT_PATH = 'assets/js/avia-course-picker.js';

    private const EDITOR_SCREENS = ['post.php', 'post-new.php'];

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

    public static function enqueueCoursePickerScript(string $hookSuffix): void
    {
        if (!in_array($hookSuffix, self::EDITOR_SCREENS, true)) {
            return;
        }

        $pluginFile = dirname(__DIR__) . '/kmc-eversports.php';
        wp_enqueue_script(
            self::COURSE_PICKER_SCRIPT_HANDLE,
            plugin_dir_url($pluginFile) . self::COURSE_PICKER_SCRIPT_PATH,
            ['jquery'],
            (string) filemtime(dirname(__DIR__) . '/' . self::COURSE_PICKER_SCRIPT_PATH),
            true,
        );
    }

    /**
     * Enfold builds the popup definition on every request; the Eversports API is only
     * queried when the builder actually requests this element's edit window via AJAX.
     *
     * @param array<mixed> $request
     * @return list<array<string, mixed>>
     */
    public static function popupElements(array $request): array
    {
        if (($request['action'] ?? null) !== self::POPUP_AJAX_ACTION) {
            return self::form([]);
        }

        $storedGroupIds = self::storedGroupIds(self::storedShortcode($request));

        try {
            $groups = GroupsParser::parse(EversportsClient::fetchGroups());
        } catch (\Throwable $e) {
            error_log('KMC Eversports: ' . $e->getMessage());
            return self::unavailableForm();
        }

        $options = self::toOptions($groups, $storedGroupIds);

        return self::form($options);
    }

    /**
     * @param array<string, string> $groupOptions
     * @return list<array<string, mixed>>
     */
    public static function form(array $groupOptions): array
    {
        return self::wrapInTab([
            [
                'name' => 'Kurse',
                'desc' => 'Ein Klick wählt einen Kurs an oder ab. Das Filterfeld grenzt die Liste ein, '
                    . 'die Auswahl bleibt dabei erhalten.',
                'id' => 'course_groups',
                'type' => 'select',
                'multiple' => 10,
                'std' => '',
                'subtype' => $groupOptions,
            ],
            self::courseNamesField(),
            self::showImagesField(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function unavailableForm(): array
    {
        return self::wrapInTab([
            [
                'name' => self::GROUPS_UNAVAILABLE_TITLE,
                'desc' => self::GROUPS_UNAVAILABLE_DESCRIPTION,
                'type' => 'heading',
                'description_class' => 'av-builder-note av-error',
            ],
            ['id' => 'course_groups', 'type' => 'hidden', 'std' => ''],
            self::courseNamesField(),
            self::showImagesField(),
        ]);
    }

    /**
     * @param list<GroupSummary> $groups
     * @param list<string> $storedGroupIds
     * @return array<string, string>
     */
    public static function toOptions(array $groups, array $storedGroupIds): array
    {
        $options = [];
        foreach ($groups as $group) {
            $name = esc_html(trim($group->name));
            $label = array_key_exists($name, $options) ? self::withIdSuffix($name, $group->id) : $name;
            $options[$label] = $group->id;
        }

        foreach (array_diff($storedGroupIds, $options) as $unknownGroupId) {
            $options[self::withIdSuffix(self::UNKNOWN_GROUP_LABEL, $unknownGroupId)] = $unknownGroupId;
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function storedGroupIds(string $shortcode): array
    {
        if (preg_match('/\bcourse_groups=[\'"]([^\'"]*)[\'"]/', $shortcode, $match) !== 1) {
            return [];
        }

        return GroupIdList::fromCommaSeparated($match[1]);
    }

    /**
     * @param array<string, string> $atts
     * @return array{showImages: bool, groupIds: list<string>}
     */
    public static function toBlockAttributes(array $atts): array
    {
        return [
            'showImages' => self::parseShowImages($atts['show_images'] ?? 'yes'),
            'groupIds' => GroupIdList::fromCommaSeparated($atts['course_groups'] ?? ''),
        ];
    }

    private static function parseShowImages(string $value): bool
    {
        return $value !== 'no';
    }

    /**
     * @param array<mixed> $request
     */
    private static function storedShortcode(array $request): string
    {
        $params = $request['params'] ?? null;
        $shortcode = is_array($params) ? ($params['shortcode'] ?? '') : '';

        return is_string($shortcode) ? stripslashes($shortcode) : '';
    }

    private static function withIdSuffix(string $label, string $groupId): string
    {
        return $label . ' (' . substr($groupId, 0, 8) . ')';
    }

    /**
     * Filled by assets/js/avia-course-picker.js with the selected course names, so the
     * builder canvas can name the courses without querying Eversports on page load.
     *
     * @return array<string, mixed>
     */
    private static function courseNamesField(): array
    {
        return ['id' => self::COURSE_NAMES_FIELD, 'type' => 'hidden', 'std' => ''];
    }

    /**
     * @return array<string, mixed>
     */
    private static function showImagesField(): array
    {
        return [
            'name' => 'Bilder anzeigen',
            'desc' => 'Kursbild über der Beschreibung anzeigen.',
            'id' => 'show_images',
            'type' => 'select',
            'std' => 'yes',
            'subtype' => ['Ja' => 'yes', 'Nein' => 'no'],
        ];
    }

    /**
     * @param list<array<string, mixed>> $fields
     * @return list<array<string, mixed>>
     */
    private static function wrapInTab(array $fields): array
    {
        return [
            ['type' => 'tab_container', 'nodescription' => true],
            ['type' => 'tab', 'name' => __('Content', 'avia_framework'), 'nodescription' => true],
            ...$fields,
            ['type' => 'tab_close', 'nodescription' => true],
            ['type' => 'tab_container_close', 'nodescription' => true],
        ];
    }
}
