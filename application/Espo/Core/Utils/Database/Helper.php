<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils\Database;

use Doctrine\DBAL\Connection as DbalConnection;

use Espo\Core\ORM\DatabaseParamsFactory;
use Espo\Core\ORM\PDO\PDOFactoryFactory;
use Espo\Core\Utils\Database\Dbal\ConnectionFactoryFactory as DBALConnectionFactoryFactory;
use Espo\ORM\DatabaseParams;

use PDO;
use RuntimeException;

class Helper
{
    private ?DbalConnection $dbalConnection = null;
    private ?PDO $pdo = null;

    public function __construct(
        private PDOFactoryFactory $pdoFactoryFactory,
        private DBALConnectionFactoryFactory $dbalConnectionFactoryFactory,
        private ConfigDataProvider $configDataProvider,
        private DetailsProviderFactory $detailsProviderFactory,
        private DatabaseParamsFactory $databaseParamsFactory
    ) {}

    public function getDbalConnection(): DbalConnection
    {
        if (!isset($this->dbalConnection)) {
            $this->dbalConnection = $this->createDbalConnection();
        }

        return $this->dbalConnection;
    }

    public function getPDO(): PDO
    {
        if (!isset($this->pdo)) {
            $this->pdo = $this->createPDO();
        }

        return $this->pdo;
    }

    /**
     * Clone with another PDO connection.
     */
    public function withPDO(PDO $pdo): self
    {
        $obj = clone $this;
        $obj->pdo = $pdo;
        $obj->dbalConnection = null;

        return $obj;
    }

    /**
     * Create a PDO connection.
     */
    public function createPDO(?DatabaseParams $params = null): PDO
    {
        $params = $params ?? $this->databaseParamsFactory->create();

        return $this->pdoFactoryFactory
            ->create($params->getPlatform() ?? '')
            ->create($params);
    }

    private function createDbalConnection(): DbalConnection
    {
        $params = $this->databaseParamsFactory->create();

        $platform = $params->getPlatform();

        if (!$platform) {
            throw new RuntimeException("No database platform.");
        }

        return $this->dbalConnectionFactoryFactory
            ->create($platform, $this->getPDO())
            ->create($params);
    }

    /**
     * Get a database type (MySQL, MariaDB, PostgreSQL).
     */
    public function getType(): string
    {
        return $this->createDetailsProvider()->getType();
    }

    /**
     * Get a database version.
     */
    public function getVersion(): string
    {
        return $this->createDetailsProvider()->getVersion();
    }

    /**
     * Get a database parameter.
     */
    public function getParam(string $name): ?string
    {
        return $this->createDetailsProvider()->getParam($name);
    }

    /**
     * Get a database server version string.
     */
    public function getServerVersion(): string
    {
        return $this->createDetailsProvider()->getServerVersion();
    }

    private function createDetailsProvider(): DetailsProvider
    {
        $platform = $this->configDataProvider->getPlatform();

        return $this->detailsProviderFactory->create($platform, $this->getPDO());
    }
}
