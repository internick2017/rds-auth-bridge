<?php
namespace NickGranados\RdsAuthBridge;

final class Plugin
{
    public static function boot(): void
    {
        // Priority 10 runs before WordPress's own check (priority 20).
        add_filter('authenticate', [self::class, 'authenticate'], 10, 3);
    }

    /**
     * @param  \WP_User|\WP_Error|null $user
     * @return \WP_User|\WP_Error|null
     */
    public static function authenticate($user, $username, $password)
    {
        try {
            $config = Config::fromEnv();
        } catch (\RuntimeException $e) {
            error_log('[RDS Auth Bridge] not configured: ' . $e->getMessage());
            return $user; // misconfigured -> never block WordPress's own login
        }

        $filter = new WpAuthFilter(
            new Authenticator(
                new ExternalUserRepository(new Connection($config))
            )
        );

        return $filter->handle($user, (string) $username, (string) $password);
    }
}
