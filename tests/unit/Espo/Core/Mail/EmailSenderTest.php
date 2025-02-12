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

namespace tests\unit\Espo\Core\Mail;

use Laminas\Mail\Transport\Smtp as SmtpTransport;

use Espo\Core\InjectableFactory;
use Espo\Entities\Email;
use Espo\Core\FileStorage\Manager;
use Espo\Core\Mail\Account\Account;
use Espo\Core\Mail\Account\SendingAccountProvider;
use Espo\Core\Mail\ConfigDataProvider;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Mail\Sender;
use Espo\Core\Mail\Smtp\TransportFactory;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;

use PHPUnit\Framework\TestCase;

class EmailSenderTest extends TestCase
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

        $configDataProvider = $this->createMock(ConfigDataProvider::class);

        $sender = new Sender(
            $this->config,
            $entityManager,
            $log,
            $transportFactory,
            $accountProvider,
            $this->createMock(Manager::class),
            $configDataProvider
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

        $configDataProvider
            ->expects($this->any())
            ->method('getOutboundEmailFromAddress')
            ->willReturn(null);
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
