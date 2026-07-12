<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Kmc\Eversports\GroupsEndpoint;
use Mockery;
use PHPUnit\Framework\TestCase;

final class GroupsEndpointTest extends TestCase
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

    public function testRegisterAddsRestApiInitHook(): void
    {
        Functions\expect('add_action')
            ->once()
            ->with('rest_api_init', [GroupsEndpoint::class, 'registerRoute']);

        GroupsEndpoint::register();

        $this->addToAssertionCount(1);
    }

    public function testRegisterRouteRegistersGroupsRoute(): void
    {
        Functions\expect('register_rest_route')
            ->once()
            ->with(
                'kmc-eversports/v1',
                '/groups',
                Mockery::on(function (mixed $args): bool {
                    /** @var array{methods: string, callback: mixed, permission_callback: mixed} $args */
                    return $args['methods'] === 'GET'
                        && $args['callback'] === [GroupsEndpoint::class, 'handle']
                        && $args['permission_callback'] === '__return_true';
                }),
            );

        GroupsEndpoint::registerRoute();

        $this->addToAssertionCount(1);
    }

    public function testHandleReturnsGroupsFromCache(): void
    {
        $cached = json_encode([
            'data' => ['activityGroups' => ['nodes' => [
                ['id' => 'g1', 'name' => 'Group One'],
                ['id' => 'g2', 'name' => 'Group Two'],
            ]]],
        ], JSON_THROW_ON_ERROR);
        Functions\when('get_transient')->justReturn($cached);

        $response = GroupsEndpoint::handle();

        self::assertSame(200, $response->get_status());
        self::assertSame(
            [
                ['id' => 'g1', 'name' => 'Group One'],
                ['id' => 'g2', 'name' => 'Group Two'],
            ],
            $response->get_data(),
        );
    }

    public function testHandleReturns500WhenClientThrows(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('test-token');
        Functions\when('wp_remote_post')->justReturn(new \WP_Error('http_error', 'Connection refused'));

        $response = GroupsEndpoint::handle();

        self::assertSame(500, $response->get_status());
    }
}
