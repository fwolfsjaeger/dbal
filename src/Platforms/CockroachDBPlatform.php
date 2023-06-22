<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Platforms;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\CockroachDBSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\Deprecations\Deprecation;

class CockroachDBPlatform extends PostgreSQLPlatform
{
    protected function initializeDoctrineTypeMappings(): void
    {
        parent::initializeDoctrineTypeMappings();
        $this->doctrineTypeMapping['int2vector'] = 'array';
    }

    public function getIntegerTypeDeclarationSQL(array $column): string
    {
        if (!empty($column['autoincrement'])) {
            return 'SERIAL4';
        }

        return 'INT4';
    }

    public function getBigIntTypeDeclarationSQL(array $column): string
    {
        if (!empty($column['autoincrement'])) {
            return 'SERIAL8';
        }

        return 'INT8';
    }

    public function getSmallIntTypeDeclarationSQL(array $column): string
    {
        if (!empty($column['autoincrement'])) {
            return 'SERIAL2';
        }

        return 'INT2';
    }

    public function getName()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/issues/4749',
            'CockroachDBPlatform::getName() is deprecated. Identify platforms by their class.',
        );

        return 'pdo_crdb';
    }

    public function getReadLockSQL(): string
    {
        return $this->getForUpdateSQL();
    }

    /**
     * DEFERRABLE, DEFERRED and IMMEDIATE are not supported
     *
     * @link https://github.com/cockroachdb/cockroach/issues/31632
     * @link https://www.cockroachlabs.com/docs/v23.1/foreign-key#foreign-key-actions
     */
    public function getAdvancedForeignKeyOptionsSQL(ForeignKeyConstraint $foreignKey): string
    {
        $query = '';

        if ($foreignKey->hasOption('match')) {
            $query .= ' MATCH ' . $foreignKey->getOption('match');
        }

        if ($foreignKey->hasOption('onUpdate')) {
            $query .= ' ON UPDATE ' . $this->getForeignKeyReferentialActionSQL($foreignKey->getOption('onUpdate'));
        }

        if ($foreignKey->hasOption('onDelete')) {
            $query .= ' ON DELETE ' . $this->getForeignKeyReferentialActionSQL($foreignKey->getOption('onDelete'));
        }

        return $query;
    }

    public function createSchemaManager(Connection $connection): CockroachDBSchemaManager
    {
        return new CockroachDBSchemaManager($connection, $this);
    }
}
