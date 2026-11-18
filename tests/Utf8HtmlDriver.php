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

        return self::decodeNonAsciiEntities($htmlValue);
    }

    /**
     * libxml2 < 2.14 serializes non-ASCII characters as named entities (&ndash;),
     * newer versions emit them as raw UTF-8 (–). Normalize to raw UTF-8 so the
     * snapshot does not depend on the libxml2 version of the machine running the
     * tests. Entities for ASCII characters (&amp;, &lt;, …) are kept, as they carry
     * meaning in HTML.
     */
    private static function decodeNonAsciiEntities(string $html): string
    {
        return (string) preg_replace_callback(
            '/&(?:[a-zA-Z][a-zA-Z0-9]*|#[0-9]+|#x[0-9a-fA-F]+);/',
            static function (array $match): string {
                $decoded = html_entity_decode($match[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return ord($decoded[0]) >= 0x80 ? $decoded : $match[0];
            },
            $html,
        );
    }
}
