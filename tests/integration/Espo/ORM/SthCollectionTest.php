<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace tests\integration\Espo\ORM;

class SthCollectionTest extends \tests\integration\Core\BaseTestCase
{
    public function test1()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $em->createEntity('Account', [
            'name' => 'test-1',
        ]);

        $em->createEntity('Account', [
            'name' => 'test-2',
        ]);
        $em->createEntity('Account', [
            'name' => 'test-3',
        ]);
        $collection = $em->createSthCollection('Account', [
            'limit' => 2,
            'orderBy' => 'name',
        ]);

        $count = 0;
        $list = [];

        foreach ($collection as $e) {
            $count++;
            $list[] = $e;
        }

        $this->assertEquals(2, $count);

        $this->assertEquals('test-1', $list[0]->get('name'));

        $array = $collection->toArray();

        $this->assertEquals('test-2', $array[1]['name']);
    }

    public function test2()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $em->createEntity('Account', [
            'name' => 'test-2',
        ]);
        $em->createEntity('Account', [
            'name' => 'test-3',
        ]);
        $collection = $em->createSthCollection('Account', [
            'limit' => 2,
            'orderBy' => 'name',
        ]);

        $collection = new \Espo\ORM\Sth2Collection('Account', $em->getEntityFactory(), $em->getQuery(), $em->getPdo(), [
            'limit' => 2,
            'orderBy' => 'name',
        ]);

        $count = 0;

        foreach ($collection as $e) {
            $count++;
        }

        $this->assertEquals(2, $count);
    }

    public function testFind()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $em->createEntity('Account', [
            'name' => 'test-1',
        ]);
        $em->createEntity('Account', [
            'name' => 'test-2',
        ]);
        $em->createEntity('Account', [
            'name' => 'test-3',
        ]);

        $collection = $em->getRepository('Account')->where([
            'name' => 'test-1',
        ])->find(['returnSthCollection' => true]);

        $this->assertEquals("Espo\\ORM\\Sth2Collection", get_class($collection));

        $count = 0;

        foreach ($collection as $e) {
            $count++;
        }

        $this->assertEquals(1, $count);
    }

    public function testFindRelatedOneToMany()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $account = $em->createEntity('Account', [
            'name' => 'test-1',
        ]);
        $em->createEntity('Opportunity', [
            'name' => 'o-1',
            'accountId' => $account->id,
        ]);
        $em->createEntity('Opportunity', [
            'name' => 'o-2',
            'accountId' => $account->id,
        ]);

        $collection = $em->getRepository('Account')->findRelated($account, 'opportunities', [
            'returnSthCollection' => true,
            'orderBy' => 'name',
        ]);

        $this->assertEquals("Espo\\ORM\\Sth2Collection", get_class($collection));

        $count = 0;

        foreach ($collection as $e) {
            $count++;
        }

        $this->assertEquals(2, $count);

        $array = $collection->toArray();

        $this->assertEquals($array[0]['name'], 'o-1');
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
            'contactsIds' => [$contact->id],
        ]);
        $em->createEntity('Opportunity', [
            'name' => 'o-2',
            'contactsIds' => [$contact->id],
        ]);

        $collection = $em->getRepository('Contact')->findRelated($contact, 'opportunities', [
            'returnSthCollection' => true,
        ]);

        $this->assertEquals("Espo\\ORM\\Sth2Collection", get_class($collection));

        $count = 0;

        foreach ($collection as $e) {
            $count++;
        }

        $this->assertEquals(2, $count);
    }
}
