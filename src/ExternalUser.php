<?php
namespace NickGranados\RdsAuthBridge;

final class ExternalUser
{
    public function __construct(
        public readonly int $externalId,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $fullName,
    ) {}
}
