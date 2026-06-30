<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Kmc\Eversports\EversportsClient;
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

        $result = (new EversportsClient())->fetchActivities();

        self::assertSame($cached, $result);
    }

    public function testItFetchesAndCachesWhenCacheIsEmpty(): void
    {
        $node = ['id' => 'node-1'];

        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_post')->justReturn([]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn(
            json_encode($this->makeApiResponse([$node], false), JSON_THROW_ON_ERROR),
        );
        Functions\when('set_transient')->justReturn(true);

        $result = (new EversportsClient())->fetchActivities();

        /** @var array{data: array{activities: array{nodes: list<mixed>}}} $decoded */
        $decoded = json_decode($result, true);
        self::assertSame([$node], $decoded['data']['activities']['nodes']);
    }

    public function testItMergesMultiplePagesIntoOneResult(): void
    {
        $page1 = json_encode($this->makeApiResponse([['id' => 'a']], true, 'cursor1'), JSON_THROW_ON_ERROR);
        $page2 = json_encode($this->makeApiResponse([['id' => 'b']], false), JSON_THROW_ON_ERROR);
        $call = 0;

        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_post')->justReturn([]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->alias(
            function () use (&$call, $page1, $page2): string {
                return $call++ === 0 ? $page1 : $page2;
            },
        );
        Functions\when('set_transient')->justReturn(true);

        $result = (new EversportsClient())->fetchActivities();

        /** @var array{data: array{activities: array{nodes: list<mixed>}}} $decoded */
        $decoded = json_decode($result, true);
        self::assertSame([['id' => 'a'], ['id' => 'b']], $decoded['data']['activities']['nodes']);
    }

    public function testItThrowsOnWpError(): void
    {
        $wpError = new class {
            // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            public function get_error_message(): string
            {
                return 'Connection refused';
            }
        };

        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_post')->justReturn($wpError);
        Functions\when('is_wp_error')->justReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection refused');

        (new EversportsClient())->fetchActivities();
    }

    public function testItThrowsOnNon200Response(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_post')->justReturn([]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(500);
        Functions\when('wp_remote_retrieve_body')->justReturn('Internal Server Error');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');

        (new EversportsClient())->fetchActivities();
    }

    public function testItThrowsOnGraphQLErrors(): void
    {
        $body = json_encode(['errors' => [['message' => 'first must not be greater than 50']]], JSON_THROW_ON_ERROR);

        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_post')->justReturn([]);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('wp_remote_retrieve_response_code')->justReturn(200);
        Functions\when('wp_remote_retrieve_body')->justReturn($body);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('first must not be greater than 50');

        (new EversportsClient())->fetchActivities();
    }

    public function testItThrowsWhenSecretFileMissing(): void
    {
        unlink($this->secretsFile);

        Functions\when('get_transient')->justReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('.secrets/eversports-api.txt is missing.');

        (new EversportsClient())->fetchActivities();
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
