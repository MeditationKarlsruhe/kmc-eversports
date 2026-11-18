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
use Spatie\Snapshots\MatchesSnapshots;

/** @SuppressWarnings(PHPMD.TooManyPublicMethods) */
final class AviaEventsIntegrationTest extends TestCase
{
    use MatchesSnapshots;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg();
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

    public function testPopupElementsDoNotQueryEversportsOutsideTheEditWindowRequest(): void
    {
        Functions\expect('get_transient')->never();

        $result = AviaEventsIntegration::popupElements(['action' => 'heartbeat']);

        self::assertSame(AviaEventsIntegration::form([]), $result);
    }

    public function testPopupElementsListGroupsAndKeepStoredUnknownGroupsForTheEditWindow(): void
    {
        Functions\when('get_transient')->justReturn($this->groupsJson());
        $request = [
            'action' => AviaEventsIntegration::POPUP_AJAX_ACTION,
            'params' => [
                'shortcode' => "[av_eversports_events course_groups=\\'a-uuid,archived-uuid\\' show_images=\\'no\\']",
            ],
        ];

        $result = AviaEventsIntegration::popupElements($request);

        $this->assertMatchesJsonSnapshot($result);
    }

    public function testPopupElementsKeepStoredSelectionWhenGroupsCannotBeLoaded(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('test-token');
        Functions\when('wp_remote_post')->justReturn(new \WP_Error('http_error', 'Connection refused'));

        $result = AviaEventsIntegration::popupElements(['action' => AviaEventsIntegration::POPUP_AJAX_ACTION]);

        $this->assertMatchesJsonSnapshot($result);
    }

    public function testToOptionsEscapesGroupNames(): void
    {
        $groups = [new GroupSummary('a-uuid', "Yoga <Abend> & 'Ruhe'")];

        $result = AviaEventsIntegration::toOptions($groups, []);

        self::assertSame(['Yoga &lt;Abend&gt; &amp; &#039;Ruhe&#039;' => 'a-uuid'], $result);
    }

    public function testToOptionsMakesDuplicateGroupNamesUnique(): void
    {
        $groups = [
            new GroupSummary('11111111-aaaa', 'Meditation'),
            new GroupSummary('22222222-bbbb', 'Meditation '),
        ];

        $result = AviaEventsIntegration::toOptions($groups, []);

        self::assertSame(
            ['Meditation' => '11111111-aaaa', 'Meditation (22222222)' => '22222222-bbbb'],
            $result,
        );
    }

    public function testToOptionsAddsStoredGroupsThatAreNoLongerActive(): void
    {
        $groups = [new GroupSummary('11111111-aaaa', 'Meditation')];

        $result = AviaEventsIntegration::toOptions($groups, ['11111111-aaaa', '99999999-zzzz']);

        self::assertSame(
            ['Meditation' => '11111111-aaaa', 'Nicht mehr aktiver Kurs (99999999)' => '99999999-zzzz'],
            $result,
        );
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('storedShortcodeProvider')]
    public function testStoredGroupIdsReadsCourseGroupsFromShortcode(string $shortcode, array $expected): void
    {
        $result = AviaEventsIntegration::storedGroupIds($shortcode);

        self::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function storedShortcodeProvider(): array
    {
        return [
            'single quotes as written by the builder' => [
                "[av_eversports_events course_groups='uuid-1,uuid-2' show_images='yes']",
                ['uuid-1', 'uuid-2'],
            ],
            'double quotes' => [
                '[av_eversports_events course_groups="uuid-1" show_images="yes"]',
                ['uuid-1'],
            ],
            'no course groups attribute' => [
                "[av_eversports_events show_images='yes']",
                [],
            ],
            'new element without shortcode' => [
                '',
                [],
            ],
        ];
    }

    /**
     * @param array<string, string> $atts
     * @param array{showImages: bool, groupIds: list<string>} $expected
     */
    #[DataProvider('elementAttributesProvider')]
    public function testItMapsElementAttributesToBlockAttributes(array $atts, array $expected): void
    {
        $result = AviaEventsIntegration::toBlockAttributes($atts);

        self::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{array<string, string>, array{showImages: bool, groupIds: list<string>}}>
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

    private function groupsJson(): string
    {
        return json_encode([
            'data' => [
                'activityGroups' => [
                    'nodes' => [
                        ['id' => 'b-uuid', 'name' => 'Meditation am Abend '],
                        ['id' => 'a-uuid', 'name' => 'Arya Tara Puja'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }
}
