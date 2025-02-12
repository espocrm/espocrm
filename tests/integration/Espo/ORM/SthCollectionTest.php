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

use Espo\Modules\Crm\Entities\Account;
use Espo\ORM\EntityManager;
use Espo\ORM\SthCollection;
use tests\integration\Core\BaseTestCase;

class SthCollectionTest extends BaseTestCase
{
    public function test1()
    {
        $app = $this->createApplication();

        /** @var EntityManager $em */
        $em = $app->getContainer()->getByClass(EntityManager::class);

        $em->createEntity('Account', [
            'name' => 'test-1',
        ]);

        $em->createEntity('Account', [
            'name' => 'test-2',
        ]);
        $em->createEntity('Account', [
            'name' => 'test-3',
        ]);

        $query = $em->getQueryBuilder()
            ->select()
            ->from('Account')
            ->limit(0, 2)
            ->order('name')
            ->build();

        $collection = $em->getCollectionFactory()->createFromQuery($query);

        $count = 0;
        $list = [];

        foreach ($collection as $e) {
            $count++;
            $list[] = $e;
        }

        $this->assertEquals(2, $count);

        $this->assertEquals('test-1', $list[0]->get('name'));

        $array = $collection->getValueMapList();

        $this->assertEquals('test-2', $array[1]->name);
    }

    public function test2(): void
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->getByClass(EntityManager::class);

        $em->createEntity('Account', [
            'name' => 'test-2',
        ]);
        $em->createEntity('Account', [
            'name' => 'test-3',
        ]);

        $query = $em->getQueryBuilder()
            ->select()
            ->from('Account')
            ->limit(0, 2)
            ->order('name')
            ->build();

        $collection = $em->getCollectionFactory()->createFromQuery($query);

        $count = 0;

        foreach ($collection as $ignored) {
            $count++;
        }

        $this->assertEquals(2, $count);
    }

    public function testFind1()
    {
        $app = $this->createApplication();

        /** @var EntityManager $em */
        $em = $app->getContainer()->getByClass(EntityManager::class);

        $em->createEntity('Account', [
            'name' => 'test-1',
        ]);
        $em->createEntity('Account', [
            'name' => 'test-2',
        ]);
        $em->createEntity('Account', [
            'name' => 'test-3',
        ]);

        $query = $em->getQueryBuilder()
            ->select()
            ->from('Account')
            ->where(['name' => 'test-1'])
            ->build();

        $collection = $em->getRDBRepository('Account')
            ->clone($query)
            ->sth()
            ->find();

        $this->assertEquals(SthCollection::class, get_class($collection));

        $count = 0;

        foreach ($collection as $e) {
            $count++;
        }

        $this->assertEquals(1, $count);
    }

    public function testFindRelatedOneToMany()
    {
        $app = $this->createApplication();

        /** @var EntityManager $em */
        $em = $app->getContainer()->getByClass(EntityManager::class);

        $account = $em->createEntity('Account', [
            'name' => 'test-1',
        ]);
        $em->createEntity('Opportunity', [
            'name' => 'o-1',
            'accountId' => $account->getId(),
        ]);
        $em->createEntity('Opportunity', [
            'name' => 'o-2',
            'accountId' => $account->getId(),
        ]);

        $query = $em->getQueryBuilder()
            ->select()
            ->from('Opportunity')
            ->order('name')
            ->build();

        $collection = $em
            ->getRelation($account, 'opportunities')
            ->clone($query)
            ->sth()
            ->find();

        $this->assertEquals(SthCollection::class, get_class($collection));

        $count = 0;

        foreach ($collection as $e) {
            $count++;
        }

        $this->assertEquals(2, $count);

        $array = $collection->getValueMapList();

        $this->assertEquals('o-1', $array[0]->name);
    }

    public function testFindRelatedManyToMany()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $contact = $em->createEntity('Contact', [
            'lastName' => 'test-1',
        ]);
        $em->createEntity('Opportunity', [
            'name' => 'o-1',
            'contactsIds' => [$contact->getId()],
        ]);
        $em->createEntity('Opportunity', [
            'name' => 'o-2',
            'contactsIds' => [$contact->getId()],
        ]);


        $query = $em->getQueryBuilder()
            ->select()
            ->from('Opportunity')
            ->build();

        $collection = $em->getRepository('Contact')->getRelation($contact, 'opportunities')
            ->clone($query)
            ->sth()
            ->find();

        $this->assertEquals(SthCollection::class, get_class($collection));

        $count = 0;

        foreach ($collection as $e) {
            $count++;
        }

        $this->assertEquals(2, $count);
    }

    public function testMethods(): void
    {
        $e1 = $this->getEntityManager()->createEntity(Account::ENTITY_TYPE, ['name' => '1']);
        $e2 = $this->getEntityManager()->createEntity(Account::ENTITY_TYPE, ['name' => '2']);
        $e3 = $this->getEntityManager()->createEntity(Account::ENTITY_TYPE, ['name' => '3']);
        $e4 = $this->getEntityManager()->createEntity(Account::ENTITY_TYPE, ['name' => '4']);

        $collection = $this->getEntityManager()
            ->getRDBRepositoryByClass(Account::class)
            ->sth()
            ->order('name')
            ->find();

        $filtered = $collection->filter(function ($e) use ($e2, $e3) {
            return $e->getId() !== $e2->getId() && $e->getId() !== $e3->getId();
        });

        $this->assertEquals([$e1->getId(), $e4->getId()], array_map(fn ($it) => $it->getId(), [...$filtered]));
        $this->assertEquals($collection->getEntityType(), $filtered->getEntityType());
    }
}
