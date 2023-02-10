<?php

namespace Espo\Modules\Postgres\Core\Utils\Database\Dbal\Factories;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\PgSQL\Driver as PostgreSQLDriver;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\VersionAwarePlatformDriver as Driver;
use Espo\Core\Utils\Database\Dbal\ConnectionFactory;
use Espo\Modules\Postgres\Core\Utils\Database\Dbal\Platforms\PostgreSQLPlatform;
use Espo\ORM\DatabaseParams;
use Espo\ORM\PDO\Options as PdoOptions;
use PDO;

class PostgresqlConnectionFactory implements ConnectionFactory
{

    /**
     * @throws DBALException
     */
    public function create(DatabaseParams $databaseParams): Connection
    {
        $driver = $this->createDriver();

        $params = [
            'platform' => new PostgreSQLPlatform,
            'host' => $databaseParams->getHost(),
            'port' => $databaseParams->getPort(),
            'dbname' => $databaseParams->getName(),
            'charset' => $databaseParams->getCharset(),
            'user' => $databaseParams->getUsername(),
            'password' => $databaseParams->getPassword(),
            'driverOptions' => PdoOptions::getOptionsFromDatabaseParams($databaseParams),
        ];

        return new Connection($params, $driver);
    }

    private function createDriver(): Driver
    {
        $driverClass = PostgreSQLDriver::class;

        return new $driverClass();
    }

}
