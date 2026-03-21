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

namespace Espo\Classes\RecordHooks\EmailAccount;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Security\HostCheck;
use Espo\Entities\EmailAccount;
use Espo\Entities\InboundEmail;
use Espo\ORM\Entity;

/**
 * @implements SaveHook<EmailAccount|InboundEmail>
 */
class BeforeSaveValidateHosts implements SaveHook
{
    public function __construct(
        private Config $config,
        private HostCheck $hostCheck,
    ) {}

    public function process(Entity $entity): void
    {
        if ($entity->isAttributeChanged('host') || $entity->isAttributeChanged('port')) {
            $this->validateImap($entity);
        }

        if ($entity->isAttributeChanged('smtpHost') || $entity->isAttributeChanged('smtpPort')) {
            $this->validateSmtp($entity);
        }
    }

    /**
     * @throws Forbidden
     */
    private function validateImap(EmailAccount|InboundEmail $entity): void
    {
        $host = $entity->getHost();
        $port = $entity->getPort();

        if ($host === null || $port === null) {
            return;
        }

        $address = $host . ':' . $port;

        if (in_array($address, $this->getAllowedAddressList())) {
            return;
        }

        if (!$this->hostCheck->isHostAndNotInternal($host)) {
            $message = $this->composeErrorMessage($host, $address);

            throw new Forbidden($message);
        }
    }

    /**
     * @throws Forbidden
     */
    private function validateSmtp(EmailAccount|InboundEmail $entity): void
    {
        $host = $entity->getSmtpHost();
        $port = $entity->getSmtpPort();

        if ($host === null || $port === null) {
            return;
        }

        $address = $host . ':' . $port;

        if (in_array($address, $this->getAllowedAddressList())) {
            return;
        }

        if (!$this->hostCheck->isHostAndNotInternal($host)) {
            $message = $this->composeErrorMessage($host, $address);

            throw new Forbidden($message);
        }
    }

    /**
     * @return string[]
     */
    private function getAllowedAddressList(): array
    {
        return $this->config->get('emailServerAllowedAddressList') ?? [];
    }

    private function composeErrorMessage(string $host, string $address): string
    {
        return "Host '$host' is not allowed as it's internal. " .
            "To allow, add `$address` to the config parameter `emailServerAllowedAddressList`.";
    }
}
