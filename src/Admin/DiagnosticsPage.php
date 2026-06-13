<?php
namespace NickGranados\RdsAuthBridge\Admin;

use NickGranados\RdsAuthBridge\Config;
use NickGranados\RdsAuthBridge\Connection;
use NickGranados\RdsAuthBridge\DirectoryUnavailable;

final class DiagnosticsPage
{
    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'addMenu']);
        add_action('admin_post_rds_auth_test', [self::class, 'handleTest']);
    }

    public static function addMenu(): void
    {
        add_options_page(
            'RDS Auth Bridge',
            'RDS Auth Bridge',
            'manage_options',
            'rds-auth-bridge',
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $result = get_transient('rds_auth_test_result');
        delete_transient('rds_auth_test_result');

        echo '<div class="wrap"><h1>RDS Auth Bridge</h1>';

        try {
            $config = Config::fromEnv();
            echo '<table class="form-table">';
            printf('<tr><th>Host</th><td>%s</td></tr>', esc_html($config->host));
            printf('<tr><th>Port</th><td>%d</td></tr>', (int) $config->port);
            printf('<tr><th>Database</th><td>%s</td></tr>', esc_html($config->dbname));
            printf('<tr><th>User</th><td>%s</td></tr>', esc_html($config->user));
            printf('<tr><th>Password</th><td>%s</td></tr>', esc_html(str_repeat('•', 8)));
            printf('<tr><th>SSL mode</th><td>%s</td></tr>', esc_html($config->sslmode));
            echo '</table>';

            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="rds_auth_test">';
            wp_nonce_field('rds_auth_test');
            submit_button('Test connection');
            echo '</form>';
        } catch (\RuntimeException $e) {
            printf('<div class="notice notice-error"><p>%s</p></div>', esc_html($e->getMessage()));
        }

        if ($result !== false) {
            $class = $result['ok'] ? 'notice-success' : 'notice-error';
            printf('<div class="notice %s"><p>%s</p></div>', esc_attr($class), esc_html($result['message']));
        }

        echo '</div>';
    }

    public static function handleTest(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Forbidden', 403);
        }
        check_admin_referer('rds_auth_test');

        try {
            (new Connection(Config::fromEnv()))->pdo()->query('SELECT 1');
            $result = ['ok' => true, 'message' => 'Connection successful.'];
        } catch (DirectoryUnavailable | \RuntimeException $e) {
            $result = ['ok' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }

        set_transient('rds_auth_test_result', $result, 30);
        wp_safe_redirect(admin_url('options-general.php?page=rds-auth-bridge'));
        exit;
    }
}
