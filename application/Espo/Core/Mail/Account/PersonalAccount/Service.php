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

namespace Espo\Core\Mail\Account\PersonalAccount;

use Espo\Core\Exceptions\ErrorSilent;
use Espo\Core\Mail\Account\Util\NotificationHelper;
use Espo\Core\Mail\Exceptions\ImapError;
use Espo\Core\Mail\Exceptions\NoImap;
use Espo\Core\Utils\Log;
use Espo\Core\Mail\Account\Account as Account;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\Error;
use Espo\Core\Mail\Account\Fetcher;
use Espo\Core\Mail\Account\Storage\Params;
use Espo\Core\Mail\Account\StorageFactory;
use Espo\Entities\User;
use Espo\Core\Mail\Sender\Message;

use Laminas\Mail\Exception\ExceptionInterface;

use Exception;

class Service
{
    public function __construct(
        private Fetcher $fetcher,
        private AccountFactory $accountFactory,
        private StorageFactory $storageFactory,
        private User $user,
        private Log $log,
        private NotificationHelper $notificationHelper
    ) {}

    /**
     * @param string $id Account ID.
     * @throws Error
     * @throws NoImap
     * @throws ImapError
     */
    public function fetch(string $id): void
    {
        $account = $this->accountFactory->create($id);

        try {
            $this->fetcher->fetch($account);
        } catch (ImapError $e) {
            $this->notificationHelper->processImapError($account);

            throw $e;
        }

        $account->updateConnectedAt();
    }

    /**
     * @return string[]
     * @throws Forbidden
     * @throws Error
     * @throws ImapError
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
                ->withPassword($this->getPassword($params, $account))
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
                ->withPassword($this->getPassword($params, $account))
                ->withImapHandlerClassName($account->getImapHandlerClassName());
        }

        try {
            $storage = $this->storageFactory->createWithParams($params);
            $storage->getFolderNames();
        } catch (Exception $e) {
            $this->log->warning("IMAP test connection failed; {message}", [
                'exception' => $e,
                'message' => $e->getMessage(),
            ]);

            $message = $e instanceof ExceptionInterface || $e instanceof ImapError ?
                $e->getMessage() : '';

            throw new ErrorSilent($message);
        }
    }

    private function getPassword(Params $params, Account $account): ?string
    {
        $password = $params->getPassword();

        if ($password !== null) {
            return $password;
        }

        $imapParams = $account->getImapParams();

        return $imapParams?->getPassword();
    }

    /**
     * @param string $id Account ID.
     * @throws Error
     * @throws ImapError
     * @throws NoImap
     */
    public function storeSentMessage(string $id, Message $message): void
    {
        $account = $this->accountFactory->create($id);

        $folder = $account->getSentFolder();

        if (!$folder) {
            throw new Error("No sent folder for Email Account $id.");
        }

        $storage = $this->storageFactory->create($account);

        $storage->appendMessage($message->toString(), $folder);
    }
}
