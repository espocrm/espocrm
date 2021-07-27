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

namespace Espo\ORM\Repository;

use Espo\ORM\{
    TransactionManager,
};

use RuntimeException;

/**
 * Wrapper for TransactionManager to be used within RDBRepository in beforeSave and afterSave methods.
 */
class RDBTransactionManager
{
    private $transactionManager;

    private $level = 0;

    public function __construct(TransactionManager $transactionManager)
    {
        $this->transactionManager = $transactionManager;
    }

    public function isStarted(): bool
    {
        return $this->level > 0;
    }

    public function start(): void
    {
        if ($this->isStarted()) {
            throw new RuntimeException("Can't start a transaction more than once.");
        }

        $this->transactionManager->start();

        $this->level = $this->transactionManager->getLevel();
    }

    public function commit(): void
    {
        if (!$this->isStarted()) {
            throw new RuntimeException("Can't commit not started transaction.");
        }

        while ($this->transactionManager->getLevel() >= $this->level) {
            $this->transactionManager->commit();
        }

        $this->level = 0;
    }

    public function rollback(): void
    {
        if (!$this->isStarted()) {
            throw new RuntimeException("Can't rollback not started transaction.");
        }

        while ($this->transactionManager->getLevel() >= $this->level) {
            $this->transactionManager->rollback();
        }

        $this->level = 0;
    }
}
