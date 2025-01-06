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
use Espo\Core\Field\PhoneNumber;
use Espo\Core\Field\PhoneNumberGroup;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Record\UpdateParams;
use Espo\Modules\Crm\Entities\Contact;

use tests\integration\Core\BaseTestCase;

class PhoneNumberTest extends BaseTestCase
{
    public function testPhoneNumber1(): void
    {
        $entityManager = $this->getContainer()->getByClass(EntityManager::class);

        $contact = $entityManager->getNewEntity(Contact::ENTITY_TYPE);
        $contact->set('phoneNumber', '+1');
        $entityManager->saveEntity($contact);

        $this->assertEquals('+1', $contact->get('phoneNumber'));

        $contact = $entityManager->getEntityById('Contact', $contact->getId());

        /* @var $group1 PhoneNumberGroup */
        $group1 = $contact->getPhoneNumberGroup();

        $this->assertEquals(1, $group1->getCount());

        $this->assertEquals('+1', $group1->getPrimary()->getNumber());

        $group2 = PhoneNumberGroup
            ::create()
            ->withAdded(
                PhoneNumber::create('+2')->invalid()
            )
            ->withAdded(
                PhoneNumber::create('+1')->optedOut()
            );

        $contact->setValueObject('phoneNumber', $group2);

        $entityManager->saveEntity($contact);

        $contact = $entityManager->getEntityById('Contact', $contact->getId());

        /* @var $group3 PhoneNumberGroup */
        $group3 = $contact->getPhoneNumberGroup();

        $this->assertEquals(2, $group3->getCount());
        $this->assertEquals('+2', $group3->getPrimary()->getNumber());
        $this->assertTrue($group3->getPrimary()->isInvalid());
        $this->assertTrue($group3->getList()[1]->isOptedOut());

        $group4 = PhoneNumberGroup
            ::create()
            ->withAdded(
                PhoneNumber::create('+2')->invalid()
            );

        $contact->setValueObject('phoneNumber', $group4);

        $entityManager->saveEntity($contact);

        $contact = $entityManager->getEntityById('Contact', $contact->getId());

        /* @var $group5 PhoneNumberGroup */
        $group5 = $contact->getPhoneNumberGroup();

        $this->assertEquals(1, $group5->getCount());
    }

    public function testPrimaryFirst(): void
    {
        $em = $this->getEntityManager();

        $lead = $em->getRDBRepositoryByClass(Lead::class)
            ->getNew();

        $lead->set('phoneNumberData', [
            (object) ['phoneNumber' => '+0000000001'],
            (object) ['phoneNumber' => '+0000000002'],
            (object) ['phoneNumber' => '+0000000003'],
            (object) ['phoneNumber' => '+0000000004'],
        ]);

        $em->saveEntity($lead);
        $em->refreshEntity($lead);

        $this->assertEquals('+0000000001', $lead->getPhoneNumber());
    }

    public function testPhoneNumber2(): void
    {
        $service = $this->getContainer()->getByClass(ServiceContainer::class)->getByClass(Contact::class);
        $em = $this->getEntityManager();

        /** @var Contact $contact */
        $contact = $em->createEntity(Contact::ENTITY_TYPE);

        /** @noinspection PhpUnhandledExceptionInspection */
        $service->update($contact->getId(), (object) [
            'phoneNumber' => '+11111111111',
        ], UpdateParams::create());

        $em->refreshEntity($contact);

        $this->assertEquals('+11111111111', $contact->getPhoneNumber());
    }
}
