<?php
namespace NickGranados\RdsAuthBridge;

interface UserDirectory
{
    /**
     * @throws DirectoryUnavailable when the backing store can't be reached.
     * @return ExternalUser|null  null when no user matches the email.
     */
    public function findByEmail(string $email): ?ExternalUser;
}
