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

namespace Espo\Core\Utils\Database\DetailsProviders;

use Espo\Core\Utils\Database\DetailsProvider;
use PDO;

class PostgresqlDetailsProvider implements DetailsProvider
{
    private const TYPE_POSTGRESQL = 'PostgreSQL';

    public function __construct(private PDO $pdo)
    {}

    public function getType(): string
    {
       return self::TYPE_POSTGRESQL;
    }

    public function getVersion(): string
    {
        $fullVersion = $this->getFullDatabaseVersion() ?? '';

        if (preg_match('/[0-9]+\.[0-9]+/', $fullVersion, $match)) {
            return $match[0];
        }

        return '0.0';
    }

    public function getServerVersion(): string
    {
        return (string) $this->getFullDatabaseVersion();
    }

    public function getParam(string $name): ?string
    {
        $name = preg_replace('/[^A-Za-z0-9_]+/', '', $name);

        $sql = "SHOW {$name}";

        $sth = $this->pdo->query($sql);

        if ($sth === false) {
            return null;
        }

        $row = $sth->fetch(PDO::FETCH_NUM);

        if ($row === false) {
            return null;
        }

        $value = $row[0] ?: null;

        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    private function getFullDatabaseVersion(): ?string
    {
        $sql = "select version()";

        $sth = $this->pdo->prepare($sql);
        $sth->execute();

        /** @var string|null|false $result */
        $result = $sth->fetchColumn();

        if ($result === false || $result === null) {
            return null;
        }

        return $result;
    }
}
