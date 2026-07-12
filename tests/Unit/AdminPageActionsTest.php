<?php

declare(strict_types=1);

namespace Kmc\Eversports\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Kmc\Eversports\AdminPage;
use Kmc\Eversports\EversportsClient;
use PHPUnit\Framework\TestCase;

final class AdminPageActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        $_POST = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testHandleSaveDiesWhenUnauthorized(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        Functions\expect('wp_die')
            ->once()
            ->with('Zugriff verweigert.')
            ->andThrow(new \RuntimeException('wp_die'));

        $this->expectException(\RuntimeException::class);

        AdminPage::handleSave();
    }

    public function testHandleSaveSavesTokenAndRedirects(): void
    {
        $_POST[EversportsClient::OPTION_TOKEN] = 'my-new-token';
        $redirectUrl = 'http://example.com/wp-admin/options-general.php?page=kmc-eversports&updated=1';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_admin_referer')->justReturn(1);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('admin_url')->justReturn($redirectUrl);
        Functions\expect('update_option')
            ->once()
            ->with(EversportsClient::OPTION_TOKEN, 'my-new-token');
        Functions\expect('wp_safe_redirect')
            ->once()
            ->andThrow(new \RuntimeException('redirect'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('redirect');

        AdminPage::handleSave();
    }

    public function testHandleSaveSkipsUpdateWhenTokenIsEmpty(): void
    {
        $_POST[EversportsClient::OPTION_TOKEN] = '';
        $redirectUrl = 'http://example.com/wp-admin/options-general.php?page=kmc-eversports&updated=1';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_admin_referer')->justReturn(1);
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('admin_url')->justReturn($redirectUrl);
        Functions\expect('update_option')->never();
        Functions\expect('wp_safe_redirect')
            ->once()
            ->andThrow(new \RuntimeException('redirect'));

        $this->expectException(\RuntimeException::class);

        AdminPage::handleSave();
    }

    public function testHandleClearCacheDiesWhenUnauthorized(): void
    {
        Functions\when('current_user_can')->justReturn(false);
        Functions\expect('wp_die')
            ->once()
            ->with('Zugriff verweigert.')
            ->andThrow(new \RuntimeException('wp_die'));

        $this->expectException(\RuntimeException::class);

        AdminPage::handleClearCache();
    }

    public function testHandleClearCacheDeletesTransientAndRedirects(): void
    {
        $redirectUrl = 'http://example.com/wp-admin/options-general.php?page=kmc-eversports&cache_cleared=1';

        Functions\when('current_user_can')->justReturn(true);
        Functions\when('check_admin_referer')->justReturn(1);
        Functions\when('admin_url')->justReturn($redirectUrl);
        Functions\expect('delete_transient')
            ->once()
            ->with(EversportsClient::ACTIVITIES_TRANSIENT_KEY);
        Functions\expect('delete_transient')
            ->once()
            ->with(EversportsClient::GROUPS_TRANSIENT_KEY);
        Functions\expect('wp_safe_redirect')
            ->once()
            ->andThrow(new \RuntimeException('redirect'));

        $this->expectException(\RuntimeException::class);

        AdminPage::handleClearCache();
    }
}
