<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class EventsShortcode
{
    public const TAG = 'kmc-eversports-events';

    public static function register(): void
    {
        add_action('init', [self::class, 'registerShortcode']);
    }

    public static function registerShortcode(): void
    {
        add_shortcode(self::TAG, [self::class, 'handle']);
    }

    /**
     * @param array<int|string, string>|string $atts
     */
    public static function handle(array|string $atts): string
    {
        $shortcodeAttributes = is_array($atts) ? $atts : [];
        $blockAttributes = self::toBlockAttributes($shortcodeAttributes);

        return Renderer::render($blockAttributes);
    }

    /**
     * @param array<int|string, string> $atts
     * @return array{showImages: bool, groupIds: list<string>}
     */
    public static function toBlockAttributes(array $atts): array
    {
        return [
            'showImages' => self::parseShowImage($atts['show-image'] ?? 'true'),
            'groupIds' => GroupIdList::fromCommaSeparated($atts['group-ids'] ?? ''),
        ];
    }

    private static function parseShowImage(string $value): bool
    {
        return strtolower(trim($value)) === 'true';
    }
}
