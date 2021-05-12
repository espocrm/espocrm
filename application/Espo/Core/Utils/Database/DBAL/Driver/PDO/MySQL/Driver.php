<?php

namespace Espo\Core\Utils\Database\DBAL\Driver\PDO\MySQL;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\PDO\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PDO;

// Espo: requires for the issue https://github.com/doctrine/dbal/issues/4496
use Espo\Core\Utils\Database\DBAL\Schema\MySQLSchemaManager;
use Doctrine\DBAL\Connection as MySQLDriverConnection;

// End: espo

final class Driver extends AbstractMySQLDriver
{
    /**
     * {@inheritdoc}
     *
     * @return Connection
     */
    public function connect(array $params)
    {
        $driverOptions = $params['driverOptions'] ?? [];

        if (! empty($params['persistent'])) {
            $driverOptions[PDO::ATTR_PERSISTENT] = true;
        }

        return new Connection(
            $this->constructPdoDsn($params),
            $params['user'] ?? '',
            $params['password'] ?? '',
            $driverOptions
        );
    }

    /**
     * Constructs the MySQL PDO DSN.
     *
     * @param mixed[] $params
     *
     * @return string The DSN.
     */
    protected function constructPdoDsn(array $params)
    {
        $dsn = 'mysql:';
        if (isset($params['host']) && $params['host'] !== '') {
            $dsn .= 'host=' . $params['host'] . ';';
        }

        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }

        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }

        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }

        if (isset($params['charset'])) {
            $dsn .= 'charset=' . $params['charset'] . ';';
        }

        return $dsn;
    }

    // Espo: requires for the issue https://github.com/doctrine/dbal/issues/4496
    public function getSchemaManager(MySQLDriverConnection $conn, AbstractPlatform $platform)
    {
        return new MySQLSchemaManager($conn, $platform);
    }
    // End: espo
}
