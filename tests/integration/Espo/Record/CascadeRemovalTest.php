<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace tests\integration\Espo\Record;

use Espo\Core\Record\ServiceContainer;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Crm\Entities\Task;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\SelectBuilder;
use tests\integration\Core\BaseTestCase;

class CascadeRemovalTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testRemovalAndRestoral(): void
    {
        $metadata = $this->getMetadata();
        $metadata->set('entityDefs', 'Account', [
            'links' => [
                'opportunities' => [
                    RelationParam::CASCADE_REMOVAL => true,
                ]
            ]
        ]);
        $metadata->set('entityDefs', 'Opportunity', [
            'links' => [
                'tasks' => [
                    RelationParam::CASCADE_REMOVAL => true,
                ]
            ]
        ]);
        $metadata->save();
        $this->getDataManager()->clearCache();

        $this->reCreateApplication();

        $em = $this->getEntityManager();

        $account = $em->createEntity('Account', ['name' => 'a1']);
        $contact = $em->createEntity('Contact', ['lastName' => 'c1', 'accountId' => $account->getId()]);
        $opp1 = $em->createEntity('Opportunity', ['name' => 'o1', 'accountId' => $account->getId()]);
        $opp2 = $em->createEntity('Opportunity', ['name' => 'o2', 'accountId' => $account->getId()]);
        $opp3 = $em->createEntity('Opportunity', ['name' => 'o3']);

        $task1 = $em->createEntity('Task', [
            'name' => 't1',
            'parentType' => 'Opportunity',
            'parentId' => $opp1->getId(),
        ]);

        $em->removeEntity($account);

        $this->assertNotNull(
            $em->getRDBRepository(Account::ENTITY_TYPE)
                ->clone(
                    SelectBuilder::create()
                        ->from(Account::ENTITY_TYPE)
                        ->withDeleted()
                        ->build()
                )
                ->where([
                    Attribute::ID => $account->getId(),
                    Attribute::DELETED => true,
                ])
                ->findOne()
        );

        $this->assertNotNull(
            $em->getRDBRepository(Contact::ENTITY_TYPE)
                ->where([
                    Attribute::ID => $contact->getId(),
                ])
                ->findOne()
        );

        $this->assertNotNull(
            $em->getRDBRepository(Opportunity::ENTITY_TYPE)
                ->clone(
                    SelectBuilder::create()
                        ->from(Opportunity::ENTITY_TYPE)
                        ->withDeleted()
                        ->build()
                )
                ->where([
                    Attribute::ID => $opp1->getId(),
                    Attribute::DELETED => true,
                ])
                ->findOne()
        );

        $this->assertNotNull(
            $em->getRDBRepository(Opportunity::ENTITY_TYPE)
                ->clone(
                    SelectBuilder::create()
                        ->from(Opportunity::ENTITY_TYPE)
                        ->withDeleted()
                        ->build()
                )
                ->where([
                    Attribute::ID => $opp2->getId(),
                    Attribute::DELETED => true,
                ])
                ->findOne()
        );

        $this->assertNotNull(
            $em->getRDBRepository(Task::ENTITY_TYPE)
                ->clone(
                    SelectBuilder::create()
                        ->from(Task::ENTITY_TYPE)
                        ->withDeleted()
                        ->build()
                )
                ->where([
                    Attribute::ID => $task1->getId(),
                    Attribute::DELETED => true,
                ])
                ->findOne()
        );

        $this->assertNotNull(
            $em->getRDBRepository(Opportunity::ENTITY_TYPE)
                ->where([
                    Attribute::ID => $opp3->getId(),
                ])
                ->findOne()
        );

        //

        $service = $this->getContainer()->getByClass(ServiceContainer::class)->get(Account::ENTITY_TYPE);

        $service->restoreDeleted($account->getId());

        $this->assertNotNull(
            $em->getRDBRepository(Account::ENTITY_TYPE)
                ->where([
                    Attribute::ID => $account->getId(),
                ])
                ->findOne()
        );

        $this->assertNotNull(
            $em->getRDBRepository(Opportunity::ENTITY_TYPE)
                ->where([
                    Attribute::ID => $opp1->getId(),
                ])
                ->findOne()
        );

        $this->assertNotNull(
            $em->getRDBRepository(Opportunity::ENTITY_TYPE)
                ->where([
                    Attribute::ID => $opp2->getId(),
                ])
                ->findOne()
        );

        $this->assertNotNull(
            $em->getRDBRepository(Task::ENTITY_TYPE)
                ->where([
                    Attribute::ID => $task1->getId(),
                ])
                ->findOne()
        );
    }
}
