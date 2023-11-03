<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Modules\Crm\Entities\Account;
use Espo\ORM\EntityManager;

class RepositoryTest extends \tests\integration\Core\BaseTestCase
{
    public function testModifiedBy(): void
    {
        $user1 = $this->createUser('test-1');
        $user2 = $this->createUser('test-2');

        $this->auth('test-1');
        $app = $this->createApplication();
        /** @var EntityManager $em */
        $em = $app->getContainer()->getByClass(EntityManager::class);

        $account = $em->createEntity(Account::ENTITY_TYPE, ['name' => '1']);

        $this->assertEquals($user1->getId(), $account->get('createdById'));

        $this->auth('test-2');
        $app = $this->createApplication();
        /** @var EntityManager $em */
        $em = $app->getContainer()->getByClass(EntityManager::class);

        $account = $em->getEntityById(Account::ENTITY_TYPE, $account->getId());
        $account->set('name', '2');
        $em->saveEntity($account);

        $this->assertEquals($user2->getId(), $account->get('modifiedById'));

        $this->auth('test-1');
        $app = $this->createApplication();
        /** @var EntityManager $em */
        $em = $app->getContainer()->getByClass(EntityManager::class);

        $account = $em->getEntityById(Account::ENTITY_TYPE, $account->getId());
        $account->set('name', '2');
        $em->saveEntity($account);

        $this->assertEquals($user2->getId(), $account->get('modifiedById'));
    }
}
