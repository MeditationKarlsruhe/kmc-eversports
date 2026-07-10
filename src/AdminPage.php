<?php

declare(strict_types=1);

namespace Kmc\Eversports;

final class AdminPage
{
    private const NONCE_SAVE  = 'kmc_eversports_save';
    private const NONCE_CLEAR = 'kmc_eversports_clear_cache';

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'addMenuPage']);
        add_action('admin_post_kmc_eversports_save', [self::class, 'handleSave']);
        add_action('admin_post_kmc_eversports_clear_cache', [self::class, 'handleClearCache']);
    }

    public static function addMenuPage(): void
    {
        add_options_page(
            'KMC Eversports',
            'KMC Eversports',
            'manage_options',
            'kmc-eversports',
            [self::class, 'renderPage'],
        );
    }

    public static function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Zugriff verweigert.');
        }

        $raw      = get_option(EversportsClient::OPTION_TOKEN, '');
        $token    = is_string($raw) ? $raw : '';
        $hasToken = $token !== '';

        ?>
        <div class="wrap">
            <h1>KMC Eversports</h1>

            <?php if (isset($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Einstellungen gespeichert.</p></div>
            <?php endif; ?>
            <?php if (isset($_GET['cache_cleared'])) : ?>
                <div class="notice notice-success is-dismissible"><p>Cache geleert.</p></div>
            <?php endif; ?>

            <h2>API-Token</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="kmc_eversports_save">
                <?php wp_nonce_field(self::NONCE_SAVE); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="kmc_eversports_api_token">Eversports API-Token</label></th>
                        <td>
                            <input
                                type="password"
                                id="kmc_eversports_api_token"
                                name="kmc_eversports_api_token"
                                value=""
                                placeholder="<?php echo esc_attr($hasToken ? '••••••••' : 'Token eingeben'); ?>"
                                class="regular-text"
                                autocomplete="new-password"
                            >
                            <?php if ($hasToken) : ?>
                                <p class="description">
                                    Token ist gespeichert. Neuen Wert eingeben, um ihn zu ersetzen.
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" class="button-primary" value="Speichern"></p>
            </form>

            <h2>Cache</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="kmc_eversports_clear_cache">
                <?php wp_nonce_field(self::NONCE_CLEAR); ?>
                <p>Löscht die zwischengespeicherten Eversports-Daten sofort.</p>
                <p><input type="submit" class="button" value="Cache leeren"></p>
            </form>
        </div>
        <?php
    }

    /** @SuppressWarnings(PHPMD.ExitExpression) */
    public static function handleSave(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Zugriff verweigert.');
        }

        check_admin_referer(self::NONCE_SAVE);

        $raw   = $_POST[EversportsClient::OPTION_TOKEN] ?? '';
        $token = sanitize_text_field(is_string($raw) ? $raw : '');
        if ($token !== '') {
            update_option(EversportsClient::OPTION_TOKEN, $token);
        }

        wp_safe_redirect(admin_url('options-general.php?page=kmc-eversports&updated=1'));
        exit;
    }

    /** @SuppressWarnings(PHPMD.ExitExpression) */
    public static function handleClearCache(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Zugriff verweigert.');
        }

        check_admin_referer(self::NONCE_CLEAR);
        delete_transient(EversportsClient::ACTIVITIES_TRANSIENT_KEY);
        wp_safe_redirect(admin_url('options-general.php?page=kmc-eversports&cache_cleared=1'));
        exit;
    }
}
