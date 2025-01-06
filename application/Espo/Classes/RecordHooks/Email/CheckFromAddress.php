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

namespace Espo\Classes\RecordHooks\Email;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Mail\Account\SendingAccountProvider;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\Config;
use Espo\Entities\Email;
use Espo\Entities\User;
use Espo\ORM\Entity;

/**
 * @implements SaveHook<Email>
 */
class CheckFromAddress implements SaveHook
{
    public function __construct(
        private User $user,
        private SendingAccountProvider $sendingAccountProvider,
        private Config $config,
        private Acl $acl,
    ) {}

    public function process(Entity $entity): void
    {
        if ($this->user->isAdmin()) {
            return;
        }

        $fromAddress = $entity->getFromAddress();

        // Should be after 'getFromAddress'.
        if (!$entity->isAttributeChanged('from')) {
            return;
        }

        if (!$fromAddress) {
            throw new BadRequest("No 'from' address");
        }

        if ($this->acl->checkScope('Import')) {
            return;
        }

        $fromAddress = strtolower($fromAddress);

        foreach ($this->user->getEmailAddressGroup()->getAddressList() as $address) {
            if ($fromAddress === strtolower($address)) {
                return;
            }
        }

        if ($this->sendingAccountProvider->getShared($this->user, $fromAddress)) {
            return;
        }

        $system = $this->sendingAccountProvider->getSystem();

        if (
            $system &&
            $this->config->get('outboundEmailIsShared') &&
            $system->getEmailAddress()
        ) {
            if ($fromAddress === strtolower($system->getEmailAddress())) {
                return;
            }
        }

        throw new Forbidden("Not allowed 'from' address.");
    }
}
