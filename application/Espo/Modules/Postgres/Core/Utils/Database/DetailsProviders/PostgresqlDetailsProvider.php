<?php

namespace Espo\Modules\Postgres\Core\Utils\Database\DetailsProviders;

use Espo\Core\Utils\Database\DetailsProvider;
use PDO;

class PostgresqlDetailsProvider implements DetailsProvider
{
    public const TYPE_POSTGRESQL = 'Postgresql';

    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function getType(): string
    {
        return self::TYPE_POSTGRESQL;
    }

    public function getVersion(): string
    {
        $serverVersion = $this->getServerVersion();

        if (preg_match('/^\d+\.\d+/', $serverVersion, $match)) {
            return $match[0];
        }

        return '0.0';
    }

    public function getServerVersion(): string
    {
        return $this->getParam('server_version');
    }

    public function getParam(string $name): ?string
    {
        $sth = $this
            ->pdo
            ->prepare("SHOW $name");

        return $sth->execute() ? $sth->fetchColumn() : null;
    }
}
