<?php
namespace NickGranados\RdsAuthBridge;

final class AuthOutcome
{
    private function __construct(
        public readonly string $status,            // passthrough|invalid|unavailable|authenticated
        public readonly ?ExternalUser $user = null,
        public readonly string $message = '',
    ) {}

    public static function passthrough(): self
    {
        return new self('passthrough');
    }

    public static function invalid(string $message = 'Incorrect email or password.'): self
    {
        return new self('invalid', null, $message);
    }

    public static function unavailable(
        string $message = 'Authentication service is temporarily unavailable. Please try again later.'
    ): self {
        return new self('unavailable', null, $message);
    }

    public static function authenticated(ExternalUser $user): self
    {
        return new self('authenticated', $user);
    }
}
