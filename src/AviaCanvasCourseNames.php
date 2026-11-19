<?php

declare(strict_types=1);

namespace Kmc\Eversports;

/**
 * Builder canvas markup of the Avia element: one item per selected course, stacked.
 * Enfold replaces the container's content with the raw course_names value after the edit
 * window is saved; assets/js/avia-course-picker.js then splits it into items again.
 */
final class AviaCanvasCourseNames
{
    public const SEPARATOR = '|';

    public static function render(string $storedNames): string
    {
        $items = array_map(
            fn (string $name): string => "<span class='avia-element-item'>" . esc_html($name) . '</span>',
            self::split($storedNames),
        );

        return "<div class='avia-element-items kmc-eversports-course-names'"
            . " data-update_with='" . AviaEventsIntegration::COURSE_NAMES_FIELD . "'"
            . " style='flex-direction:column;align-items:flex-start'>"
            . implode('', $items)
            . '</div>';
    }

    /**
     * @return list<string>
     */
    private static function split(string $storedNames): array
    {
        $names = array_map(
            fn (string $name): string => trim(html_entity_decode($name, ENT_QUOTES)),
            explode(self::SEPARATOR, $storedNames),
        );

        return array_values(array_filter($names, fn (string $name): bool => $name !== ''));
    }
}
