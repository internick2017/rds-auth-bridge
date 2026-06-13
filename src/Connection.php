<?php
namespace NickGranados\RdsAuthBridge;

use PDO;
use PDOException;

final class Connection
{
    public function __construct(private readonly Config $config) {}

    /** @throws DirectoryUnavailable */
    public function pdo(): PDO
    {
        try {
            return new PDO(
                $this->config->dsn(),
                $this->config->user,
                $this->config->password,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT            => 5,
                ]
            );
        } catch (PDOException $e) {
            error_log('[RDS Auth Bridge] connection failed: ' . $e->getMessage());
            throw new DirectoryUnavailable('Cannot connect to external user directory.', 0, $e);
        }
    }
}
