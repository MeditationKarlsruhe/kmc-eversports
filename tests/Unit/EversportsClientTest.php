<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Kmc\Eversports\EversportsClient;
use Mockery;
use PHPUnit\Framework\TestCase;

/** @SuppressWarnings(PHPMD.TooManyPublicMethods) */
final class EversportsClientTest extends TestCase
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

    public function testItReturnsCachedValue(): void
    {
        $cached = '{"data":{"activities":{"nodes":[]}}}';
        Functions\when('get_transient')->justReturn($cached);

        $result = EversportsClient::fetchActivities();

        self::assertSame($cached, $result);
    }

    public function testItFetchesAndCachesWhenCacheIsEmpty(): void
    {
        $node = ['id' => 'node-1'];
        $response = $this->makeHttpResponse(
            200,
            json_encode($this->makeApiResponse([$node], false), JSON_THROW_ON_ERROR),
        );

        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('test-token');
        Functions\expect('wp_remote_post')
            ->once()
            ->with(
                'https://provider-api.eversportsmanager.io/api/graphql',
                Mockery::on(function (mixed $args): bool {
                    /** @var array{headers: array{Content-Type: string, Authorization: string}} $args */
                    return $args['headers']['Content-Type'] === 'application/json'
                        && str_starts_with($args['headers']['Authorization'], 'Bearer ');
                }),
            )
            ->andReturn($response);
        Functions\expect('set_transient')
            ->once()
            ->with('kmc_eversports_activities', Mockery::type('string'), HOUR_IN_SECONDS)
            ->andReturn(true);

        $result = EversportsClient::fetchActivities();

        /** @var array{data: array{activities: array{nodes: list<mixed>}}} $decoded */
        $decoded = json_decode($result, true);
        self::assertSame([$node], $decoded['data']['activities']['nodes']);
    }

    public function testItMergesMultiplePagesIntoOneResult(): void
    {
        $response1 = $this->makeHttpResponse(
            200,
            json_encode($this->makeApiResponse([['id' => 'a']], true, 'cursor1'), JSON_THROW_ON_ERROR),
        );
        $response2 = $this->makeHttpResponse(
            200,
            json_encode($this->makeApiResponse([['id' => 'b']], false), JSON_THROW_ON_ERROR),
        );
        $call = 0;

        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('test-token');
        Functions\expect('wp_remote_post')
            ->twice()
            ->with(
                'https://provider-api.eversportsmanager.io/api/graphql',
                Mockery::type('array'),
            )
            ->andReturnUsing(function () use (&$call, $response1, $response2): array {
                return $call++ === 0 ? $response1 : $response2;
            });
        Functions\when('set_transient')->justReturn(true);

        $result = EversportsClient::fetchActivities();

        /** @var array{data: array{activities: array{nodes: list<mixed>}}} $decoded */
        $decoded = json_decode($result, true);
        self::assertSame([['id' => 'a'], ['id' => 'b']], $decoded['data']['activities']['nodes']);
    }

    public function testItThrowsOnWpError(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('test-token');
        Functions\when('wp_remote_post')->justReturn(new \WP_Error('http_error', 'Connection refused'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection refused');

        EversportsClient::fetchActivities();
    }

    public function testItThrowsOnNon200Response(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('test-token');
        Functions\when('wp_remote_post')->justReturn($this->makeHttpResponse(500, 'Internal Server Error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        EversportsClient::fetchActivities();
    }

    public function testItThrowsOnGraphQLErrors(): void
    {
        $body = json_encode(
            ['errors' => [['message' => 'first must not be greater than 50']]],
            JSON_THROW_ON_ERROR,
        );

        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('test-token');
        Functions\when('wp_remote_post')->justReturn($this->makeHttpResponse(200, $body));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('first must not be greater than 50');

        EversportsClient::fetchActivities();
    }

    public function testItThrowsWhenTokenIsNotConfigured(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Eversports API token is not configured');

        EversportsClient::fetchActivities();
    }

    public function testItReturnsCachedGroups(): void
    {
        $cached = '{"data":{"activityGroups":{"nodes":[]}}}';
        Functions\when('get_transient')->justReturn($cached);

        $result = EversportsClient::fetchGroups();

        self::assertSame($cached, $result);
    }

    public function testItFetchesAndCachesGroupsWhenCacheIsEmpty(): void
    {
        $node = ['id' => 'grp-1', 'name' => 'Group One'];
        $response = $this->makeHttpResponse(
            200,
            json_encode($this->makeGroupsApiResponse([$node], false), JSON_THROW_ON_ERROR),
        );

        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('test-token');
        Functions\expect('wp_remote_post')
            ->once()
            ->with(
                'https://provider-api.eversportsmanager.io/api/graphql',
                Mockery::type('array'),
            )
            ->andReturn($response);
        Functions\expect('set_transient')
            ->once()
            ->with('kmc_eversports_groups', Mockery::type('string'), HOUR_IN_SECONDS)
            ->andReturn(true);

        $result = EversportsClient::fetchGroups();

        /** @var array{data: array{activityGroups: array{nodes: list<mixed>}}} $decoded */
        $decoded = json_decode($result, true);
        self::assertSame([$node], $decoded['data']['activityGroups']['nodes']);
    }

    public function testItMergesMultipleGroupPagesIntoOneResult(): void
    {
        $response1 = $this->makeHttpResponse(
            200,
            json_encode($this->makeGroupsApiResponse([['id' => 'a']], true, 'cursor1'), JSON_THROW_ON_ERROR),
        );
        $response2 = $this->makeHttpResponse(
            200,
            json_encode($this->makeGroupsApiResponse([['id' => 'b']], false), JSON_THROW_ON_ERROR),
        );
        $call = 0;

        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('test-token');
        Functions\expect('wp_remote_post')
            ->twice()
            ->with(
                'https://provider-api.eversportsmanager.io/api/graphql',
                Mockery::type('array'),
            )
            ->andReturnUsing(function () use (&$call, $response1, $response2): array {
                return $call++ === 0 ? $response1 : $response2;
            });
        Functions\when('set_transient')->justReturn(true);

        $result = EversportsClient::fetchGroups();

        /** @var array{data: array{activityGroups: array{nodes: list<mixed>}}} $decoded */
        $decoded = json_decode($result, true);
        self::assertSame([['id' => 'a'], ['id' => 'b']], $decoded['data']['activityGroups']['nodes']);
    }

    public function testItThrowsOnWpErrorWhenFetchingGroups(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('get_option')->justReturn('test-token');
        Functions\when('wp_remote_post')->justReturn(new \WP_Error('http_error', 'Connection refused'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection refused');

        EversportsClient::fetchGroups();
    }

    /** @return array{response: array{code: int, message: string}, body: string} */
    private function makeHttpResponse(int $code, string $body): array
    {
        return ['response' => ['code' => $code, 'message' => 'OK'], 'body' => $body];
    }

    /**
     * @param list<mixed> $nodes
     * @return array{
     *     data: array{
     *         activities: array{
     *             pageInfo: array{hasNextPage: bool, endCursor: string|null},
     *             nodes: list<mixed>
     *         }
     *     }
     * }
     */
    private function makeApiResponse(array $nodes, bool $hasNextPage, ?string $endCursor = null): array
    {
        return [
            'data' => [
                'activities' => [
                    'pageInfo' => ['hasNextPage' => $hasNextPage, 'endCursor' => $endCursor],
                    'nodes' => $nodes,
                ],
            ],
        ];
    }

    /**
     * @param list<mixed> $nodes
     * @return array{
     *     data: array{
     *         activityGroups: array{
     *             pageInfo: array{hasNextPage: bool, endCursor: string|null},
     *             nodes: list<mixed>
     *         }
     *     }
     * }
     */
    private function makeGroupsApiResponse(array $nodes, bool $hasNextPage, ?string $endCursor = null): array
    {
        return [
            'data' => [
                'activityGroups' => [
                    'pageInfo' => ['hasNextPage' => $hasNextPage, 'endCursor' => $endCursor],
                    'nodes' => $nodes,
                ],
            ],
        ];
    }
}
