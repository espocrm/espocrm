<?php

namespace Espo\Modules\Postgres\ORM\PDO;

use Espo\ORM\DatabaseParams;
use Espo\ORM\PDO\Options;
use Espo\ORM\PDO\PDOFactory;
use RuntimeException;
use PDO;

class PostgresqlPDOFactory implements PDOFactory
{
    public function create(DatabaseParams $databaseParams): PDO
    {
        $platform = strtolower($databaseParams->getPlatform() ?? '');

        $host = $databaseParams->getHost();
        $port = $databaseParams->getPort();
        $dbname = $databaseParams->getName();
        $charset = $databaseParams->getCharset();
        $username = $databaseParams->getUsername();
        $password = $databaseParams->getPassword();

        if (!$platform) {
            throw new RuntimeException("No 'platform' parameter.");
        }

        if (!$host) {
            throw new RuntimeException("No 'host' parameter.");
        }

        $dsn = "pgsql:host=$host";

        if ($port) {
            $dsn .= ";port=$port";
        }

        if ($dbname) {
            $dsn .= ";dbname=$dbname";
        }

        if ($charset) {
            $dsn .= ";options='--client_encoding=$charset'";
        }

        $options = Options::getOptionsFromDatabaseParams($databaseParams);

        $pdo = new PDO($dsn, $username, $password, $options);

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }
}
