<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class EventsTemplate
{
    private const TEMPLATE_PATH = '/templates/eversports-events.php';

    /**
     * @param list<ClassGroup> $groups
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function render(array $groups, bool $showImage): string
    {
        ob_start();
        include dirname(__DIR__) . self::TEMPLATE_PATH;
        return (string) ob_get_clean();
    }
}
