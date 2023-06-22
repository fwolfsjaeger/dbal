<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Driver\PDO\CockroachDB;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\API\ExceptionConverter as ExceptionConverterInterface;
use Doctrine\DBAL\Driver\API\PostgreSQL\ExceptionConverter;
use Doctrine\DBAL\Driver\PDO\Connection;
use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\CockroachDBPlatform;
use Doctrine\DBAL\Schema\CockroachDBSchemaManager;
use Doctrine\Deprecations\Deprecation;
use PDO;
use PDOException;
use SensitiveParameter;

final class Driver implements DriverInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(
        #[SensitiveParameter]
        array $params
    ): Connection {
        $driverOptions = $params['driverOptions'] ?? [];

        if (!empty($params['persistent'])) {
            $driverOptions[PDO::ATTR_PERSISTENT] = true;
        }

        // ensure the database name is set
        $params['dbname'] = $params['dbname'] ?? $params['default_dbname'] ?? 'defaultdb';

        $safeParams = $params;
        unset($safeParams['password']);

        try {
            $pdo = new PDO(
                $this->constructPdoDsn($safeParams),
                $params['user'] ?? '',
                $params['password'] ?? '',
                $driverOptions,
            );
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }

        if (
            !isset($driverOptions[PDO::PGSQL_ATTR_DISABLE_PREPARES])
            || $driverOptions[PDO::PGSQL_ATTR_DISABLE_PREPARES] === true
        ) {
            $pdo->setAttribute(PDO::PGSQL_ATTR_DISABLE_PREPARES, true);
        }

        $connection = new Connection($pdo);

        /*
         * define client_encoding via SET NAMES to avoid inconsistent DSN support
         * - passing client_encoding via the 'options' param breaks pgbouncer support
         */
        if (isset($params['charset'])) {
            $connection->exec('SET NAMES \'' . $params['charset'] . '\'');
        }

        return $connection;
    }

    /**
     * Constructs the CockroachDB PDO DSN.
     *
     * @param array<string, mixed> $params
     */
    private function constructPdoDsn(array $params): string
    {
        $dsn = 'pgsql:';

        if (isset($params['host']) && $params['host'] !== '') {
            $dsn .= 'host=' . $params['host'] . ';';
        }

        if (isset($params['port']) && $params['port'] !== '') {
            $dsn .= 'port=' . $params['port'] . ';';
        }

        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        } elseif (isset($params['default_dbname'])) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5705',
                'The "default_dbname" connection parameter is deprecated. Use "dbname" instead.',
            );

            $dsn .= 'dbname=' . $params['default_dbname'] . ';';
        } else {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5705',
                'Relying on the DBAL connecting to the default database by default is deprecated.'
                . ' Unless you want to have the server determine the default database for the connection,'
                . ' specify the database name explicitly.',
            );

            // Used for temporary connections to allow operations like dropping the database currently connected to.
            $dsn .= 'dbname=defaultdb;';
        }

        if (isset($params['sslmode'])) {
            $dsn .= 'sslmode=' . $params['sslmode'] . ';';
        }

        if (isset($params['sslrootcert'])) {
            $dsn .= 'sslrootcert=' . $params['sslrootcert'] . ';';
        }

        if (isset($params['sslcert'])) {
            $dsn .= 'sslcert=' . $params['sslcert'] . ';';
        }

        if (isset($params['sslkey'])) {
            $dsn .= 'sslkey=' . $params['sslkey'] . ';';
        }

        if (isset($params['sslcrl'])) {
            $dsn .= 'sslcrl=' . $params['sslcrl'] . ';';
        }

        if (isset($params['application_name'])) {
            $dsn .= 'application_name=' . $params['application_name'] . ';';
        }

        return $dsn;
    }

    /**
     * {@inheritDoc}
     */
    public function getDatabasePlatform()
    {
        return new CockroachDBPlatform();
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated Use {@link CockroachDBSchemaManager::createSchemaManager()} instead.
     */
    public function getSchemaManager(\Doctrine\DBAL\Connection $conn, AbstractPlatform $platform)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5458',
            'AbstractPostgreSQLDriver::getSchemaManager() is deprecated.'
            . ' Use CockroachDBSchemaManager::createSchemaManager() instead.',
        );

        assert($platform instanceof CockroachDBPlatform);

        return new CockroachDBSchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverterInterface
    {
        return new ExceptionConverter();
    }
}
