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
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Opportunity;
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

    public function testIsRelated(): void
    {
        $em = $this->getEntityManager();

        $acc1 = $em->createEntity(Account::ENTITY_TYPE, []);
        $acc2 = $em->createEntity(Account::ENTITY_TYPE, []);

        $opp1 = $em->createEntity(Opportunity::ENTITY_TYPE, []);

        $contact1 = $em->createEntity(Contact::ENTITY_TYPE, []);
        $contact2 = $em->createEntity(Contact::ENTITY_TYPE, []);

        $oppRepo = $em->getRDBRepositoryByClass(Opportunity::class);

        $oppRepo->getRelation($opp1, 'account')->relate($acc1);
        $oppRepo->getRelation($opp1, 'contacts')->relate($contact1);

        $this->assertTrue(
            $oppRepo->getRelation($opp1, 'account')->isRelatedById($acc1->getId())
        );

        $this->assertTrue(
            $oppRepo->getRelation($opp1, 'account')->isRelated($acc1)
        );

        $this->assertFalse(
            $oppRepo->getRelation($opp1, 'account')->isRelatedById($acc2->getId())
        );

        $this->assertFalse(
            $oppRepo->getRelation($opp1, 'account')->isRelated($acc2)
        );

        $this->assertTrue(
            $oppRepo->getRelation($opp1, 'contacts')->isRelatedById($contact1->getId())
        );

        $this->assertTrue(
            $oppRepo->getRelation($opp1, 'contacts')->isRelated($contact1)
        );

        $this->assertFalse(
            $oppRepo->getRelation($opp1, 'contacts')->isRelatedById($contact2->getId())
        );

        $this->assertFalse(
            $oppRepo->getRelation($opp1, 'contacts')->isRelated($contact2)
        );
    }
}
