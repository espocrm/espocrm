<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\integration\Espo\Core\FieldProcessing;

use Espo\Core\ORM\EntityManager;

class RelationTest extends \tests\integration\Core\BaseTestCase
{
    public function testLinkMultiple1(): void
    {
        /* @var $entityManager EntityManager */
        $entityManager = $this->getContainer()->get('entityManager');

        $contact1 = $entityManager->createEntity('Contact', []);
        $contact2 = $entityManager->createEntity('Contact', []);
        $contact3 = $entityManager->createEntity('Contact', []);

        $opportunity = $entityManager->createEntity('Opportunity', [
            'contactsIds' => [
                $contact1->getId(),
                $contact2->getId(),
            ],
            'contactsColumns' => (object) [
                $contact1->getId() => (object) [
                    'role' => 'Decision Maker',
                ],
            ],
        ]);

        $opportunity = $entityManager->getEntity('Opportunity', $opportunity->getId());

        $this->assertEquals(
            self::sortArray([
                $contact1->getId(),
                $contact2->getId(),
            ]),
            self::sortArray($opportunity->getLinkMultipleIdList('contacts'))
        );

        $column1 = $entityManager
            ->getRDBRepository('Opportunity')
            ->getRelation($opportunity, 'contacts')
            ->getColumn($contact1, 'role');

        $this->assertEquals('Decision Maker', $column1);

        $opportunity->set([
            'contactsIds' => [
                $contact2->getId(),
                $contact3->getId(),
            ],
            'contactsColumns' => (object) [
                $contact2->getId() => (object) [
                    'role' => null,
                ],
                $contact3->getId() => (object) [
                    'role' => 'Evaluator',
                ],
            ],
        ]);

        $entityManager->saveEntity($opportunity);

        $opportunity = $entityManager->getEntity('Opportunity', $opportunity->getId());

        $this->assertEquals(
            self::sortArray([
                $contact2->getId(),
                $contact3->getId(),
            ]),
            self::sortArray($opportunity->getLinkMultipleIdList('contacts'))
        );

        $column3 = $entityManager
            ->getRDBRepository('Opportunity')
            ->getRelation($opportunity, 'contacts')
            ->getColumn($contact3, 'role');

        $this->assertEquals('Evaluator', $column3);

        $opportunity = $entityManager->getEntity('Opportunity', $opportunity->getId());

        $opportunity->set([
            'contactsColumns' => (object) [
                $contact3->getId() => (object) [
                    'role' => 'Decision Maker',
                ],
            ],
        ]);

        $entityManager->saveEntity($opportunity);

        $column3 = $entityManager
            ->getRDBRepository('Opportunity')
            ->getRelation($opportunity, 'contacts')
            ->getColumn($contact3, 'role');

        $this->assertEquals('Decision Maker', $column3);
    }

    public function testHasOne(): void
    {
        /* @var $entityManager EntityManager */
        $entityManager = $this->getContainer()->get('entityManager');

        $lead1 = $entityManager->createEntity('Lead', []);
        $lead2 = $entityManager->createEntity('Lead', []);

        $account = $entityManager->createEntity('Account', []);

        $lead1->set('createdAccountId', $account->getId());

        $entityManager->saveEntity($lead1);

        $lead2->set('createdAccountId', $account->getId());

        $entityManager->saveEntity($lead2);

        $lead1 = $entityManager->getEntity('Lead', $lead1->getId());

        $this->assertNull($lead1->get('createdAccountId'));

        $lead2 = $entityManager->getEntity('Lead', $lead2->getId());

        $this->assertEquals($account->getId(), $lead2->get('createdAccountId'));
    }

    public function testBelongsToHasOne(): void
    {
        /* @var $entityManager EntityManager */
        $entityManager = $this->getContainer()->get('entityManager');

        $lead1 = $entityManager->createEntity('Lead', []);
        $lead2 = $entityManager->createEntity('Lead', []);

        $account = $entityManager->createEntity('Account', [
            'originalLeadId' => $lead1->getId(),
        ]);

        $entityManager->saveEntity($account);

        $account = $entityManager->getEntity('Account', $account->getId());

        $account->set([
            'originalLeadId' => $lead2->getId(),
        ]);

        $entityManager->saveEntity($account);

        $lead2 = $entityManager->getEntity('Lead', $lead2->getId());

        $this->assertEquals($account->getId(), $lead2->get('createdAccountId'));

        $lead1 = $entityManager->getEntity('Lead', $lead1->getId());

        $this->assertEquals(null, $lead1->get('createdAccountId'));
    }

    private static function sortArray(array $array): array
    {
        sort($array);

        return $array;
    }
}
