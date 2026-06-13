<?php
namespace NickGranados\RdsAuthBridge\Tests\Integration;

use NickGranados\RdsAuthBridge\Config;
use NickGranados\RdsAuthBridge\Connection;
use NickGranados\RdsAuthBridge\ExternalUserRepository;
use PHPUnit\Framework\TestCase;

final class ExternalUserRepositoryTest extends TestCase
{
    private function repo(): ExternalUserRepository
    {
        return new ExternalUserRepository(new Connection(Config::fromEnv()));
    }

    public function test_finds_a_seeded_user_by_email(): void
    {
        $user = $this->repo()->findByEmail('maria@taxplatform.com');

        $this->assertNotNull($user);
        $this->assertSame('maria@taxplatform.com', $user->email);
        $this->assertSame('Maria Silva', $user->fullName);
        $this->assertTrue(password_verify('TaxPass123!', $user->passwordHash));
    }

    public function test_returns_null_for_unknown_email(): void
    {
        $this->assertNull($this->repo()->findByEmail('ghost@nowhere.com'));
    }
}
