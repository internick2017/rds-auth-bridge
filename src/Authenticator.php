<?php
namespace NickGranados\RdsAuthBridge;

final class Authenticator
{
    public function __construct(private readonly UserDirectory $directory) {}

    public function authenticate(string $email, string $password): AuthOutcome
    {
        if ($email === '' || $password === '') {
            return AuthOutcome::passthrough();
        }

        try {
            $user = $this->directory->findByEmail($email);
        } catch (DirectoryUnavailable) {
            return AuthOutcome::unavailable();
        }

        if ($user === null) {
            return AuthOutcome::passthrough(); // not our user — let WordPress try wp_users
        }

        if (! password_verify($password, $user->passwordHash)) {
            return AuthOutcome::invalid();
        }

        return AuthOutcome::authenticated($user);
    }
}
