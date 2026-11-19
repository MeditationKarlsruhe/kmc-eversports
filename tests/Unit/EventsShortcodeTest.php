<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Kmc\Eversports\EventsShortcode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EventsShortcodeTest extends TestCase
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

    public function testRegisterAddsInitHook(): void
    {
        Functions\expect('add_action')
            ->once()
            ->with('init', [EventsShortcode::class, 'registerShortcode']);

        EventsShortcode::register();

        $this->addToAssertionCount(1);
    }

    public function testRegisterShortcodeRegistersEversportsEventsTag(): void
    {
        Functions\expect('add_shortcode')
            ->once()
            ->with('kmc-eversports-events', [EventsShortcode::class, 'handle']);

        EventsShortcode::registerShortcode();

        $this->addToAssertionCount(1);
    }

    /**
     * @param array<int|string, string> $atts
     * @param array{showImages: bool, groupIds: list<string>} $expected
     */
    #[DataProvider('shortcodeAttributesProvider')]
    public function testItMapsShortcodeAttributesToBlockAttributes(array $atts, array $expected): void
    {
        $result = EventsShortcode::toBlockAttributes($atts);

        self::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{array<int|string, string>, array{showImages: bool, groupIds: list<string>}}>
     */
    public static function shortcodeAttributesProvider(): array
    {
        return [
            'single group id' => [
                ['group-ids' => 'grp-1'],
                ['showImages' => true, 'groupIds' => ['grp-1']],
            ],
            'comma separated group ids' => [
                ['group-ids' => 'grp-1,grp-2'],
                ['showImages' => true, 'groupIds' => ['grp-1', 'grp-2']],
            ],
            'whitespace and empty entries are ignored' => [
                ['group-ids' => ' grp-1 , ,grp-2, '],
                ['showImages' => true, 'groupIds' => ['grp-1', 'grp-2']],
            ],
            'missing group ids' => [
                [],
                ['showImages' => true, 'groupIds' => []],
            ],
            'show image false' => [
                ['group-ids' => 'grp-1', 'show-image' => 'false'],
                ['showImages' => false, 'groupIds' => ['grp-1']],
            ],
            'show image is case insensitive' => [
                ['group-ids' => 'grp-1', 'show-image' => ' TRUE '],
                ['showImages' => true, 'groupIds' => ['grp-1']],
            ],
            'show image other than true hides images' => [
                ['group-ids' => 'grp-1', 'show-image' => 'no'],
                ['showImages' => false, 'groupIds' => ['grp-1']],
            ],
        ];
    }

    public function testHandleReturnsEmptyStringWhenShortcodeHasNoAttributes(): void
    {
        $result = EventsShortcode::handle('');

        self::assertSame('', $result);
    }

    public function testHandleRendersSelectedGroups(): void
    {
        $json = json_encode([
            'data' => [
                'activities' => [
                    'nodes' => [
                        $this->makeActivityNode('grp-1', 'Group One'),
                        $this->makeActivityNode('grp-2', 'Group Two'),
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        Functions\when('get_transient')->justReturn($json);

        $result = EventsShortcode::handle(['group-ids' => 'grp-2']);

        self::assertStringContainsString('Group Two', $result);
        self::assertStringNotContainsString('Group One', $result);
    }

    /** @return array{
     *     id: string,
     *     start: string,
     *     end: string,
     *     detailsPageURL: string|null,
     *     activityGroup: array{
     *         id: string,
     *         name: string,
     *         description: array{html: string},
     *         images: array{nodes: list<mixed>}
     *     }
     * }
     */
    private function makeActivityNode(string $groupId, string $groupName): array
    {
        return [
            'id' => $groupId . '-activity',
            'start' => '2026-06-15T10:00:00+02:00',
            'end' => '2026-06-15T11:00:00+02:00',
            'detailsPageURL' => null,
            'activityGroup' => [
                'id' => $groupId,
                'name' => $groupName,
                'description' => ['html' => '<p>' . $groupName . '</p>'],
                'images' => ['nodes' => []],
            ],
        ];
    }
}
