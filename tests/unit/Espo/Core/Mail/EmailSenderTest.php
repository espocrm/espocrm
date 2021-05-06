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

namespace tests\unit\Espo\Core\Mail;

use Laminas\{
    Mail\Transport\Smtp as SmtpTransport,
};


use Espo\Entities\{
    Email,
};

use Espo\Core\{
    Mail\EmailSender,
    Mail\SmtpTransportFactory,
    ORM\EntityManager,
    Utils\Config,
    ServiceFactory,
    Utils\Log,
};

use Espo\Services\InboundEmail as InboundEmailService;

class EmailSenderTest extends \PHPUnit\Framework\TestCase
{
    public function setUp() : void
    {
        $config = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $serviceFactory = $this->getMockBuilder(ServiceFactory::class)->disableOriginalConstructor()->getMock();
        $transportFactory = $this->getMockBuilder(SmtpTransportFactory::class)->disableOriginalConstructor()->getMock();

        $this->transport = $this->getMockBuilder(SmtpTransport::class)->disableOriginalConstructor()->getMock();

        $log = $this->createMock(Log::class);

        $emailSender = new EmailSender($config, $entityManager, $serviceFactory, $transportFactory, $log);

        $transportFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->transport);

        $this->emailSender = $emailSender;
        $this->config = $config;

        $config
            ->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap([
                    ['outboundEmailFromAddress', null, null],
                    ['smtpServer', null, 'test-server'],
                    ['smtpPort', null, '85'],
                ])
            );

        $entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap([
                    ['outboundEmailFromAddress', null, 'test@from.com'],
                    ['smtpServer', null, 'test-server'],
                ])
            );

        $inboundEmailService = $this->createMock(InboundEmailService::class);

        $serviceFactory
            ->expects($this->any())
            ->method('create')
            ->will(
                $this->returnValueMap([
                    ['InboundEmail', $inboundEmailService],
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
