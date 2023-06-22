<?php

namespace Doctrine\DBAL\Tests\Functional\Driver\PDO\CockroachDB;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\CockroachDB\Driver;
use Doctrine\DBAL\Tests\Functional\Driver\AbstractDriverTest;
use Doctrine\DBAL\Tests\TestUtil;

/** @requires extension pdo_pgsql */
class DriverTest extends AbstractDriverTest
{
    protected function setUp(): void
    {
        parent::setUp();

        if (TestUtil::isDriverOneOf('pdo_crdb')) {
            return;
        }

        static::markTestSkipped('This test requires the pdo_crdb driver.');
    }

    /** @dataProvider getDatabaseParameter */
    public function testDatabaseParameters(
        ?string $databaseName,
        ?string $defaultDatabaseName,
        ?string $expectedDatabaseName
    ): void
    {
        $params = $this->connection->getParams();

        if ($databaseName !== null) {
            $params['dbname'] = $databaseName;
        } else {
            unset($params['dbname']);
        }

        if ($defaultDatabaseName !== null) {
            $params['default_dbname'] = $defaultDatabaseName;
        }

        $connection = new Connection(
            $params,
            $this->connection->getDriver(),
            $this->connection->getConfiguration(),
            $this->connection->getEventManager(),
        );

        self::assertSame(
            $expectedDatabaseName,
            $connection->getDatabase(),
        );
    }

    /** @return mixed[][] */
    public static function getDatabaseParameter(): iterable
    {
        $params = TestUtil::getConnectionParams();
        $realDatabaseName = $params['dbname'] ?? '';
        $dummyDatabaseName = $realDatabaseName . 'a';

        return [
            // dbname, default_dbname, expected
            [$realDatabaseName, null, $realDatabaseName],
            [$realDatabaseName, $dummyDatabaseName, $realDatabaseName],
            [null, $realDatabaseName, $realDatabaseName],
            [null, null, static::getDatabaseNameForConnectionWithoutDatabaseNameParameter()],
        ];
    }

    protected function createDriver(): Driver
    {
        return new Driver();
    }

    protected static function getDatabaseNameForConnectionWithoutDatabaseNameParameter(): ?string
    {
        return 'defaultdb';
    }
}
