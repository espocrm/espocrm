<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\ORM\PDO;

use Espo\ORM\DatabaseParams;

use PDO;
use RuntimeException;

class DefaultPDOProvider implements PDOProvider
{
    private $databaseParams;

    /**
     * @var ?PDO
     */
    private $pdo = null;

    public function __construct(DatabaseParams $databaseParams)
    {
        $this->databaseParams = $databaseParams;
    }

    public function get(): PDO
    {
        if (!$this->pdo) {
            $this->intPDO();
        }

        return $this->pdo;
    }

    private function intPDO(): void
    {
        $platform = strtolower($this->databaseParams->getPlatform() ?? '');

        $host = $this->databaseParams->getHost();
        $port = $this->databaseParams->getPort();
        $dbname = $this->databaseParams->getName();
        $charset = $this->databaseParams->getCharset();
        $username = $this->databaseParams->getUsername();
        $password = $this->databaseParams->getPassword();

        if (!$platform) {
            throw new RuntimeException("No 'platform' parameter.");
        }

        if (!$host) {
            throw new RuntimeException("No 'host' parameter.");
        }

        $dsn = $platform . ':' . 'host=' . $host;

        if ($port) {
            $dsn .= ';' . 'port=' . (string) $port;
        }

        if ($dbname) {
            $dsn .= ';' . 'dbname=' . $dbname;
        }

        if ($charset) {
            $dsn .= ';' . 'charset=' . $charset;
        }

        $options = Options::getOptionsFromDatabaseParams($this->databaseParams);

        $this->pdo = new PDO($dsn, $username, $password, $options);

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}
