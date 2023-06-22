<?php

namespace Doctrine\DBAL\Tests\Functional\Driver\PDO\CockroachDB;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tests\FunctionalTestCase;
use Doctrine\DBAL\Tests\TestUtil;

/** @requires extension pdo_pgsql */
class ConnectionTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        if (TestUtil::isDriverOneOf('pdo_crdb')) {
            return;
        }

        self::markTestSkipped('This test requires the pdo_crdb driver.');
    }

    /** @dataProvider getValidCharsets */
    public function testConnectsWithValidCharsetOption(string $charset): void
    {
        $params = $this->connection->getParams();
        $params['charset'] = $charset;

        $connection = DriverManager::getConnection(
            $params,
            $this->connection->getConfiguration(),
            $this->connection->getEventManager(),
        );

        self::assertEquals(
            $charset,
            $connection->fetchOne('SHOW client_encoding'),
        );
    }

    /** @return mixed[][] */
    public static function getValidCharsets(): iterable
    {
        return [
            ['UTF8'],
        ];
    }
}
