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

use Espo\Core\{
    Field\EmailAddressGroup,
    Field\EmailAddress,
};

class EmailAddressTest extends \tests\integration\Core\BaseTestCase
{
    public function testEmailAddress1(): void
    {
        /* @var $entityManager EntityManager */
        $entityManager = $this->getContainer()->get('entityManager');

        $contact = $entityManager->createEntity('Contact', [
            'emailAddress' => 'test@test.com',
        ]);

        $contact = $entityManager->getEntity('Contact', $contact->getId());

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

        $contact = $entityManager->getEntity('Contact', $contact->getId());

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

        $contact = $entityManager->getEntity('Contact', $contact->getId());

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

        $contactFetched = $entityManager->getEntity('Contact', $contact->getId());

        /* @var $group EmailAddressGroup */
        $group = $contactFetched->getEmailAddressGroup();

        $this->assertEquals(1, $group->getCount());

        $this->assertEquals('test@test.com', $group->getPrimary()->getAddress());
    }
}
