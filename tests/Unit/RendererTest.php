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

        $result = Renderer::render(['showImages' => true]);

        self::assertSame('', $result);
    }
}
