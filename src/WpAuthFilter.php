<?php
namespace NickGranados\RdsAuthBridge;

final class WpAuthFilter
{
    public function __construct(private readonly Authenticator $authenticator) {}

    /**
     * @param  \WP_User|\WP_Error|null $user
     * @return \WP_User|\WP_Error|null
     */
    public function handle($user, string $username, string $password)
    {
        // Respect a definitive result from an earlier-priority handler
        // (another auth/security plugin) — never override it.
        if ($user instanceof \WP_User || $user instanceof \WP_Error) {
            return $user;
        }

        $outcome = $this->authenticator->authenticate($username, $password);

        return match ($outcome->status) {
            'authenticated' => $this->syncWpUser($outcome->user),
            'invalid'       => $this->reject('rds_bad_credentials', $outcome->message),
            'unavailable'   => $this->reject('rds_unavailable', $outcome->message),
            default         => $user, // passthrough -> WordPress handles its own users
        };
    }

    /**
     * Authoritative rejection for a user that belongs to the external directory.
     *
     * We remove WordPress's default password handlers (priority 20) so they cannot
     * overwrite our error — without this, a synced user's wrong password (or an RDS
     * outage) would surface as WP's generic "incorrect password" instead of our
     * specific message. WordPress runs the `authenticate` filter once per login
     * request, so removing them here only affects this single login attempt.
     */
    private function reject(string $code, string $message): \WP_Error
    {
        remove_filter('authenticate', 'wp_authenticate_username_password', 20);
        remove_filter('authenticate', 'wp_authenticate_email_password', 20);
        return new \WP_Error($code, esc_html($message));
    }

    private function syncWpUser(ExternalUser $external): \WP_User|\WP_Error
    {
        $existing = get_user_by('email', $external->email);
        if ($existing instanceof \WP_User) {
            return $existing;
        }

        $newId = wp_insert_user([
            'user_login'   => $external->email,
            'user_email'   => $external->email,
            'display_name' => $external->fullName,
            'user_pass'    => wp_generate_password(32), // real auth is against RDS, not this
            'role'         => 'subscriber',
        ]);

        if (is_wp_error($newId)) {
            return $newId;
        }

        $wpUser = get_user_by('id', $newId);
        if (! $wpUser instanceof \WP_User) {
            return new \WP_Error('rds_sync_failed', esc_html('Authenticated, but the local account could not be created. Please try again.'));
        }

        return $wpUser;
    }
}
