<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace integration\Espo\Core\Authentication\Oidc;

use Espo\Core\Authentication\Oidc\UserProvider\DefaultUserInfoPopulator;
use Espo\Core\Authentication\Oidc\UserProvider\UserInfo;
use Espo\Core\Field\EmailAddress;
use Espo\Core\Field\EmailAddressGroup;
use Espo\Core\Field\PhoneNumber;
use Espo\Core\Field\PhoneNumberGroup;
use Espo\Entities\User;
use tests\integration\Core\BaseTestCase;

class UserInfoPopulatorTest extends BaseTestCase
{
    public function testPopulate(): void
    {
        $em = $this->getEntityManager();

        $user = $em->getRDBRepositoryByClass(User::class)->getNew();

        $user
            ->setUserName('name')
            ->setPhoneNumberGroup(
                PhoneNumberGroup::create([
                    PhoneNumber::create('+100'),
                    PhoneNumber::create('+200'),
                ])
            )
            ->setEmailAddressGroup(
                EmailAddressGroup::create([
                    EmailAddress::create('test1@test.com'),
                    EmailAddress::create('test2@test.com'),
                ]),
            );

        $em->saveEntity($user);

        $userInfo = $this->createMock(UserInfo::class);
        $userInfo
            ->method('get')
            ->willReturnMap([
                ['email', 'test3@test.com'],
                ['phone_number', '+300'],
            ]);

        $populator = $this->getInjectableFactory()->create(DefaultUserInfoPopulator::class);

        //

        $populator->populate($userInfo, $user);

        $em->saveEntity($user);
        $em->refreshEntity($user);

        $this->assertCount(2, $user->getEmailAddressGroup()->getList());
        $this->assertCount(2, $user->getPhoneNumberGroup()->getList());

        $this->assertEquals('test3@test.com', $user->getEmailAddress());
        $this->assertEquals('+300', $user->getPhoneNumber());

        $this->assertEquals('test2@test.com', $user->getEmailAddressGroup()->getList()[1]->getAddress());
        $this->assertEquals('+200', $user->getPhoneNumberGroup()->getList()[1]->getNumber());

        //

        $userInfo = $this->createMock(UserInfo::class);
        $userInfo
            ->method('get')
            ->willReturnMap([
                ['email', null],
                ['phone_number', null],
            ]);

        $populator->populate($userInfo, $user);

        $em->saveEntity($user);
        $em->refreshEntity($user);

        $this->assertEquals('test2@test.com', $user->getEmailAddress());
        $this->assertEquals('+200', $user->getPhoneNumber());

        $this->assertCount(1, $user->getEmailAddressGroup()->getList());
        $this->assertCount(1, $user->getPhoneNumberGroup()->getList());
    }
}
