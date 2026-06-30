<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Kmc\Eversports\EversportsClient;
use Mockery;
use PHPUnit\Framework\TestCase;

final class EversportsClientTest extends TestCase
{
    private string $secretsDir;
    private string $secretsFile;
    private bool $createdSecretsDir = false;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $this->secretsDir = dirname(__DIR__, 2) . '/.secrets';
        $this->secretsFile = $this->secretsDir . '/eversports-api.txt';
        if (!is_dir($this->secretsDir)) {
            mkdir($this->secretsDir, 0755, true);
            $this->createdSecretsDir = true;
        }
        file_put_contents($this->secretsFile, 'test-token');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->secretsFile)) {
            unlink($this->secretsFile);
        }
        if ($this->createdSecretsDir && is_dir($this->secretsDir)) {
            rmdir($this->secretsDir);
        }
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
            ->with('eversports_activities', Mockery::type('string'), HOUR_IN_SECONDS)
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
        Functions\when('wp_remote_post')->justReturn(new \WP_Error('http_error', 'Connection refused'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection refused');

        EversportsClient::fetchActivities();
    }

    public function testItThrowsOnNon200Response(): void
    {
        Functions\when('get_transient')->justReturn(false);
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
        Functions\when('wp_remote_post')->justReturn($this->makeHttpResponse(200, $body));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('first must not be greater than 50');

        EversportsClient::fetchActivities();
    }

    public function testItThrowsWhenSecretFileMissing(): void
    {
        unlink($this->secretsFile);

        Functions\when('get_transient')->justReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('.secrets/eversports-api.txt is missing or not readable.');

        EversportsClient::fetchActivities();
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
}
