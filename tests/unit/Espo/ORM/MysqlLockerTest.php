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

namespace tests\unit\Espo\ORM;

use Espo\ORM\EntityFactory;
use Espo\ORM\Locker\MysqlLocker;
use Espo\ORM\Metadata;
use Espo\ORM\QueryComposer\MysqlQueryComposer;
use Espo\ORM\TransactionManager;

use PDO;
use PHPUnit\Framework\TestCase;

class MysqlLockerTest extends TestCase
{
    private $locker;
    private $pdo;

    protected function setUp() : void
    {
        $this->pdo = $this->createMock(PDO::class);
        $entityFactory = $this->createMock(EntityFactory::class);
        $metadata = $this->createMock(Metadata::class);
        $transactionManager = $this->createMock(TransactionManager::class);

        $composer = new MysqlQueryComposer($this->pdo, $entityFactory, $metadata);
        $this->locker = new MysqlLocker($this->pdo, $composer, $transactionManager);
    }

    public function testLockCommit()
    {
        $invokedCount = $this->exactly(3);

        $this->pdo
            ->expects($invokedCount)
            ->method('exec')
            ->willReturnCallback(function ($sql) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals('LOCK TABLES `account` WRITE', $sql);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals('LOCK TABLES `contact` READ', $sql);
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertEquals('UNLOCK TABLES', $sql);
                }

                return 1;
            });

        $this->locker->lockExclusive('Account');
        $this->locker->lockShare('Contact');

        $this->assertTrue($this->locker->isLocked());

        $this->locker->commit();

        $this->assertFalse($this->locker->isLocked());
    }

    public function testLockRollback()
    {
        $invokedCount = $this->exactly(3);

        $this->pdo
            ->expects($invokedCount)
            ->method('exec')
            ->willReturnCallback(function ($sql) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals('LOCK TABLES `account` WRITE', $sql);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals('LOCK TABLES `contact` READ', $sql);
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertEquals('UNLOCK TABLES', $sql);
                }

                return 1;
            });

        $this->locker->lockExclusive('Account');
        $this->locker->lockShare('Contact');

        $this->assertTrue($this->locker->isLocked());

        $this->locker->commit();

        $this->assertFalse($this->locker->isLocked());
    }
}
