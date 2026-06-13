<?php
namespace NickGranados\RdsAuthBridge\Tests\Unit;

use NickGranados\RdsAuthBridge\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    /** All env vars this class touches — saved/restored around every test. */
    private const ENV_KEYS = [
        'RDS_DB_HOST', 'RDS_DB_PORT', 'RDS_DB_NAME',
        'RDS_DB_USER', 'RDS_DB_PASSWORD', 'RDS_DB_SSLMODE',
    ];

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    protected function setUp(): void
    {
        // Save original values, then clear so tests start from a clean slate.
        foreach (self::ENV_KEYS as $k) {
            $this->savedEnv[$k] = getenv($k);
            putenv($k);
        }
    }

    private function setEnv(array $vars): void
    {
        foreach ($vars as $k => $v) { putenv("$k=$v"); }
    }

    protected function tearDown(): void
    {
        // Restore original values so later test suites (e.g. integration) keep
        // the env vars that were set by the Docker Compose service definition.
        foreach (self::ENV_KEYS as $k) {
            $original = $this->savedEnv[$k];
            if ($original === false || $original === '') {
                putenv($k);
            } else {
                putenv("$k=$original");
            }
        }
    }

    public function test_builds_dsn_from_env(): void
    {
        $this->setEnv([
            'RDS_DB_HOST' => 'db.example.com',
            'RDS_DB_NAME' => 'taxplatform',
            'RDS_DB_USER' => 'taxapp',
            'RDS_DB_PASSWORD' => 'secret',
        ]);

        $config = Config::fromEnv();

        $this->assertSame(
            'pgsql:host=db.example.com;port=5432;dbname=taxplatform;sslmode=require',
            $config->dsn()
        );
        $this->assertSame('taxapp', $config->user);
        $this->assertSame('secret', $config->password);
    }

    public function test_port_and_sslmode_are_overridable(): void
    {
        $this->setEnv([
            'RDS_DB_HOST' => 'rds', 'RDS_DB_NAME' => 'taxplatform',
            'RDS_DB_USER' => 'taxapp', 'RDS_DB_PASSWORD' => 'secret',
            'RDS_DB_PORT' => '15432', 'RDS_DB_SSLMODE' => 'disable',
        ]);

        $this->assertSame(
            'pgsql:host=rds;port=15432;dbname=taxplatform;sslmode=disable',
            Config::fromEnv()->dsn()
        );
    }

    public function test_missing_required_var_throws(): void
    {
        // setUp() already cleared all vars; set only HOST — NAME/USER/PASSWORD remain missing.
        $this->setEnv(['RDS_DB_HOST' => 'rds']);
        $this->expectException(\RuntimeException::class);
        Config::fromEnv();
    }
}
