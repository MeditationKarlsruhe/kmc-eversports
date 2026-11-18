<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Kmc\Eversports\AviaEventsIntegration;
use Kmc\Eversports\GroupSummary;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AviaEventsIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRegisterAddsElementFolderAndPreviewStylesheetFilters(): void
    {
        Filters\expectAdded('avia_load_shortcodes')
            ->once()
            ->with([AviaEventsIntegration::class, 'addElementFolder'], 10);
        Filters\expectAdded('avf_preview_window_css_files')
            ->once()
            ->with([AviaEventsIntegration::class, 'addPreviewStylesheet']);

        AviaEventsIntegration::register();

        $this->addToAssertionCount(2);
    }

    public function testAddElementFolderAppendsPluginEnfoldFolderWithTrailingSlash(): void
    {
        $result = AviaEventsIntegration::addElementFolder(['/theme/avia-shortcodes/']);

        self::assertSame(
            ['/theme/avia-shortcodes/', dirname(__DIR__, 2) . '/enfold/'],
            $result,
        );
    }

    public function testAddPreviewStylesheetAddsPluginStylesheet(): void
    {
        Functions\when('plugin_dir_url')->justReturn('https://site.test/wp-content/plugins/kmc-eversports/');

        $result = AviaEventsIntegration::addPreviewStylesheet(['https://site.test/theme.css' => 1]);

        self::assertSame(
            [
                'https://site.test/theme.css' => 1,
                'https://site.test/wp-content/plugins/kmc-eversports/assets/css/kmc-eversports.css' => 1,
            ],
            $result,
        );
    }

    public function testGroupOptionsMapsGroupNamesToIds(): void
    {
        $json = json_encode([
            'data' => [
                'activityGroups' => [
                    'nodes' => [
                        ['id' => 'b-uuid', 'name' => 'Meditation am Abend '],
                        ['id' => 'a-uuid', 'name' => 'Arya Tara Puja'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
        Functions\when('get_transient')->justReturn($json);

        $result = AviaEventsIntegration::groupOptions();

        self::assertSame(
            ['Arya Tara Puja' => 'a-uuid', 'Meditation am Abend' => 'b-uuid'],
            $result,
        );
    }

    public function testGroupOptionsReturnsHintWhenGroupsCannotBeLoaded(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('test-token');
        Functions\when('wp_remote_post')->justReturn(new \WP_Error('http_error', 'Connection refused'));

        $result = AviaEventsIntegration::groupOptions();

        self::assertSame([AviaEventsIntegration::GROUPS_UNAVAILABLE_LABEL => ''], $result);
    }

    public function testToOptionsMakesDuplicateGroupNamesUnique(): void
    {
        $groups = [
            new GroupSummary('11111111-aaaa', 'Meditation'),
            new GroupSummary('22222222-bbbb', 'Meditation'),
        ];

        $result = AviaEventsIntegration::toOptions($groups);

        self::assertSame(
            ['Meditation' => '11111111-aaaa', 'Meditation (22222222)' => '22222222-bbbb'],
            $result,
        );
    }

    /**
     * @param array<int|string, mixed> $atts
     * @param array{showImages: bool, groupIds: list<string>} $expected
     */
    #[DataProvider('elementAttributesProvider')]
    public function testItMapsElementAttributesToBlockAttributes(array $atts, array $expected): void
    {
        $result = AviaEventsIntegration::toBlockAttributes($atts);

        self::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{array<int|string, mixed>, array{showImages: bool, groupIds: list<string>}}>
     */
    public static function elementAttributesProvider(): array
    {
        return [
            'groups as stored by the avia builder' => [
                [
                    'course_groups' => 'uuid-1,uuid-2',
                    'show_images' => 'yes',
                    'av_uid' => 'av-abc',
                    'sc_version' => '1.0',
                ],
                ['showImages' => true, 'groupIds' => ['uuid-1', 'uuid-2']],
            ],
            'images switched off' => [
                ['course_groups' => 'uuid-1', 'show_images' => 'no'],
                ['showImages' => false, 'groupIds' => ['uuid-1']],
            ],
            'no group selected' => [
                ['show_images' => 'yes'],
                ['showImages' => true, 'groupIds' => []],
            ],
            'show images missing defaults to yes' => [
                ['course_groups' => 'uuid-1'],
                ['showImages' => true, 'groupIds' => ['uuid-1']],
            ],
            'whitespace and empty entries are ignored' => [
                ['course_groups' => ' uuid-1 , ,uuid-2'],
                ['showImages' => true, 'groupIds' => ['uuid-1', 'uuid-2']],
            ],
        ];
    }
}
