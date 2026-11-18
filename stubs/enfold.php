<?php

/**
 * Minimal stand-ins for the Enfold (Avia Layout Builder) classes the plugin extends.
 *
 * Enfold is a commercial theme and is not available in CI. These stubs mirror only the
 * parts of Enfold 8.3.2's API that KmcEversportsEventsAviaElement relies on
 * (config-templatebuilder/avia-template-builder/php/class-shortcode-template.php).
 *
 * Simplified on purpose: the real constructor calls shortcode_insert_button(), then
 * extra_config() (which calls popup_elements() and expands popup templates), and then
 * appends hidden av_uid/sc_version fields. The stub only calls the two plugin hooks, so
 * tests see exactly the plugin's own config and popup definition.
 *
 * The real shortcode_handler_prepare() always passes an array as $atts.
 *
 * Used by PHPStan (scanFiles) and by the PHPUnit bootstrap. Never loaded in WordPress.
 */

declare(strict_types=1);

class AviaBuilder
{
    /** @var array<string, string> */
    public static $path = ['iconsURL' => 'https://example.test/enfold/icons/'];
}

abstract class aviaShortcodeTemplate
{
    /** @var array<string, mixed> */
    public $config = [];

    /** @var list<array<string, mixed>> */
    public $elements = [];

    public function __construct(AviaBuilder $builder)
    {
        unset($builder);
        $this->shortcode_insert_button();
        $this->popup_elements();
    }

    /** @return void */
    abstract protected function shortcode_insert_button();

    /**
     * @param array<string, string> $atts
     * @param mixed $content
     * @param mixed $shortcodename
     * @param mixed $meta
     * @return string
     */
    abstract public function shortcode_handler($atts, $content = '', $shortcodename = '', $meta = '');

    /** @return void */
    protected function popup_elements()
    {
    }
}
