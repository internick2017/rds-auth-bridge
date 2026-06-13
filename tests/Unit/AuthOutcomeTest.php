<?php
namespace NickGranados\RdsAuthBridge\Tests\Unit;

use NickGranados\RdsAuthBridge\AuthOutcome;
use NickGranados\RdsAuthBridge\ExternalUser;
use PHPUnit\Framework\TestCase;

final class AuthOutcomeTest extends TestCase
{
    public function test_passthrough(): void
    {
        $this->assertSame('passthrough', AuthOutcome::passthrough()->status);
    }

    public function test_invalid_has_default_message(): void
    {
        $outcome = AuthOutcome::invalid();
        $this->assertSame('invalid', $outcome->status);
        $this->assertNotSame('', $outcome->message);
    }

    public function test_unavailable_has_default_message(): void
    {
        $this->assertSame('unavailable', AuthOutcome::unavailable()->status);
    }

    public function test_authenticated_carries_the_user(): void
    {
        $user = new ExternalUser(1, 'a@b.com', 'hash', 'A B');
        $outcome = AuthOutcome::authenticated($user);
        $this->assertSame('authenticated', $outcome->status);
        $this->assertSame($user, $outcome->user);
    }
}
