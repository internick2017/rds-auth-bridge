<?php
namespace NickGranados\RdsAuthBridge;

use PDOException;

final class ExternalUserRepository implements UserDirectory
{
    public function __construct(private readonly Connection $connection) {}

    public function findByEmail(string $email): ?ExternalUser
    {
        try {
            $pdo  = $this->connection->pdo();
            $stmt = $pdo->prepare(
                'SELECT id, email, password_hash, full_name FROM clients WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $row = $stmt->fetch();
        } catch (PDOException $e) {
            error_log('[RDS Auth Bridge] query failed: ' . $e->getMessage());
            throw new DirectoryUnavailable('Query against external directory failed.', 0, $e);
        }

        if ($row === false) {
            return null;
        }

        return new ExternalUser(
            externalId:   (int) $row['id'],
            email:        $row['email'],
            passwordHash: $row['password_hash'],
            fullName:     $row['full_name'],
        );
    }
}
