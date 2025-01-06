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

namespace Espo\Core\Sms;

use Espo\Core\InjectableFactory;
use Espo\Entities\Sms as SmsEntity;
use Espo\Core\Utils\Config;

class SmsSender
{
    private ?Sender $sender = null;

    public function __construct(
        private InjectableFactory $injectableFactory,
        private Config $config
    ) {}

    private function getSender(): Sender
    {
        if ($this->sender === null) {
            // Sender factory can throw an exception (if no 'smsProvider' in config).
            // Better it be thrown when sending rather than when instantiating
            // constructor dependencies.
            $this->sender = $this->injectableFactory->createResolved(Sender::class);
        }

        return $this->sender;
    }

    public function send(SmsEntity $sms): void
    {
        $systemFromNumber = $this->config->get('outboundSmsFromNumber');

        if ($sms->getFromNumber() === null && $systemFromNumber) {
            $sms->setFromNumber($systemFromNumber);
        }

        $this->getSender()->send($sms);

        $sms->setAsSent();
    }
}
