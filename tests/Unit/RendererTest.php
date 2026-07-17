<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Kmc\Eversports\Renderer;
use PHPUnit\Framework\TestCase;

final class RendererTest extends TestCase
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

    public function testItReturnsEmptyStringWhenClientThrows(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('test-token');
        Functions\when('wp_remote_post')->justReturn(new \WP_Error('http_error', 'Connection refused'));

        $result = Renderer::render(['showImages' => true, 'groupIds' => ['grp-1']]);

        self::assertSame('', $result);
    }

    public function testItReturnsEmptyStringWhenNoGroupIsSelected(): void
    {
        $result = Renderer::render(['showImages' => true, 'groupIds' => []]);

        self::assertSame('', $result);
    }

    public function testItReturnsEmptyStringWhenGroupIdsIsMissing(): void
    {
        $result = Renderer::render(['showImages' => true]);

        self::assertSame('', $result);
    }

    public function testItFiltersGroupsBySelectedGroupIds(): void
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

        $result = Renderer::render(['showImages' => true, 'groupIds' => ['grp-2']]);

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
