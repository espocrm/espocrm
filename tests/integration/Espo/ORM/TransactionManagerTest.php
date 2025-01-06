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

namespace tests\integration\Espo\ORM;

class TransactionManagerTest extends \tests\integration\Core\BaseTestCase
{
    public function testOne()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $tm = $em->getTransactionManager();

        $tm->start();

        $account = $em->createEntity('Account', [
            'name' => 'test',
        ]);

        $this->assertNotNull($account);

        $id = $account->getId();

        $tm->commit();

        $account = $em->getEntity('Account', $id);

        $this->assertNotNull($account);
    }

    public function testRollbackOne()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $tm = $em->getTransactionManager();

        $tm->start();

        $account = $em->createEntity('Account', [
            'name' => 'test',
        ]);

        $this->assertNotNull($account);

        $id = $account->getId();

        $tm->rollback();

        $account = $em->getEntity('Account', $id);

        $this->assertNull($account);
    }

    public function testRollbackNested()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $tm = $em->getTransactionManager();

        $tm->start();

        $account1 = $em->createEntity('Account', [
            'name' => 'test1',
        ]);

        $id1 = $account1->getId();

        $tm->start();

        $account2 = $em->createEntity('Account', [
            'name' => 'test2',
        ]);

        $id2 = $account2->getId();

        $tm->rollback();

        $tm->commit();

        $account1 = $em->getEntity('Account', $id1);
        $account2 = $em->getEntity('Account', $id2);

        $this->assertNotNull($account1);
        $this->assertNull($account2);
    }

    public function testRunCommit()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $tm = $em->getTransactionManager();

        $account = $em->createEntity('Account', [
            'name' => 'test',
        ]);

        $id = $account->getId();

        $tm->run(
            function () use ($em, $id){
                $account = $em->getEntity('Account', $id);
                $account->set('name', 'test-1');
                $em->saveEntity($account);
            }
        );

        $account = $em->getEntity('Account', $id);

        $this->assertNotNull($account);
        $this->assertEquals('test-1', $account->get('name'));
    }

    public function testRunRollback()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $tm = $em->getTransactionManager();

        $account = $em->createEntity('Account', [
            'name' => 'test',
        ]);

        $id = $account->getId();

        try {
            $tm->run(
                function () use ($em, $id){
                    $account = $em->getEntity('Account', $id);
                    $account->set('name', 'test-1');
                    $em->saveEntity($account);

                    throw new \Exception();
                }
            );
        } catch (\Exception $e) {}

        $account = $em->getEntity('Account', $id);

        $this->assertNotNull($account);
        $this->assertEquals('test', $account->get('name'));
    }
}
