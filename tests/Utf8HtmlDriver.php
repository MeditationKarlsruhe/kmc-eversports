<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests;

use DOMDocument;
use Spatie\Snapshots\Drivers\HtmlDriver;

final class Utf8HtmlDriver extends HtmlDriver
{
    public function serialize(mixed $data): string
    {
        if (! is_string($data)) {
            return parent::serialize($data);
        }

        $domDocument = new DOMDocument('1.0', 'UTF-8');
        $domDocument->preserveWhiteSpace = false;
        $domDocument->formatOutput = true;

        // The <?xml encoding="UTF-8"> prefix tells libxml2 to parse the HTML as
        // UTF-8, preventing multibyte chars from being misread as Latin-1 entities.
        @$domDocument->loadHTML('<?xml encoding="UTF-8">' . $data, LIBXML_HTML_NODEFDTD);

        $htmlValue = (string) preg_replace(
            '/^<\?xml encoding="UTF-8">/',
            '',
            (string) $domDocument->saveHTML(),
        );

        if (PHP_OS_FAMILY === 'Windows') {
            $htmlValue = implode("\n", explode("\r\n", $htmlValue));
        }

        return $htmlValue;
    }
}
