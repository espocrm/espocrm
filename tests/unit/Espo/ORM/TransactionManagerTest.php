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

use Espo\ORM\QueryComposer\MysqlQueryComposer;
use Espo\ORM\TransactionManager;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TransactionManagerTest extends TestCase
{
    private $pdo;
    private $manager;

    protected function setUp() : void
    {
        $this->pdo = $this->createMock(PDO::class);
        $composer = $this->createMock(MysqlQueryComposer::class);

        $composer
            ->expects($this->any())
            ->method('composeCreateSavepoint')
            ->willReturnCallback(
                function ($name) {
                    return 'SAVEPOINT ' . $name;
                }
            );

        $composer
            ->expects($this->any())
            ->method('composeReleaseSavepoint')
            ->willReturnCallback(
                function ($name) {
                    return 'RELEASE SAVEPOINT ' . $name;
                }
            );

        $composer
            ->expects($this->any())
            ->method('composeRollbackToSavepoint')
            ->willReturnCallback(
                function ($name) {
                    return 'ROLLBACK TO SAVEPOINT ' . $name;
                }
            );

        $this->manager = new TransactionManager($this->pdo, $composer);
    }

    public function testStartOnce()
    {
        $this->pdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->manager->start();
    }

    public function testNested()
    {
        $this->pdo
            ->expects($this->exactly(1))
            ->method('beginTransaction');

        $invokedCount = $this->exactly(4);

        $this->pdo
            ->expects($invokedCount)
            ->method('exec')
            ->willReturnCallback(function ($sql) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals('SAVEPOINT POINT_1', $sql);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals('SAVEPOINT POINT_2', $sql);
                }

                if ($invokedCount->numberOfInvocations() === 3) {
                    $this->assertEquals('RELEASE SAVEPOINT POINT_2', $sql);
                }

                if ($invokedCount->numberOfInvocations() === 4) {
                    $this->assertEquals('ROLLBACK TO SAVEPOINT POINT_1', $sql);
                }

                return 1;
            });

       $this->pdo
            ->expects($this->exactly(1))
            ->method('commit');

        $this->manager->start();
        $this->manager->start();
        $this->manager->start();

        $this->manager->commit();
        $this->manager->rollback();
        $this->manager->commit();
    }

    public function testNestedRollback()
    {
        $invokedCount = $this->exactly(2);

        $this->pdo
            ->expects($invokedCount)
            ->method('exec')
            ->willReturnCallback(function ($sql) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals('SAVEPOINT POINT_1', $sql);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals('ROLLBACK TO SAVEPOINT POINT_1', $sql);
                }

                return 1;
            });

        $this->pdo
            ->expects($this->once())
            ->method('rollBack');

        $this->manager->start();
        $this->manager->start();

        $this->manager->rollback();
        $this->manager->rollback();
    }

    public function testLevel()
    {
        $this->assertEquals(0, $this->manager->getLevel());

        $this->assertFalse($this->manager->isStarted());

        $this->manager->start();

        $this->assertEquals(1, $this->manager->getLevel());

        $this->assertTrue($this->manager->isStarted());

        $this->manager->start();

        $this->assertEquals(2, $this->manager->getLevel());

        $this->manager->commit();

        $this->assertEquals(1, $this->manager->getLevel());

        $this->manager->rollback();

        $this->assertEquals(0, $this->manager->getLevel());

        $this->assertFalse($this->manager->isStarted());
    }

    public function testError1()
    {
        $this->expectException(RuntimeException::class);

        $this->manager->commit();
    }

    public function testError2()
    {
        $this->expectException(RuntimeException::class);

        $this->manager->start();

        $this->assertTrue($this->manager->isStarted());

        $this->manager->commit();
        $this->manager->rollback();
    }

    public function testRunOnce()
    {
        $this->pdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->pdo
            ->expects($this->once())
            ->method('commit');

        $this->manager->run(
            function () {}
        );
    }

    public function testRunNested()
    {
        $this->pdo
            ->expects($this->once())
            ->method('beginTransaction');

        $invokedCount = $this->exactly(2);

        $this->pdo
            ->expects($invokedCount)
            ->method('exec')
            ->willReturnCallback(function ($sql) use ($invokedCount) {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertEquals('SAVEPOINT POINT_1', $sql);
                }

                if ($invokedCount->numberOfInvocations() === 2) {
                    $this->assertEquals('RELEASE SAVEPOINT POINT_1', $sql);
                }

                return 1;
            });

        $this->pdo
            ->expects($this->once())
            ->method('commit');

        $this->manager->run(
            function () {
                $this->manager->run(
                    function () {}
                );
            }
        );
    }

    public function testRunException()
    {
        $this->pdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->pdo
            ->expects($this->once())
            ->method('rollback');

        try {
            $this->manager->run(
                function () {
                    throw new RuntimeException();
                }
            );
        } catch (RuntimeException $e) {}
    }
}
