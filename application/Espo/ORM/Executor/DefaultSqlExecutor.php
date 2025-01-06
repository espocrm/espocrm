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

namespace Espo\ORM\Executor;

use Espo\ORM\PDO\PDOProvider;
use Psr\Log\LoggerInterface;

use PDO;
use PDOStatement;
use PDOException;
use Exception;
use RuntimeException;

class DefaultSqlExecutor implements SqlExecutor
{
    private const MAX_ATTEMPT_COUNT = 4;

    private PDO $pdo;

    public function __construct(
        PDOProvider $pdoProvider,
        private ?LoggerInterface $logger = null,
        private bool $logAll = false,
        private bool $logFailed = false
    ) {
        $this->pdo = $pdoProvider->get();
    }

    /**
     * Execute a query.
     */
    public function execute(string $sql, bool $rerunIfDeadlock = false): PDOStatement
    {
        if ($this->logAll) {
            $this->logger?->info("SQL: " . $sql, ['isSql' => true]);
        }

        if (!$rerunIfDeadlock) {
            return $this->executeSqlWithDeadlockHandling($sql, 1);
        }

        return $this->executeSqlWithDeadlockHandling($sql);
    }

    private function executeSqlWithDeadlockHandling(string $sql, ?int $counter = null): PDOStatement
    {
        $counter = $counter ?? self::MAX_ATTEMPT_COUNT;

        try {
            $sth = $this->pdo->query($sql);
        } catch (Exception $e) {
            $counter--;

            if ($counter === 0 || !$this->isExceptionIsDeadlock($e)) {
                if ($this->logFailed) {
                    $this->logger?->error("SQL failed: " . $sql, ['isSql' => true]);
                }

                /** @var PDOException $e */
                throw $e;
            }

            return $this->executeSqlWithDeadlockHandling($sql, $counter);
        }

        if (!$sth) {
            throw new RuntimeException("Query execution failure.");
        }

        return $sth;
    }

    private function isExceptionIsDeadlock(Exception $e): bool
    {
        if (!$e instanceof PDOException) {
            return false;
        }

        return isset($e->errorInfo) && $e->errorInfo[0] == 40001 && $e->errorInfo[1] == 1213;
    }
}
