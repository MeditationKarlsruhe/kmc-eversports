<?php

/**
 * Minimal stand-ins for the Enfold (Avia Layout Builder) classes the plugin extends.
 *
 * Enfold is a commercial theme and is not available in CI. These stubs mirror the
 * parts of Enfold 8.3.2's API that KmcEversportsEventsAviaElement relies on
 * (config-templatebuilder/avia-template-builder/php/class-shortcode-template.php):
 * the constructor calls shortcode_insert_button() and then popup_elements().
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
     * @param mixed $atts
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

    /**
     * @param mixed $params
     * @return array<string, mixed>
     */
    public function editor_element($params)
    {
        return is_array($params) ? $params : [];
    }
}
