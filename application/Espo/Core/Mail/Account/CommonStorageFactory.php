<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\Mail\Account;

use DirectoryTree\ImapEngine\Mailbox;
use Espo\Core\InjectableFactory;
use Espo\Core\Mail\Account\Storage\DirectoryTreeStorage;
use Espo\Core\Mail\Account\Storage\Handler;
use Espo\Core\Mail\Account\Storage\Params;

/**
 * @since 9.3.0
 */
class CommonStorageFactory
{
    public function __construct(
        private InjectableFactory $injectableFactory,
    ) {}

    public function create(Params $params): DirectoryTreeStorage
    {
        $handlerClassName = $params->getImapHandlerClassName();

        if ($handlerClassName && $params->getId()) {
            $handler = $this->injectableFactory->create($handlerClassName);

            if ($handler instanceof Handler || method_exists($handler, 'handle')) {
                $params = $handler->handle($params, $params->getId());
            }
        }

        $encryption = match ($params->getSecurity()) {
            Params::SECURITY_SSL => 'ssl',
            Params::SECURITY_START_TLS => 'starttls',
            default => null,
        };

        $authentication = $params->getAuthMechanism() === Params::AUTH_MECHANISM_XOAUTH ?
            'oauth' : 'plain';

        $config = [
            'host' => $params->getHost(),
            'port' => $params->getPort(),
            'username' => $params->getUsername(),
            'password' => $params->getPassword(),
            'encryption' => $encryption,
            'authentication' => $authentication,
        ];

        $mailbox = new Mailbox($config);

        return new DirectoryTreeStorage($mailbox);
    }
}
