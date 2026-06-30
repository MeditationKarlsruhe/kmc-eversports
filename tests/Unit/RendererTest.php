<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Kmc\Eversports\Renderer;
use PHPUnit\Framework\TestCase;

final class RendererTest extends TestCase
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

    public function testItReturnsEmptyStringWhenClientThrows(): void
    {
        Functions\when('get_transient')->justReturn(false);
        Functions\when('wp_remote_post')->justReturn(new \WP_Error('http_error', 'Connection refused'));

        $result = Renderer::render(['showImages' => true]);

        self::assertSame('', $result);
    }
}
