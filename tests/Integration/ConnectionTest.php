<?php
namespace NickGranados\RdsAuthBridge\Tests\Integration;

use NickGranados\RdsAuthBridge\Config;
use NickGranados\RdsAuthBridge\Connection;
use NickGranados\RdsAuthBridge\DirectoryUnavailable;
use PHPUnit\Framework\TestCase;
use PDO;

final class ConnectionTest extends TestCase
{
    public function test_opens_a_pdo_connection_to_postgres(): void
    {
        $pdo = (new Connection(Config::fromEnv()))->pdo();
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertEquals('1', $pdo->query('SELECT 1')->fetchColumn());
    }

    public function test_bad_host_throws_directory_unavailable(): void
    {
        $bad = new Config('no-such-host', 5432, 'taxplatform', 'taxapp', 'taxsecret', 'disable');
        $this->expectException(DirectoryUnavailable::class);
        (new Connection($bad))->pdo();
    }
}
