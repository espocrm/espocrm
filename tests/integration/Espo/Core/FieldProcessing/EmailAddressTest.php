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
use Espo\Modules\Crm\Entities\Lead;
use Espo\Core\Field\EmailAddress;
use Espo\Core\Field\EmailAddressGroup;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Record\UpdateParams;
use Espo\Modules\Crm\Entities\Contact;

use tests\integration\Core\BaseTestCase;

class EmailAddressTest extends BaseTestCase
{
    public function testEmailAddress1(): void
    {
        /* @var $entityManager EntityManager */
        $entityManager = $this->getContainer()->get('entityManager');

        $contact = $entityManager->createEntity('Contact', [
            'emailAddress' => 'test@test.com',
        ]);

        $contact = $entityManager->getEntityById('Contact', $contact->getId());

        /* @var $group1 EmailAddressGroup */
        $group1 = $contact->getEmailAddressGroup();

        $this->assertEquals(1, $group1->getCount());

        $this->assertEquals('test@test.com', $group1->getPrimary()->getAddress());

        $group2 = EmailAddressGroup::create()
            ->withAdded(
                EmailAddress::create('test-a@test.com')->invalid()
            )
            ->withAdded(
                EmailAddress::create('test@test.com')->optedOut()
            );

        $contact->setValueObject('emailAddress', $group2);

        $entityManager->saveEntity($contact);

        $contact = $entityManager->getEntityById('Contact', $contact->getId());

        /* @var $group3 EmailAddressGroup */
        $group3 = $contact->getEmailAddressGroup();

        $this->assertEquals(2, $group3->getCount());
        $this->assertEquals('test-a@test.com', $group3->getPrimary()->getAddress());
        $this->assertTrue($group3->getPrimary()->isInvalid());
        $this->assertTrue($group3->getList()[1]->isOptedOut());

        $group4 = EmailAddressGroup::create()
            ->withAdded(
                EmailAddress::create('test-a@test.com')->invalid()
            );

        $contact->setValueObject('emailAddress', $group4);

        $entityManager->saveEntity($contact);

        $contact = $entityManager->getEntityById('Contact', $contact->getId());

        /* @var $group5 EmailAddressGroup */
        $group5 = $contact->getEmailAddressGroup();

        $this->assertEquals(1, $group5->getCount());
    }

    public function testEmailAddress2(): void
    {
        /* @var $entityManager EntityManager */
        $entityManager = $this->getContainer()->get('entityManager');

        $lead = $entityManager->createEntity('Lead', [
            'emailAddress' => 'test@test.com',
        ]);

        $contact = $entityManager->createEntity('Contact', [
            'emailAddress' => 'test@test.com',
        ]);

        $contactFetched = $entityManager->getEntityById('Contact', $contact->getId());

        /* @var $group EmailAddressGroup */
        $group = $contactFetched->getEmailAddressGroup();

        $this->assertEquals(1, $group->getCount());

        $this->assertEquals('test@test.com', $group->getPrimary()->getAddress());
    }

    public function testPrimaryFirst(): void
    {
        $em = $this->getEntityManager();

        $lead = $em->getRDBRepositoryByClass(Lead::class)
            ->getNew();

        $lead->set('emailAddressData', [
            (object) ['emailAddress' => 'test-1@test.com'],
            (object) ['emailAddress' => 'test-2@test.com'],
            (object) ['emailAddress' => 'test-3@test.com'],
            (object) ['emailAddress' => 'test-4@test.com'],
        ]);

        $em->saveEntity($lead);
        $em->refreshEntity($lead);

        $this->assertEquals('test-1@test.com', $lead->getEmailAddress());
    }

    public function testEmailAddress3(): void
    {
        $service = $this->getContainer()->getByClass(ServiceContainer::class)->getByClass(Contact::class);
        $em = $this->getEntityManager();

        /** @var Contact $contact */
        $contact = $em->createEntity(Contact::ENTITY_TYPE);

        /** @noinspection PhpUnhandledExceptionInspection */
        $service->update($contact->getId(), (object) [
            'emailAddress' => 'test@test.com',
        ], UpdateParams::create());

        $em->refreshEntity($contact);

        $this->assertEquals('test@test.com', $contact->getEmailAddress());
    }
}
