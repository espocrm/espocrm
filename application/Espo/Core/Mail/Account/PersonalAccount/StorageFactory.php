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

namespace Espo\Core\Mail\Account\PersonalAccount;

use Espo\Core\Mail\Account\CommonStorageFactory;
use Espo\Core\Mail\Account\Storage\Params;
use Espo\Core\Mail\Account\StorageFactory as StorageFactoryInterface;
use Espo\Core\Mail\Account\Account;
use Espo\Core\Mail\Exceptions\NoImap;
use Espo\Core\Mail\Account\Storage\LaminasStorage;

use LogicException;

class StorageFactory implements StorageFactoryInterface
{
    public function __construct(
        private CommonStorageFactory $commonStorageFactory,
    ) {}

    public function create(Account $account): LaminasStorage
    {
        $userLink = $account->getUser();

        if (!$userLink) {
            throw new LogicException("No user for mail account.");
        }

        $userId = $userLink->getId();

        $imapParams = $account->getImapParams();

        if (!$imapParams) {
            throw new NoImap("No IMAP params.");
        }

        $params = Params::createBuilder()
            ->setHost($imapParams->getHost())
            ->setPort($imapParams->getPort())
            ->setSecurity($imapParams->getSecurity())
            ->setUsername($imapParams->getUsername())
            ->setPassword($imapParams->getPassword())
            ->setEmailAddress($account->getEmailAddress())
            ->setUserId($userId)
            ->setId($account->getId())
            ->setImapHandlerClassName($account->getImapHandlerClassName())
            ->build();

        return $this->createWithParams($params);
    }

    public function createWithParams(Params $params): LaminasStorage
    {
        return $this->commonStorageFactory->create($params);
    }
}
