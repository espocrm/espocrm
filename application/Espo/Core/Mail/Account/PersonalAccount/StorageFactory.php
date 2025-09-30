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

use Espo\Core\Mail\Account\Storage\Params;
use Espo\Core\Mail\Account\StorageFactory as StorageFactoryInterface;
use Espo\Core\Mail\Account\Account;
use Espo\Core\Mail\Exceptions\ImapError;
use Espo\Core\Mail\Exceptions\NoImap;
use Espo\Core\Mail\Mail\Storage\Imap;
use Espo\Core\Mail\Account\Storage\LaminasStorage;
use Espo\Core\Utils\Log;
use Espo\Core\InjectableFactory;
use Espo\ORM\Name\Attribute;

use Laminas\Mail\Protocol\Exception\RuntimeException as ProtocolRuntimeException;
use Laminas\Mail\Storage\Exception\InvalidArgumentException;
use Laminas\Mail\Storage\Exception\RuntimeException;

use LogicException;
use Throwable;

class StorageFactory implements StorageFactoryInterface
{
    public function __construct(
        private Log $log,
        private InjectableFactory $injectableFactory,
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
        $rawParams = [
            'host' => $params->getHost(),
            'port' => $params->getPort(),
            'username' => $params->getUsername(),
            'password' => $params->getPassword(),
            'emailAddress' => $params->getEmailAddress(),
            'userId' => $params->getUserId(),
            'imapHandler' => $params->getImapHandlerClassName(),
            Attribute::ID => $params->getId(),
        ];

        if ($params->getSecurity()) {
            $rawParams['security'] = $params->getSecurity();
        }

        /** @var ?class-string $handlerClassName */
        $handlerClassName = $rawParams['imapHandler'] ?? null;

        $handler = null;
        $imapParams = null;

        if ($handlerClassName && !empty($rawParams['id'])) {
            try {
                $handler = $this->injectableFactory->create($handlerClassName);
            } catch (Throwable $e) {
                $this->log->error(
                    "EmailAccount: Could not create Imap Handler. Error: " . $e->getMessage()
                );
            }

            if ($handler && method_exists($handler, 'prepareProtocol')) {
                // for backward compatibility
                $rawParams['ssl'] = $rawParams['security'] ?? null;

                $imapParams = $handler->prepareProtocol($rawParams['id'], $rawParams);
            }
        }

        if (!$imapParams) {
            $imapParams = [
                'host' => $rawParams['host'],
                'port' => $rawParams['port'],
                'user' => $rawParams['username'],
                'password' => $rawParams['password'],
            ];

            if (!empty($rawParams['security'])) {
                $imapParams['ssl'] = $rawParams['security'];
            }
        }

        try {
            $storage = new Imap($imapParams);
        } catch (RuntimeException|InvalidArgumentException|ProtocolRuntimeException $e) {
            throw new ImapError($e->getMessage(), 0, $e);
        }

        return new LaminasStorage($storage);
    }
}
