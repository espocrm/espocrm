<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Mail\Account\PersonalAccount;

use Laminas\Mail\Message;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\Error;

use Espo\Core\Mail\Account\Fetcher;
use Espo\Core\Mail\Account\Storage\Params;
use Espo\Core\Mail\Account\StorageFactory;

use Espo\Core\Utils\Crypt;

use Espo\Entities\User;

class Service
{
    private Fetcher $fetcher;

    private AccountFactory $accountFactory;

    private Crypt $crypt;

    private StorageFactory $storageFactory;

    private User $user;

    public function __construct(
        Fetcher $fetcher,
        AccountFactory $accountFactory,
        Crypt $crypt,
        StorageFactory $storageFactory,
        User $user
    ) {
        $this->fetcher = $fetcher;
        $this->accountFactory = $accountFactory;
        $this->crypt = $crypt;
        $this->storageFactory = $storageFactory;
        $this->user = $user;
    }

    /**
     * @param string $id Account ID.
     * @throws Error
     */
    public function fetch(string $id): void
    {
        $account = $this->accountFactory->create($id);

        $this->fetcher->fetch($account);
    }

    /**
     * @return string[]
     * @throws Forbidden
     * @throws Error
     */
    public function getFolderList(Params $params): array
    {
        $userId = $params->getUserId();

        if (
            $userId &&
            !$this->user->isAdmin() &&
            $userId !== $this->user->getId()
        ) {
            throw new Forbidden();
        }

        if ($params->getId()) {
            $account = $this->accountFactory->create($params->getId());

            $params = $params
                ->withPassword(
                    $params->getPassword() ??
                    $this->crypt->decrypt($account->getPassword() ?? '')
                )
                ->withImapHandlerClassName($account->getImapHandlerClassName());
        }

        $storage = $this->storageFactory->createWithParams($params);

        return $storage->getFolderNames();
    }

    /**
     * @throws Forbidden
     * @throws Error
     */
    public function testConnection(Params $params): void
    {
        $userId = $params->getUserId();

        if (
            $userId &&
            !$this->user->isAdmin() &&
            $userId !== $this->user->getId()
        ) {
            throw new Forbidden();
        }

        if (!$params->getId() && $params->getPassword() === null) {
            throw new Forbidden();
        }

        if ($params->getId()) {
            $account = $this->accountFactory->create($params->getId());

            if (
                !$this->user->isAdmin() &&
                $account->getUser()->getId() !== $this->user->getId()
            ) {
                throw new Forbidden();
            }

            $params = $params
                ->withPassword(
                    $params->getPassword() ??
                    $this->crypt->decrypt($account->getPassword() ?? '')
                )
                ->withImapHandlerClassName($account->getImapHandlerClassName());
        }

        $storage = $this->storageFactory->createWithParams($params);

        $storage->getFolderNames();
    }

    /**
     * @param string $id Account ID.
     * @throws Error
     */
    public function storeSentMessage(string $id, Message $message): void
    {
        $account = $this->accountFactory->create($id);

        $folder = $account->getSentFolder();

        if (!$folder) {
            throw new Error("No sent folder for Email Account {$id}.");
        }

        $storage = $this->storageFactory->create($account);

        $storage->appendMessage($message->toString(), $folder);
    }
}
