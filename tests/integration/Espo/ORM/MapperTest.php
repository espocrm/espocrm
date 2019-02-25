<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class MapperTest extends \tests\integration\Core\BaseTestCase
{
    public function testRelate1()
    {
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $account = $entityManager->getEntity('Account');
        $account->set('name', 'Test');
        $entityManager->saveEntity($account);

        $contact = $entityManager->getEntity('Contact');
        $contact->set('lastName', 'Test');;
        $entityManager->saveEntity($contact);

        $entityManager->getRepository('Account')->relate($account, 'contacts', $contact);
        $isRelated = $entityManager->getRepository('Account')->isRelated($account, 'contacts', $contact);
        $this->assertTrue($isRelated);

        $entityManager->getRepository('Account')->unrelate($account, 'contacts', $contact);
        $isRelated = $entityManager->getRepository('Account')->isRelated($account, 'contacts', $contact);
        $this->assertFalse($isRelated);
    }

    public function testRelate2()
    {
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $account = $entityManager->getEntity('Account');
        $account->set('name', 'Test');
        $entityManager->saveEntity($account);

        $contact = $entityManager->getEntity('Contact');
        $contact->set('lastName', 'Test');;
        $entityManager->saveEntity($contact);

        $entityManager->getRepository('Contact')->relate($contact, 'account', $account);
        $isRelated = $entityManager->getRepository('Contact')->isRelated($contact, 'account', $account);
        $this->assertTrue($isRelated);

        $contact = $entityManager->getEntity('Contact', $contact->id);

        $entityManager->getRepository('Contact')->unrelate($contact, 'account', $account);
        $isRelated = $entityManager->getRepository('Contact')->isRelated($contact, 'account', $account);
        $this->assertFalse($isRelated);
    }

    public function testRelate3()
    {
        $app = $this->createApplication();

        $entityManager = $app->getContainer()->get('entityManager');

        $account = $entityManager->getEntity('Account');
        $account->set('name', 'Test');
        $entityManager->saveEntity($account);

        $task = $entityManager->getEntity('Task');
        $task->set('name', 'Test');;
        $entityManager->saveEntity($task);

        $entityManager->getRepository('Task')->relate($task, 'parent', $account);
        $isRelated = $entityManager->getRepository('Task')->isRelated($task, 'parent', $account);
        $this->assertTrue($isRelated);

        $task = $entityManager->getEntity('Task', $task->id);

        $entityManager->getRepository('Task')->unrelate($task, 'parent', $account);
        $isRelated = $entityManager->getRepository('Task')->isRelated($task, 'parent', $account);
        $this->assertFalse($isRelated);
    }
}
