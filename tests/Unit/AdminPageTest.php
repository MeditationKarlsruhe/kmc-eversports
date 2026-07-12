<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Kmc\Eversports\AdminPage;
use PHPUnit\Framework\TestCase;

final class AdminPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        $_GET = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRegisterAddsHooks(): void
    {
        Functions\expect('add_action')->times(3);

        AdminPage::register();

        $this->addToAssertionCount(1);
    }

    public function testAddMenuPageRegistersOptionsPage(): void
    {
        Functions\expect('add_options_page')
            ->once()
            ->with('KMC Eversports', 'KMC Eversports', 'manage_options', 'kmc-eversports', \Mockery::type('array'));

        AdminPage::addMenuPage();

        $this->addToAssertionCount(1);
    }

    public function testRenderPageDiesWhenUnauthorized(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        Functions\expect('wp_die')
            ->once()
            ->with('Zugriff verweigert.')
            ->andThrow(new \RuntimeException('wp_die'));

        $this->expectException(\RuntimeException::class);

        AdminPage::renderPage();
    }

    public function testRenderPageShowsForms(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('get_option')->justReturn('');
        Functions\when('admin_url')->justReturn('http://example.com/wp-admin/admin-post.php');
        Functions\when('wp_nonce_field')->justReturn('');

        ob_start();
        AdminPage::renderPage();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('value="kmc_eversports_save"', $output);
        self::assertStringContainsString('value="kmc_eversports_clear_cache"', $output);
        self::assertStringContainsString('type="password"', $output);
    }

    public function testRenderPageShowsUpdatedNotice(): void
    {
        $_GET['kmc_updated'] = '1';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('get_option')->justReturn('');
        Functions\when('admin_url')->justReturn('http://example.com/wp-admin/admin-post.php');
        Functions\when('wp_nonce_field')->justReturn('');

        ob_start();
        AdminPage::renderPage();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('Einstellungen gespeichert', $output);
    }

    public function testRenderPageShowsCacheClearedNotice(): void
    {
        $_GET['kmc_cache_cleared'] = '1';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('get_option')->justReturn('');
        Functions\when('admin_url')->justReturn('http://example.com/wp-admin/admin-post.php');
        Functions\when('wp_nonce_field')->justReturn('');

        ob_start();
        AdminPage::renderPage();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('Cache geleert', $output);
    }

    public function testRenderPageShowsTokenSetDescriptionWhenTokenIsConfigured(): void
    {
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('get_option')->justReturn('some-token');
        Functions\when('admin_url')->justReturn('http://example.com/wp-admin/admin-post.php');
        Functions\when('wp_nonce_field')->justReturn('');

        ob_start();
        AdminPage::renderPage();
        $output = (string) ob_get_clean();

        self::assertStringContainsString('Token ist gespeichert', $output);
    }
}
