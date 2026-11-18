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
    }

    protected function popup_elements(): void
    {
        $groupOptions = is_admin() ? AviaEventsIntegration::groupOptions() : [];

        $this->elements = [
            ['type' => 'tab_container', 'nodescription' => true],
            ['type' => 'tab', 'name' => __('Content', 'avia_framework'), 'nodescription' => true],
            [
                'name' => 'Kurse',
                'desc' => 'Einen oder mehrere Kurse auswählen (Strg/Cmd gedrückt halten für Mehrfachauswahl).',
                'id' => 'course_groups',
                'type' => 'select',
                'multiple' => 10,
                'std' => '',
                'subtype' => $groupOptions,
            ],
            [
                'name' => 'Bilder anzeigen',
                'desc' => 'Kursbild über der Beschreibung anzeigen.',
                'id' => 'show_images',
                'type' => 'select',
                'std' => 'yes',
                'subtype' => ['Ja' => 'yes', 'Nein' => 'no'],
            ],
            ['type' => 'tab_close', 'nodescription' => true],
            ['type' => 'tab_container_close', 'nodescription' => true],
        ];
    }

    /**
     * @param mixed $params
     * @return array<string, mixed>
     */
    public function editor_element($params): array
    {
        $editorElement = parent::editor_element($params);
        $editorElement['content'] = null;

        return $editorElement;
    }

    /**
     * @param mixed $atts
     * @param mixed $content
     * @param mixed $shortcodename
     * @param mixed $meta
     */
    public function shortcode_handler($atts, $content = '', $shortcodename = '', $meta = ''): string
    {
        $shortcodeAttributes = is_array($atts) ? $atts : [];
        $blockAttributes = AviaEventsIntegration::toBlockAttributes($shortcodeAttributes);

        return Renderer::render($blockAttributes);
    }
}
