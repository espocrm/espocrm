<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\ORM\EntityCollection;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Opportunity;
use tests\integration\Core\BaseTestCase;
use tests\integration\testClasses\Entities\Account as AccountExtended;
use tests\integration\testClasses\Entities\Opportunity as OpportunityExtended;

class RelationsTest extends BaseTestCase
{
    public function testGetOne(): void
    {
        $metadata = $this->getMetadata();
        $metadata->set('entityDefs', Opportunity::ENTITY_TYPE, [
            'entityClassName' => OpportunityExtended::class,
        ]);
        $metadata->save();

        $this->reCreateApplication();

        $em = $this->getEntityManager();

        $opp = $em->getEntity(Opportunity::ENTITY_TYPE);
        $this->assertInstanceOf(OpportunityExtended::class, $opp);
        $this->assertNull($opp->getRelatedAccount());

        $account = $em->createEntity(Account::ENTITY_TYPE);
        $opp = $em->createEntity(Opportunity::ENTITY_TYPE, [
            'accountId' => $account->getId(),
        ]);

        $this->assertInstanceOf(OpportunityExtended::class, $opp);

        $account1 = $opp->getRelatedAccount();

        $this->assertInstanceOf(Account::class, $account1);
        $this->assertEquals($account->getId(), $account1->getId());

        $em->refreshEntity($opp);

        $account2 = $opp->getRelatedAccount();

        $this->assertInstanceOf(Account::class, $account2);
        $this->assertNotSame($account1, $account2);
        $this->assertEquals($account->getId(), $account2->getId());

        $em->saveEntity($opp);

        $account3 = $opp->getRelatedAccount();

        $this->assertInstanceOf(Account::class, $account3);
        $this->assertNotSame($account1, $account3);
        $this->assertEquals($account->getId(), $account3->getId());
    }

    public function testGetMany(): void
    {
        $metadata = $this->getMetadata();
        $metadata->set('entityDefs', Account::ENTITY_TYPE, [
            'entityClassName' => AccountExtended::class,
        ]);
        $metadata->save();

        $this->reCreateApplication();

        $em = $this->getEntityManager();

        $account = $em->getEntity(Account::ENTITY_TYPE);
        $this->assertInstanceOf(AccountExtended::class, $account);
        $this->assertInstanceOf(EntityCollection::class, $account->getRelatedOpportunities());

        $account = $em->createEntity(Account::ENTITY_TYPE);
        $this->assertInstanceOf(AccountExtended::class, $account);

        $em->createEntity(Opportunity::ENTITY_TYPE, ['accountId' => $account->getId()]);
        $em->createEntity(Opportunity::ENTITY_TYPE, ['accountId' => $account->getId()]);
        $em->createEntity(Opportunity::ENTITY_TYPE);

        $collection = $account->getRelatedOpportunities();

        $this->assertInstanceOf(EntityCollection::class, $collection);
        $items = iterator_to_array($collection);

        $this->assertCount(2, $items);
    }
}
