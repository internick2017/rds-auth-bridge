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
        if ($user instanceof \WP_User) {
            return $user; // an earlier handler already authenticated
        }

        $outcome = $this->authenticator->authenticate($username, $password);

        return match ($outcome->status) {
            'authenticated' => $this->syncWpUser($outcome->user),
            'invalid'       => new \WP_Error('rds_bad_credentials', '<strong>Error:</strong> ' . esc_html($outcome->message)),
            'unavailable'   => new \WP_Error('rds_unavailable', esc_html($outcome->message)),
            default         => $user, // passthrough -> let WordPress continue
        };
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

        return get_user_by('id', $newId);
    }
}
