<?php

namespace Doctrine\DBAL\Tests\Functional\Schema\CockroachDB;

use Doctrine\DBAL\Platforms\CockroachDBPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Tests\FunctionalTestCase;
use Doctrine\DBAL\Types\IntegerType;

final class SchemaTest extends FunctionalTestCase
{
    public function testCreateTableWithSequenceInColumnDefinition(): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if (!$platform instanceof CockroachDBPlatform) {
            self::markTestSkipped('Test is for CockroachDB.');
        }

        $this->dropTableIfExists('my_table');

        $options = ['default' => 'nextval(\'my_table_id_seq\'::regclass)'];
        $table = new Table('my_table', [new Column('id', new IntegerType(), $options)]);
        $sequence = new Sequence('my_table_id_seq');

        $schema = new Schema([$table], [$sequence]);
        foreach ($schema->toSql($platform) as $sql) {
            $this->connection->executeStatement($sql);
        }

        $result = $this->connection->fetchAssociative(
            'SELECT column_default FROM information_schema.columns WHERE table_name = ?',
            ['my_table'],
        );

        $this->assertNotFalse($result);
        $this->assertEquals('nextval(\'public.my_table_id_seq\'::REGCLASS)', $result['column_default']);
    }
}
