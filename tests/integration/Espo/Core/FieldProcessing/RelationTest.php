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

namespace tests\integration\Espo\Core\FieldProcessing;

use Espo\Core\ORM\EntityManager;
use Espo\Modules\Crm\Entities\Contact;
use tests\integration\Core\BaseTestCase;
use Espo\Modules\Crm\Entities\Opportunity;

class RelationTest extends BaseTestCase
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

        $opportunity = $entityManager->getEntityById('Opportunity', $opportunity->getId());

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

        $opportunity = $entityManager->getEntityById('Opportunity', $opportunity->getId());

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

        $opportunity = $entityManager->getEntityById('Opportunity', $opportunity->getId());

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

        // W/o link-multiple.

        $opp1 = $entityManager->getRDBRepositoryByClass(Opportunity::class)->getNew();
        $entityManager->saveEntity($opp1);

        $opp2 = $entityManager->getRDBRepositoryByClass(Opportunity::class)->getNew();
        $entityManager->saveEntity($opp2);

        $contact = $entityManager->getRDBRepositoryByClass(Contact::class)->getNew();
        $contact->setMultiple([
            'opportunitiesIds' => [$opp1->getId()]
        ]);
        $entityManager->saveEntity($contact);

        $this->assertTrue(
            $entityManager->getRelation($contact, 'opportunities')->isRelatedById($opp1->getId())
        );

        // Do not allow update IDs if no link-multiple field.

        $contact->setMultiple([
            'opportunitiesIds' => [$opp2->getId()]
        ]);
        $entityManager->saveEntity($contact);

        $this->assertTrue(
            $entityManager->getRelation($contact, 'opportunities')->isRelatedById($opp1->getId())
        );

        $this->assertFalse(
            $entityManager->getRelation($contact, 'opportunities')->isRelatedById($opp2->getId())
        );
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

        $lead1 = $entityManager->getEntityById('Lead', $lead1->getId());

        $this->assertNull($lead1->get('createdAccountId'));

        $lead2 = $entityManager->getEntityById('Lead', $lead2->getId());

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

        $account = $entityManager->getEntityById('Account', $account->getId());

        $account->set([
            'originalLeadId' => $lead2->getId(),
        ]);

        $entityManager->saveEntity($account);

        $lead2 = $entityManager->getEntityById('Lead', $lead2->getId());

        $this->assertEquals($account->getId(), $lead2->get('createdAccountId'));

        $lead1 = $entityManager->getEntityById('Lead', $lead1->getId());

        $this->assertEquals(null, $lead1->get('createdAccountId'));
    }

    private static function sortArray(array $array): array
    {
        sort($array);

        return $array;
    }
}
