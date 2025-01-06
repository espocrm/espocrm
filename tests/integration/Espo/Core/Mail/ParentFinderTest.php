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

namespace tests\integration\Espo\Core\Mail;

use Espo\Core\Mail\Importer\DefaultParentFinder;
use Espo\Core\Mail\Message;
use Espo\Entities\Email;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use tests\integration\Core\BaseTestCase;

class ParentFinderTest extends BaseTestCase
{
    public function testReplied(): void
    {
        $em = $this->getEntityManager();

        $account = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'Test'
        ]);

        $emailOne = $em->createEntity(Email::ENTITY_TYPE, [
            'parentId' => $account->getId(),
            'parentType' => $account->getEntityType(),
            'status' => Email::STATUS_ARCHIVED,
        ]);

        /** @var Email $email */
        $email = $em->getNewEntity(Email::ENTITY_TYPE);

        $email->set([
            'repliedId' => $emailOne->getId(),
        ]);

        $finder = $this->getInjectableFactory()->create(DefaultParentFinder::class);

        $message = $this->createMock(Message::class);

        $parent = $finder->find($email, $message);

        $this->assertInstanceOf(Account::class, $parent);
        $this->assertEquals($account->getId(), $parent->getId());
    }

    public function testReferences(): void
    {
        $em = $this->getEntityManager();

        $account = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'Test'
        ]);

        /** @var Email $email */
        $email = $em->getNewEntity(Email::ENTITY_TYPE);

        $finder = $this->getInjectableFactory()->create(DefaultParentFinder::class);

        $message = $this->createMock(Message::class);

        $message
            ->expects($this->once())
            ->method('getHeader')
            ->with('References')
            ->willReturn('Account/' . $account->getId(). '/1667915635/6605@espo');

        $parent = $finder->find($email, $message);

        $this->assertInstanceOf(Account::class, $parent);
        $this->assertEquals($account->getId(), $parent->getId());
    }

    public function testFromAddressAccount(): void
    {
        $em = $this->getEntityManager();

        /** @var Account $subject */
        $subject = $em->createEntity(Account::ENTITY_TYPE, [
            'emailAddress' => 'subject@test.com',
        ]);

        /** @var Email $email */
        $email = $em->getNewEntity(Email::ENTITY_TYPE);

        $email->setFromAddress($subject->getEmailAddress());

        $finder = $this->getInjectableFactory()->create(DefaultParentFinder::class);

        $message = $this->createMock(Message::class);

        $parent = $finder->find($email, $message);

        $this->assertInstanceOf(Account::class, $parent);
        $this->assertEquals($subject->getId(), $parent->getId());
    }

    public function testFromAddressContact(): void
    {
        $em = $this->getEntityManager();

        /** @var Contact $subject */
        $subject = $em->createEntity(Contact::ENTITY_TYPE, [
            'emailAddress' => 'subject@test.com',
        ]);

        /** @var Email $email */
        $email = $em->getNewEntity(Email::ENTITY_TYPE);

        $email->setFromAddress($subject->getEmailAddress());

        $finder = $this->getInjectableFactory()->create(DefaultParentFinder::class);

        $message = $this->createMock(Message::class);

        $parent = $finder->find($email, $message);

        $this->assertInstanceOf(Contact::class, $parent);
        $this->assertEquals($subject->getId(), $parent->getId());
    }

    public function testFromAddressLead(): void
    {
        $em = $this->getEntityManager();

        /** @var Lead $subject */
        $subject = $em->createEntity(Lead::ENTITY_TYPE, [
            'emailAddress' => 'subject@test.com',
        ]);

        /** @var Email $email */
        $email = $em->getNewEntity(Email::ENTITY_TYPE);

        $email->setFromAddress($subject->getEmailAddress());

        $finder = $this->getInjectableFactory()->create(DefaultParentFinder::class);

        $message = $this->createMock(Message::class);

        $parent = $finder->find($email, $message);

        $this->assertInstanceOf(Lead::class, $parent);
        $this->assertEquals($subject->getId(), $parent->getId());
    }

    public function testReplyToAddress(): void
    {
        $em = $this->getEntityManager();

        /** @var Lead $subject */
        $subject = $em->createEntity(Lead::ENTITY_TYPE, [
            'emailAddress' => 'subject@test.com',
        ]);

        /** @var Email $email */
        $email = $em->getNewEntity(Email::ENTITY_TYPE);

        $email->setFromAddress('any@address.com');
        $email->addReplyToAddress($subject->getEmailAddress());

        $finder = $this->getInjectableFactory()->create(DefaultParentFinder::class);

        $message = $this->createMock(Message::class);

        $parent = $finder->find($email, $message);

        $this->assertInstanceOf(Lead::class, $parent);
        $this->assertEquals($subject->getId(), $parent->getId());
    }

    public function testToAddress(): void
    {
        $em = $this->getEntityManager();

        /** @var Lead $subject */
        $subject = $em->createEntity(Lead::ENTITY_TYPE, [
            'emailAddress' => 'subject@test.com',
        ]);

        /** @var Email $email */
        $email = $em->getNewEntity(Email::ENTITY_TYPE);

        $email->setFromAddress('any@address.com');
        $email->addToAddress($subject->getEmailAddress());

        $finder = $this->getInjectableFactory()->create(DefaultParentFinder::class);

        $message = $this->createMock(Message::class);

        $parent = $finder->find($email, $message);

        $this->assertInstanceOf(Lead::class, $parent);
        $this->assertEquals($subject->getId(), $parent->getId());
    }
}
