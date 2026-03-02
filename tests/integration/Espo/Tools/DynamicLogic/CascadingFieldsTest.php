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

namespace tests\integration\Espo\Tools\DynamicLogic;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\ServiceContainer;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Tools\EntityManager\EntityManager;
use Espo\Tools\FieldManager\FieldManager;
use Espo\Tools\LinkManager\LinkManager;
use tests\integration\Core\BaseTestCase;

class CascadingFieldsTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLinkMultiple(): void
    {
        $emTool = $this->getInjectableFactory()->create(EntityManager::class);
        $lmTool = $this->getInjectableFactory()->create(LinkManager::class);
        $fmTool = $this->getInjectableFactory()->create(FieldManager::class);

        $emTool->create('MyTest', 'Base');

        $lmTool->create([
            'link' => 'accounts',
            'linkForeign' => 'tests',
            'linkType' => 'manyToMany',
            'entity' => 'CMyTest',
            'entityForeign' => Account::ENTITY_TYPE,
            'label' => 'Account',
            'labelForeign' => 'Test',
            'relationName' => 'testAccount',
            'linkMultipleField' => true,
            'linkMultipleFieldForeign' => true,
        ]);

        $lmTool->create([
            'link' => 'contacts',
            'linkForeign' => 'tests',
            'linkType' => 'manyToMany',
            'entity' => 'CMyTest',
            'entityForeign' => Contact::ENTITY_TYPE,
            'label' => 'Contact',
            'labelForeign' => 'Test',
            'relationName' => 'testContact',
            'linkMultipleField' => true,
            'linkMultipleFieldForeign' => true,
        ]);

        $fmTool->update('CMyTest', 'contacts', [
            'dynamicLogicCascading' => [
                'items' => [
                    [
                        'localField' => 'accounts',
                        'foreignField' => 'accounts',
                        'matchRequired' => true,
                    ]
                ]
            ]
        ]);

        $this->reCreateApplication();

        $em = $this->getEntityManager();

        $a1 = $em->createEntity(Account::ENTITY_TYPE);
        $a2 = $em->createEntity(Account::ENTITY_TYPE);

        $c1A12 = $em->createEntity(Contact::ENTITY_TYPE, [
            'accountsIds' => [$a1->getId(), $a2->getId()],
            'accountId' => $a1->getId(),
        ]);

        $c2A1 = $em->createEntity(Contact::ENTITY_TYPE, [
            'accountsIds' => [$a1->getId()],
            'accountId' => $a1->getId(),
        ]);

        $service = $this->getContainer()->getByClass(ServiceContainer::class)->get('CMyTest');

        //

        $service->create((object) [
            'name' => 't1',
            'accountsIds' => [$a1->getId()],
            'contactsIds' => [$c2A1->getId()],
        ], CreateParams::create());

        //

        $service->create((object) [
            'name' => 't1',
            'accountsIds' => [$a1->getId(), $a2->getId()],
            'contactsIds' => [$c1A12->getId()],
        ], CreateParams::create());

        //

        $thrown = false;

        try {
            $service->create((object) [
                'name' => 't1',
                'accountsIds' => [$a1->getId(), $a2->getId()],
                'contactsIds' => [$c2A1->getId()],
            ], CreateParams::create());
        } catch (BadRequest) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testLink(): void
    {
        $emTool = $this->getInjectableFactory()->create(EntityManager::class);
        $lmTool = $this->getInjectableFactory()->create(LinkManager::class);
        $fmTool = $this->getInjectableFactory()->create(FieldManager::class);

        $emTool->create('MyTest', 'Base');

        $lmTool->create([
            'link' => 'account',
            'linkForeign' => 'tests',
            'linkType' => 'manyToOne',
            'entity' => 'CMyTest',
            'entityForeign' => Account::ENTITY_TYPE,
            'label' => 'Account',
            'labelForeign' => 'Test',
            'linkMultipleField' => true,
            'linkMultipleFieldForeign' => true,
        ]);

        $lmTool->create([
            'link' => 'contact',
            'linkForeign' => 'tests',
            'linkType' => 'manyToOne',
            'entity' => 'CMyTest',
            'entityForeign' => Contact::ENTITY_TYPE,
            'label' => 'Contact',
            'labelForeign' => 'Test',
            'linkMultipleField' => true,
            'linkMultipleFieldForeign' => true,
        ]);

        $fmTool->update('CMyTest', 'contact', [
            'dynamicLogicCascading' => [
                'items' => [
                    [
                        'localField' => 'account',
                        'foreignField' => 'accounts',
                        'matchRequired' => true,
                    ]
                ]
            ]
        ]);

        $this->reCreateApplication();

        $em = $this->getEntityManager();

        $a1 = $em->createEntity(Account::ENTITY_TYPE);
        $a2 = $em->createEntity(Account::ENTITY_TYPE);

        $c1A12 = $em->createEntity(Contact::ENTITY_TYPE, [
            'accountsIds' => [$a1->getId(), $a2->getId()],
            'accountId' => $a1->getId(),
        ]);

        $c2A1 = $em->createEntity(Contact::ENTITY_TYPE, [
            'accountsIds' => [$a1->getId()],
            'accountId' => $a1->getId(),
        ]);

        $service = $this->getContainer()->getByClass(ServiceContainer::class)->get('CMyTest');

        //

        $service->create((object) [
            'name' => 't1',
            'accountId' => $a1->getId(),
            'contactId' => $c2A1->getId(),
        ], CreateParams::create());

        //

        $service->create((object) [
            'name' => 't1',
            'accountId' => $a2->getId(),
            'contactId' => $c1A12->getId(),
        ], CreateParams::create());

        //

        $thrown = false;

        try {
            $service->create((object) [
                'name' => 't1',
                'accountId' => $a2->getId(),
                'contactId' => $c2A1->getId(),
            ], CreateParams::create());
        } catch (BadRequest) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }
}
