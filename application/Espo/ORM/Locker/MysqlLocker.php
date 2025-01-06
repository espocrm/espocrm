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

namespace Espo\ORM\Locker;

use Espo\ORM\QueryComposer\QueryComposer;
use Espo\ORM\QueryComposer\MysqlQueryComposer;
use Espo\ORM\Query\LockTableBuilder;
use Espo\ORM\TransactionManager;

use PDO;
use RuntimeException;

/**
 * Transactions within locking is not supported for MySQL.
 */
class MysqlLocker implements Locker
{
    private MysqlQueryComposer $queryComposer;
    /** @phpstan-ignore-next-line */
    private TransactionManager $transactionManager;

    private bool $isLocked = false;

    public function __construct(
        private PDO $pdo,
        QueryComposer $queryComposer,
        TransactionManager $transactionManager
    ) {
        $this->transactionManager = $transactionManager;

        if (!$queryComposer instanceof MysqlQueryComposer) {
            throw new RuntimeException();
        }

        $this->queryComposer = $queryComposer;
    }

    /**
     * {@inheritdoc}
     */
    public function isLocked(): bool
    {
        return $this->isLocked;
    }
    /**
     * {@inheritdoc}
     */
    public function lockExclusive(string $entityType): void
    {
        $this->isLocked = true;

        $query = (new LockTableBuilder())
            ->table($entityType)
            ->inExclusiveMode()
            ->build();

        $sql = $this->queryComposer->composeLockTable($query);

        $this->pdo->exec($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function lockShare(string $entityType): void
    {
        $this->isLocked = true;

        $query = (new LockTableBuilder())
            ->table($entityType)
            ->inShareMode()
            ->build();

        $sql = $this->queryComposer->composeLockTable($query);

        $this->pdo->exec($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): void
    {
        if (!$this->isLocked) {
            throw new RuntimeException("Can't commit, it was not locked.");
        }

        $this->isLocked = false;

        $sql = $this->queryComposer->composeUnlockTables();

        $this->pdo->exec($sql);
    }

    /**
     * Lift locking.
     * Rolling back within locking is not supported for MySQL.
     */
    public function rollback(): void
    {
        if (!$this->isLocked) {
            throw new RuntimeException("Can't rollback, it was not locked.");
        }

        $this->isLocked = false;

        $sql = $this->queryComposer->composeUnlockTables();

        $this->pdo->exec($sql);
    }
}
