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

namespace tests\unit\Espo\ORM;

use Espo\ORM\{
    TransactionManager,
    QueryComposer\MysqlQueryComposer,
};

use PDO;
use RuntimeException;

class TransactionManagerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $this->composer = $this->getMockBuilder(MysqlQueryComposer::class)->disableOriginalConstructor()->getMock();

        $this->composer
            ->expects($this->any())
            ->method('composeCreateSavepoint')
            ->will(
                $this->returnCallback(
                    function ($name) {
                        return 'SAVEPOINT ' . $name;
                    }
                )
            );

        $this->composer
            ->expects($this->any())
            ->method('composeReleaseSavepoint')
            ->will(
                $this->returnCallback(
                    function ($name) {
                        return 'RELEASE SAVEPOINT ' . $name;
                    }
                )
            );

        $this->composer
            ->expects($this->any())
            ->method('composeRollbackToSavepoint')
            ->will(
                $this->returnCallback(
                    function ($name) {
                        return 'ROLLBACK TO SAVEPOINT ' . $name;
                    }
                )
            );

        $this->manager = new TransactionManager($this->pdo, $this->composer);
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

        $this->pdo
            ->expects($this->exactly(4))
            ->method('exec')
            ->withConsecutive(
                ['SAVEPOINT POINT_1'],
                ['SAVEPOINT POINT_2'],
                ['RELEASE SAVEPOINT POINT_2'],
                ['ROLLBACK TO SAVEPOINT POINT_1'],
            );

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
        $this->pdo
            ->expects($this->exactly(2))
            ->method('exec')
            ->withConsecutive(
                ['SAVEPOINT POINT_1'],
                ['ROLLBACK TO SAVEPOINT POINT_1'],
            );

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

        $this->pdo
            ->expects($this->exactly(2))
            ->method('exec')
            ->withConsecutive(
                ['SAVEPOINT POINT_1'],
                ['RELEASE SAVEPOINT POINT_1'],
            );

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
