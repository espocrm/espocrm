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

namespace tests\integration\Espo\Core\ORM;

use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Opportunity;
use tests\integration\Core\BaseTestCase;

class CoreEntityTest extends BaseTestCase
{
    public function testLinkMultiple(): void
    {
        $contact1 = $this->getEntityManager()->createEntity(Contact::ENTITY_TYPE, [
            'lastName' => 'Test 1',
        ]);

        $contact2 = $this->getEntityManager()->createEntity(Contact::ENTITY_TYPE, [
            'lastName' => 'Test 2',
        ]);

        $opp = $this->getEntityManager()->createEntity(Opportunity::ENTITY_TYPE, [
            'name' => 'Test',
            'contactsIds' => [$contact1->getId()],
            'contactsColumns' => [
                $contact1->getId() => ['role' => 'Evaluator']
            ],
        ]);

        $opp = $this->getEntityManager()
            ->getRDBRepositoryByClass(Opportunity::class)
            ->getById($opp->getId());

        $this->assertTrue(in_array($contact1->getId(), $opp->getLinkMultipleIdList('contacts')));
        $this->assertEquals('Evaluator', $opp->getLinkMultipleColumn('contacts', 'role', $contact1->getId()));

        $opp->addLinkMultipleId('contacts', $contact2->getId());
        $opp->setLinkMultipleColumn('contacts', 'role', $contact2->getId(), 'Decision Maker');

        $this->assertTrue($opp->hasLinkMultipleId('contacts', $contact2->getId()));
        $this->assertEquals('Decision Maker', $opp->getLinkMultipleColumn('contacts', 'role', $contact2->getId()));

        // Rewrites set values.
        $opp->loadLinkMultipleField('contacts');

        $this->assertFalse($opp->hasLinkMultipleId('contacts', $contact2->getId()));

        $this->assertTrue(in_array($contact1->getId(), $opp->getFetched('contactsIds')));
        $this->assertFalse(in_array($contact2->getId(), $opp->getFetched('contactsIds')));
    }
}
