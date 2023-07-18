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

namespace tests\integration\Espo\Record;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\ORM\EntityManager;
use tests\integration\Core\BaseTestCase;

class LinkTest extends BaseTestCase
{
    public function testUnlinkRequired1(): void
    {
        $metadata = $this->getContainer()->getByClass(Metadata::class);

        $metadata->set('entityDefs', CaseObj::ENTITY_TYPE, [
            'fields' => [
                'account' => ['required' => true]
            ]
        ]);
        $metadata->save();

        $this->reCreateApplication();

        $em = $this->getContainer()->getByClass(EntityManager::class);

        $account = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'Test',
        ]);

        $case = $em->createEntity(CaseObj::ENTITY_TYPE, [
            'name' => 'Test',
            'accountId' => $account->getId(),
        ]);

        $accountService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Account::class);

        $this->expectException(Forbidden::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $accountService->unlink($account->getId(), 'cases', $case->getId());
    }
}
