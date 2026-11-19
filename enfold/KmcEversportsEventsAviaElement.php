<?php

declare(strict_types=1);

use Kmc\Eversports\AviaEventsIntegration;
use Kmc\Eversports\Renderer;

/**
 * Global namespace on purpose: Enfold uses the class name as HTML id and jQuery selector
 * (#avia-tmpl-<class>), where namespace backslashes would act as CSS escapes.
 *
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
final class KmcEversportsEventsAviaElement extends \aviaShortcodeTemplate
{
    protected function shortcode_insert_button(): void
    {
        $this->config['version'] = '1.0';
        $this->config['self_closing'] = 'yes';
        $this->config['name'] = 'Eversports Events';
        $this->config['tab'] = __('Content Elements', 'avia_framework');
        $this->config['icon'] = \AviaBuilder::$path['iconsURL'] . 'sc-upcoming-events.svg';
        $this->config['order'] = 5;
        $this->config['target'] = 'avia-target-insert';
        $this->config['shortcode'] = AviaEventsIntegration::SHORTCODE;
        $this->config['tooltip'] = 'Zeigt den Kursplan ausgewählter Eversports-Kurse an';
        $this->config['preview'] = 'large';
        $this->config['alb_items'] = ['value' => AviaEventsIntegration::COURSE_NAMES_FIELD];
    }

    protected function popup_elements(): void
    {
        $this->elements = AviaEventsIntegration::popupElements($_POST);
    }

    protected function admin_assets(): void
    {
        add_action('admin_enqueue_scripts', [AviaEventsIntegration::class, 'enqueueCoursePickerScript']);
    }

    /**
     * @param array<string, string> $atts
     * @param mixed $content
     * @param mixed $shortcodename
     * @param mixed $meta
     */
    public function shortcode_handler($atts, $content = '', $shortcodename = '', $meta = ''): string
    {
        $blockAttributes = AviaEventsIntegration::toBlockAttributes($atts);

        return Renderer::render($blockAttributes);
    }
}
