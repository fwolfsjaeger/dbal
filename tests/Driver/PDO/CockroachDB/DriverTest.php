<?php

namespace Doctrine\DBAL\Tests\Driver\PDO\CockroachDB;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\API;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\PDO;
use Doctrine\DBAL\Driver\PDO\CockroachDB\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\CockroachDBPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\CockroachDBSchemaManager;
use Doctrine\DBAL\Tests\Driver\AbstractDriverTest;
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

        static::markTestSkipped('Test enabled only when using pdo_crdb specific phpunit.xml');
    }

    public function testConnection(): void
    {
        $connection = $this->connect();

        self::assertInstanceOf(PDO\Connection::class, $connection);
    }

    protected function createDriver(): DriverInterface
    {
        return new Driver();
    }

    protected function createPlatform(): AbstractPlatform
    {
        return new CockroachDBPlatform();
    }

    protected function createSchemaManager(\Doctrine\DBAL\Connection $connection): AbstractSchemaManager
    {
        return new CockroachDBSchemaManager(
            $connection,
            $this->createPlatform(),
        );
    }

    private function connect(): Connection
    {
        return $this->createDriver()->connect(TestUtil::getConnectionParams());
    }

    protected function createExceptionConverter(): API\ExceptionConverter
    {
        return new API\PostgreSQL\ExceptionConverter();
    }
}
