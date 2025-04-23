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

namespace tests\integration\Espo\Record;

use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\ServiceContainer;
use Espo\Entities\Portal;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\Modules\Crm\Entities\Contact;
use tests\integration\Core\BaseTestCase;

class DefaultsPopulatorTest extends BaseTestCase
{
    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Conflict
     */
    public function testPortal(): void
    {
        $em = $this->getEntityManager();

        $account = $em->getRDBRepositoryByClass(Account::class)->getNew();
        $em->saveEntity($account);

        $contact = $em->getRDBRepositoryByClass(Contact::class)->getNew();
        $contact->setAccount($account);
        $em->saveEntity($contact);

        $portal = $em->getRDBRepositoryByClass(Portal::class)->getNew();
        $em->saveEntity($portal);

        $this->createUser([
            'userName' => 'tester',
            'portalsIds' => [$portal->getId()],
            'contactId' => $contact->getId(),
            'accountsIds' => [$account->getId()],
        ], [
            'data' => [
                'Case' => [
                    'create' => Table::LEVEL_YES,
                    'read' => 'contact',
                ],
            ],
        ], true);

        $this->auth('tester', null, $portal->getId());
        $this->reCreateApplication();

        $service = $this->getContainer()->getByClass(ServiceContainer::class)->getByClass(CaseObj::class);

        $case = $service->create((object) [
            'name' => 'Test',
        ], CreateParams::create());

        $this->assertEquals($contact->getId(), $case->getContact()?->getId());
        $this->assertEquals($account->getId(), $case->getAccount()?->getId());
    }
}
