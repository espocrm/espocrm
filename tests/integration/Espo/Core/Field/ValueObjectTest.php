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

namespace tests\integration\Espo\Core\Field;

use Espo\Core\Field\{
    Currency,
    Date,
    DateTime,
    DateTimeOptional,
    Address,
    EmailAddress,
    EmailAddressGroup,
    PhoneNumber,
    PhoneNumberGroup,
    Link,
    LinkParent,
    LinkMultiple,
    LinkMultipleItem,
};

class ValueObjectTest extends \tests\integration\Core\BaseTestCase
{
    public function testAddress()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $entity = $entityManager->getEntity('Account');

        $address = Address
            ::create()
            ->withCity('Test')
            ->withCountry('United States');

        $entity->setBillingAddress($address);

        $entityManager->saveEntity($entity);

        $entity = $entityManager->getEntity('Account', $entity->getId());

        $address = $entity->getBillingAddress();

        $this->assertNotNull($address);

        $this->assertEquals('Test', $address->getCity());

        $entity->setValueObject('billingAddress', null);

        $address = $entity->getValueObject('billingAddress');

        $this->assertNotNull($address);

        $this->assertEquals(null, $address->getCity());
    }

    public function testCurrency()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $opportunity = $entityManager->getEntity('Opportunity');

        $opportunity->set('name', 'opp-1');

        $opportunity->setAmount(new Currency(1000.0, 'USD'));

        $entityManager->saveEntity($opportunity);

        $opportunity = $entityManager->getEntity('Opportunity', $opportunity->getId());

        $currency = $opportunity->getAmount();

        $this->assertNotNull($currency);

        $this->assertEquals(1000.0, $currency->getAmount());
        $this->assertEquals('USD', $currency->getCode());
    }

    public function testDate()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $opportunity = $entityManager->getEntity('Opportunity');

        $opportunity->set('name', 'opp-1');

        $opportunity->setCloseDate(Date::fromString('2021-05-01'));

        $entityManager->saveEntity($opportunity);

        $opportunity = $entityManager->getEntity('Opportunity', $opportunity->getId());

        $closeDate = $opportunity->getCloseDate();

        $this->assertNotNull($closeDate);

        $this->assertEquals('2021-05-01', $closeDate->getString());

        $opportunity->setValueObject('closeDate', null);

        $entityManager->saveEntity($opportunity);

        $opportunity = $entityManager->getEntity('Opportunity', $opportunity->getId());

        $closeDate = $opportunity->getValueObject('closeDate');

        $this->assertNull($closeDate);
    }

    public function testDateTime()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $call = $entityManager->getEntity('Call');

        $call->set('name', 'call-1');

        $call->setValueObject('dateStart', DateTime::fromString('2021-05-01 10:00:00'));

        $entityManager->saveEntity($call);

        $call = $entityManager->getEntity('Call', $call->getId());

        $dateStart = $call->getValueObject('dateStart');

        $this->assertNotNull($dateStart);

        $this->assertEquals('2021-05-01 10:00:00', $dateStart->getString());

        $call->setValueObject('dateStart', null);

        $entityManager->saveEntity($call);

        $call = $entityManager->getEntity('Call', $call->getId());

        $dateStart = $call->getValueObject('dateStart');

        $this->assertNull($dateStart);
    }

    public function testDateTimeOptional()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $meeting = $entityManager->getEntity('Meeting');

        $meeting->set('name', 'meeting-1');

        $meeting->setDateStart(DateTimeOptional::fromString('2021-05-01 10:00:00'));

        $entityManager->saveEntity($meeting);

        $meeting = $entityManager->getEntity('Meeting', $meeting->getId());

        $dateStart = $meeting->getDateStart();

        $this->assertNotNull($dateStart);

        $this->assertEquals('2021-05-01 10:00:00', $dateStart->getString());

        $meeting->setValueObject('dateStart', null);

        $entityManager->saveEntity($meeting);

        $meeting = $entityManager->getEntity('Meeting', $meeting->getId());

        $dateStart = $meeting->getValueObject('dateStart');

        $this->assertNull($dateStart);

        $meeting->setValueObject('dateStart', DateTimeOptional::fromString('2021-05-01'));

        $entityManager->saveEntity($meeting);

        $meeting = $entityManager->getEntity('Meeting', $meeting->getId());

        $dateStart = $meeting->getValueObject('dateStart');

        $this->assertNotNull($dateStart);

        $this->assertEquals('2021-05-01', $dateStart->getString());
    }

    public function testEmailAddress()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $entity = $entityManager->getEntity('Account');

        $group = EmailAddressGroup::create([
            EmailAddress::create('one@test.com'),
            EmailAddress::create('two@test.com')->optedOut(),
            EmailAddress::create('three@test.com')->invalid(),
        ]);

        $entity->setEmailAddressGroup($group);

        $entityManager->saveEntity($entity);

        $entity = $entityManager->getEntity('Account', $entity->getId());

        $group = $entity->getEmailAddressGroup();

        $this->assertEquals('one@test.com', $group->getPrimary()->getAddress());

        $this->assertTrue($group->getList()[1]->isOptedOut() || $group->getList()[2]->isOptedOut());

        $this->assertTrue($group->getList()[1]->isInvalid() || $group->getList()[2]->isInvalid());
    }

    public function testPhoneNumber()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $entity = $entityManager->getEntity('Account');

        $group = PhoneNumberGroup::create([
            PhoneNumber::create('1')->withType('Office'),
            PhoneNumber::create('2')->optedOut(),
            PhoneNumber::create('3')->invalid(),
        ]);

        $entity->setPhoneNumberGroup($group);

        $entityManager->saveEntity($entity);

        $entity = $entityManager->getEntity('Account', $entity->getId());

        $group = $entity->getPhoneNumberGroup();

        $this->assertEquals('1', $group->getPrimary()->getNumber());
        $this->assertEquals('Office', $group->getPrimary()->getType());

        $this->assertTrue($group->getList()[1]->isOptedOut() || $group->getList()[2]->isOptedOut());

        $this->assertTrue($group->getList()[1]->isInvalid() || $group->getList()[2]->isInvalid());
    }

    public function testLink()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $entity = $entityManager->getEntity('Account');

        $entity->setValueObject('assignedUser', Link::create('1'));

        $link = $entity->getValueObject('assignedUser');

        $this->assertEquals('1', $link->getId());

        $entity->setValueObject('assignedUser', null);

        $this->assertNull($entity->getValueObject('assignedUser'));
    }

    public function testLinkParent()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $entity = $entityManager->getEntity('Task');

        $entity->setValueObject('parent', LinkParent::create('Account', 'test-id'));

        $link = $entity->getValueObject('parent');

        $this->assertEquals('test-id', $link->getId());
        $this->assertEquals('Account', $link->getEntityType());

        $entity->setValueObject('parent', null);

        $this->assertNull($entity->getValueObject('parent'));
    }

    public function testLinkMultiple1()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $c1 = $entityManager->createEntity('Contact', []);
        $c2 = $entityManager->createEntity('Contact', []);

        $entity = $entityManager->getEntity('Opportunity');

        $link = LinkMultiple::create([
            LinkMultipleItem::create($c1->id)->withColumnValue('role', 'Decision Maker'),
            LinkMultipleItem::create($c2->id),
        ]);

        $entity->setValueObject('contacts', $link);

        $entityManager->saveEntity($entity);

        $entity = $entityManager->getEntity('Opportunity', $entity->id);

        /**
         * @var LinkMultiple
         */
        $link = $entity->getValueObject('contacts');

        $this->assertEquals(2, $link->getCount());

        $this->assertEquals('Decision Maker', $link->getById($c1->id)->getColumnValue('role'));
        $this->assertEquals(null, $link->getById($c2->id)->getColumnValue('role'));
    }

    public function testLinkMultiple2()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $c1 = $entityManager->createEntity('Contact', []);
        $c2 = $entityManager->createEntity('Contact', []);

        $entity = $entityManager->getEntity('Opportunity');

        $link = LinkMultiple::create([
            LinkMultipleItem::create($c1->id)->withColumnValue('role', 'Decision Maker'),
            LinkMultipleItem::create($c2->id),
        ]);

        $entity->setValueObject('contacts', $link);

        $entityManager->saveEntity($entity);

        $entity = $entityManager->getEntity('Opportunity', $entity->id);

        $entity->loadLinkMultipleField('contacts');

        /**
         * @var LinkMultiple
         */
        $link = $entity->getValueObject('contacts');

        $this->assertEquals(2, $link->getCount());

        $this->assertEquals('Decision Maker', $link->getById($c1->id)->getColumnValue('role'));
    }
}
