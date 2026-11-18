<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Kmc\Eversports\AviaEventsIntegration;
use Kmc\Eversports\EversportsClient;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

final class KmcEversportsEventsAviaElementTest extends TestCase
{
    use MatchesSnapshots;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('__')->returnArg();
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItDefinesElementConfig(): void
    {
        $element = $this->createElement();

        $this->assertMatchesJsonSnapshot($element->config);
    }

    public function testItTakesPopupElementsFromTheCurrentRequest(): void
    {
        Functions\when('get_transient')->justReturn($this->groupsJson());
        $_POST = ['action' => AviaEventsIntegration::POPUP_AJAX_ACTION];
        $expected = AviaEventsIntegration::popupElements($_POST);

        $element = $this->createElement();

        self::assertSame($expected, $element->elements);
    }

    public function testShortcodeHandlerRendersSelectedGroups(): void
    {
        $element = $this->createElement();
        Functions\when('get_transient')->alias(
            fn (string $key): string|false => $key === EversportsClient::ACTIVITIES_TRANSIENT_KEY
                ? $this->activitiesJson()
                : false,
        );

        $result = $element->shortcode_handler(['course_groups' => 'grp-2', 'show_images' => 'yes']);

        self::assertStringContainsString('Group Two', $result);
        self::assertStringNotContainsString('Group One', $result);
    }

    public function testShortcodeHandlerRendersNothingWithoutAttributes(): void
    {
        $element = $this->createElement();

        $result = $element->shortcode_handler([]);

        self::assertSame('', $result);
    }

    private function createElement(): \KmcEversportsEventsAviaElement
    {
        $builder = new \AviaBuilder();

        return new \KmcEversportsEventsAviaElement($builder);
    }

    private function groupsJson(): string
    {
        return json_encode([
            'data' => [
                'activityGroups' => [
                    'nodes' => [
                        ['id' => 'grp-1', 'name' => 'Group One'],
                        ['id' => 'grp-2', 'name' => 'Group Two'],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function activitiesJson(): string
    {
        return json_encode([
            'data' => [
                'activities' => [
                    'nodes' => [
                        $this->makeActivityNode('grp-1', 'Group One'),
                        $this->makeActivityNode('grp-2', 'Group Two'),
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    /** @return array<string, mixed> */
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
