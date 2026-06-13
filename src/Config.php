<?php
namespace NickGranados\RdsAuthBridge;

final class Config
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $dbname,
        public readonly string $user,
        public readonly string $password,
        public readonly string $sslmode,
    ) {}

    public static function fromEnv(): self
    {
        return new self(
            host: self::requireEnv('RDS_DB_HOST'),
            port: (int) (self::optional('RDS_DB_PORT') ?? '5432'),
            dbname: self::requireEnv('RDS_DB_NAME'),
            user: self::requireEnv('RDS_DB_USER'),
            password: self::requireEnv('RDS_DB_PASSWORD'),
            sslmode: self::optional('RDS_DB_SSLMODE') ?? 'require',
        );
    }

    public function dsn(): string
    {
        return sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $this->host, $this->port, $this->dbname, $this->sslmode
        );
    }

    private static function requireEnv(string $key): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            throw new \RuntimeException("Missing required env var: {$key}");
        }
        return $value;
    }

    private static function optional(string $key): ?string
    {
        $value = getenv($key);
        return ($value === false || $value === '') ? null : $value;
    }
}
