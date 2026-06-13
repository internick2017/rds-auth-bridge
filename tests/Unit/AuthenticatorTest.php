<?php
namespace NickGranados\RdsAuthBridge\Tests\Unit;

use NickGranados\RdsAuthBridge\Authenticator;
use NickGranados\RdsAuthBridge\DirectoryUnavailable;
use NickGranados\RdsAuthBridge\ExternalUser;
use NickGranados\RdsAuthBridge\UserDirectory;
use PHPUnit\Framework\TestCase;

final class AuthenticatorTest extends TestCase
{
    private function directoryWith(?ExternalUser $user, bool $throws = false): UserDirectory
    {
        return new class($user, $throws) implements UserDirectory {
            public function __construct(private ?ExternalUser $user, private bool $throws) {}
            public function findByEmail(string $email): ?ExternalUser
            {
                if ($this->throws) {
                    throw new DirectoryUnavailable('down');
                }
                return $this->user;
            }
        };
    }

    private function userWithPassword(string $plain): ExternalUser
    {
        return new ExternalUser(1, 'maria@taxplatform.com', password_hash($plain, PASSWORD_BCRYPT), 'Maria Silva');
    }

    public function test_empty_credentials_pass_through(): void
    {
        $auth = new Authenticator($this->directoryWith(null));
        $this->assertSame('passthrough', $auth->authenticate('', '')->status);
    }

    public function test_directory_down_returns_unavailable(): void
    {
        $auth = new Authenticator($this->directoryWith(null, throws: true));
        $this->assertSame('unavailable', $auth->authenticate('maria@taxplatform.com', 'x')->status);
    }

    public function test_unknown_email_passes_through_to_wordpress(): void
    {
        $auth = new Authenticator($this->directoryWith(null));
        $this->assertSame('passthrough', $auth->authenticate('nobody@x.com', 'x')->status);
    }

    public function test_wrong_password_is_invalid(): void
    {
        $auth = new Authenticator($this->directoryWith($this->userWithPassword('TaxPass123!')));
        $this->assertSame('invalid', $auth->authenticate('maria@taxplatform.com', 'WRONG')->status);
    }

    public function test_correct_password_authenticates(): void
    {
        $user = $this->userWithPassword('TaxPass123!');
        $auth = new Authenticator($this->directoryWith($user));
        $outcome = $auth->authenticate('maria@taxplatform.com', 'TaxPass123!');
        $this->assertSame('authenticated', $outcome->status);
        $this->assertSame($user, $outcome->user);
    }
}
