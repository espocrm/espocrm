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

namespace tests\integration\Espo\Core\Authentication\TwoFactor;

use Espo\Core\Api\Method;
use Espo\Core\Authentication\HeaderKey;
use Espo\Core\Authentication\Result;
use Espo\Core\Authentication\TwoFactor\Email\EmailLogin;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Name\Field;
use Espo\Entities\Email;
use Espo\Entities\TwoFactorCode;
use Espo\Entities\User;
use Espo\Tools\User\UserDataProvider;
use tests\integration\Core\BaseTestCase;

class EmailLoginTest extends BaseTestCase
{
    /**
     * The code is sent on the first 2FA step, before the user is set in the container.
     */
    public function testSendCodeWithNoCurrentUser(): void
    {
        $this->setConfigParams([
            'auth2FA' => true,
            'auth2FAMethodList' => [EmailLogin::NAME],
        ]);

        $emailAddress = 'test-2fa@test.com';

        $user = $this->createUser([
            User::FIELD_USER_NAME => 'test-2fa',
            Field::EMAIL_ADDRESS => $emailAddress,
        ]);

        $em = $this->getEntityManager();

        $userDataProvider = $this->getInjectableFactory()->create(UserDataProvider::class);

        $userData = $userDataProvider->get($user->getId());
        $userData->set('auth2FA', true);
        $userData->set('auth2FAMethod', EmailLogin::NAME);

        $em->saveEntity($userData);

        $this->setApplication(
            $this->createApplication(reuse: true, noUser: true)
        );

        $this->assertFalse($this->getContainer()->has('user'));

        /** @var ?Email $sentEmail */
        $sentEmail = null;

        $emailSender = $this->createMock(EmailSender::class);

        $emailSender
            ->expects(self::once())
            ->method('send')
            ->willReturnCallback(function (Email $email) use (&$sentEmail) {
                $sentEmail = $email;
            });

        $login = $this->getInjectableFactory()->createWithBinding(
            EmailLogin::class,
            BindingContainerBuilder::create()
                ->bindInstance(EmailSender::class, $emailSender)
                ->build()
        );

        $em->refreshEntity($user);

        $request = $this->createRequest(method: Method::GET);

        $result = $login->login(Result::success($user), $request);

        $this->assertTrue($result->isSecondStepRequired());

        $codeEntity = $em->getRDBRepositoryByClass(TwoFactorCode::class)
            ->where([
                'userId' => $user->getId(),
                'method' => EmailLogin::NAME,
                'isActive' => true,
            ])
            ->findOne();

        $this->assertNotNull($codeEntity);
        $this->assertNotNull($sentEmail);
        $this->assertStringContainsString($codeEntity->getCode(), $sentEmail->getBody() ?? '');
        $this->assertContains($emailAddress, $sentEmail->getToAddressList());

        //

        $request = $this->createRequest(
            method: Method::GET,
            headers: [HeaderKey::AUTHORIZATION_CODE => $codeEntity->getCode()],
        );

        $result = $login->login(Result::success($user), $request);

        $this->assertTrue($result->isSuccess());
    }
}
