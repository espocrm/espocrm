<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\unit\Espo\Core\Mail;

use Espo\Core\InjectableFactory;

use Laminas\{
    Mail\Transport\Smtp as SmtpTransport,
};

use Espo\Entities\{
    Email,
};

use Espo\Core\{
    FileStorage\Manager,
    Mail\Account\Account,
    Mail\Account\SendingAccountProvider,
    Mail\EmailSender,
    Mail\Sender,
    Mail\Smtp\TransportFactory,
    Mail\SmtpParams,
    ORM\EntityManager,
    Utils\Config,
    Utils\Log};

class EmailSenderTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $entityManager = $this->createMock(EntityManager::class);
        $injectableFactory = $this->createMock(InjectableFactory::class);
        $transportFactory = $this->createMock(TransportFactory::class);
        $this->transport = $this->createMock(SmtpTransport::class);

        $accountProvider = $this->createMock(SendingAccountProvider::class);
        $log = $this->createMock(Log::class);

        $emailSender = new EmailSender(
            $this->config,
            $accountProvider,
            $injectableFactory
        );

        $sender = new Sender(
            $this->config,
            $entityManager,
            $log,
            $transportFactory,
            $accountProvider,
            $this->createMock(Manager::class)
        );

        $this->emailSender = $emailSender;

        $injectableFactory
            ->expects($this->any())
            ->method('createWithBinding')
            ->willReturn($sender);

        $transportFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->transport);


        $account = $this->createMock(Account::class);

        $account
            ->expects($this->once())
            ->method('getSmtpParams')
            ->willReturn(
                SmtpParams::create('test-server', 85)
            );

        $accountProvider
            ->expects($this->once())
            ->method('getSystem')
            ->willReturn($account);

        $this->config
            ->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap([
                    ['outboundEmailFromAddress', null, null],
                ])
            );
    }

    protected function createEmail(array $data) : Email
    {
        $email = $this->getMockBuilder(Email::class)->disableOriginalConstructor()->getMock();

        $email
            ->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($name) use ($data) {
                        return $data[$name] ?? null;
                    }
                )
            );

        $email
            ->expects($this->any())
            ->method('getBodyPlainForSending')
            ->willReturn('test');

        $email
            ->expects($this->any())
            ->method('isNew')
            ->willReturn(true);

        return $email;
    }

    public function testSend1()
    {
        $email = $this->createEmail([
            'name' => 'test',
            'from' => 'test@tester.com',
        ]);

        $this->transport
            ->expects($this->once())
            ->method('send');

        $this->emailSender->send($email);
    }
}
