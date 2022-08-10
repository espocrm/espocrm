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

namespace Espo\Services;

use Laminas\Mail\Message;

use Espo\ORM\Entity;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Mail\Account\PersonalAccount\Service as AccountService;
use Espo\Core\Record\CreateParams;

use Espo\Entities\EmailAccount as EmailAccountEntity;
use Espo\Entities\User;

use Espo\Core\Di;

use Throwable;
use stdClass;

/**
 * @extends Record<\Espo\Entities\EmailAccount>
 */
class EmailAccount extends Record implements

    Di\CryptAware
{
    use Di\CryptSetter;

    protected function filterInput($data)
    {
        parent::filterInput($data);

        if (property_exists($data, 'password')) {
            $data->password = $this->crypt->encrypt($data->password);
        }

        if (property_exists($data, 'smtpPassword')) {
            $data->smtpPassword = $this->crypt->encrypt($data->smtpPassword);
        }
    }

    public function processValidation(Entity $entity, $data)
    {
        parent::processValidation($entity, $data);

        if ($entity->get('useImap')) {
            if (!$entity->get('fetchSince')) {
                throw new BadRequest("EmailAccount validation: fetchSince is required.");
            }
        }
    }

    public function create(stdClass $data, CreateParams $params): Entity
    {
        if (!$this->user->isAdmin()) {
            $count = $this->entityManager
                ->getRDBRepository('EmailAccount')
                ->where([
                    'assignedUserId' => $this->getUser()->getId()
                ])
                ->count();

            if ($count >= $this->getConfig()->get('maxEmailAccountCount', \PHP_INT_MAX)) {
                throw new Forbidden();
            }
        }

        $entity = parent::create($data, $params);

        if (!$this->getUser()->isAdmin()) {
            $entity->set('assignedUserId', $this->getUser()->getId());
        }

        $this->entityManager->saveEntity($entity);

        return $entity;
    }

    public function storeSentMessage(EmailAccountEntity $emailAccount, Message $message): void
    {
        /** @var AccountService $service */
        $service = $this->injectableFactory->create(AccountService::class);

        $service->storeSentMessage($emailAccount->getId(), $message);
    }

    /**
     * @return EmailAccountEntity|null
     */
    public function findAccountForUser(User $user, string $address)
    {
        $emailAccount = $this->entityManager
            ->getRDBRepository('EmailAccount')
            ->where([
                'emailAddress' => $address,
                'assignedUserId' => $user->getId(),
                'status' => 'Active',
            ])
            ->findOne();

        return $emailAccount;
    }

    /**
     * @return ?array<string,mixed>
     */
    public function getSmtpParamsFromAccount(EmailAccountEntity $emailAccount): ?array
    {
        $smtpParams = [];

        $smtpParams['server'] = $emailAccount->get('smtpHost');

        if ($smtpParams['server']) {
            $smtpParams['port'] = $emailAccount->get('smtpPort');
            $smtpParams['auth'] = $emailAccount->get('smtpAuth');
            $smtpParams['security'] = $emailAccount->get('smtpSecurity');

            if ($emailAccount->get('smtpAuth')) {
                $smtpParams['username'] = $emailAccount->get('smtpUsername');
                $smtpParams['password'] = $emailAccount->get('smtpPassword');
                $smtpParams['authMechanism'] = $emailAccount->get('smtpAuthMechanism');
            }

            if (array_key_exists('password', $smtpParams)) {
                $smtpParams['password'] = $this->crypt->decrypt($smtpParams['password']);
            }

            $this->applySmtpHandler($emailAccount, $smtpParams);

            return $smtpParams;
        }

        return null;
    }

    /**
     * @param array<string,mixed> $params
     */
    public function applySmtpHandler(EmailAccountEntity $emailAccount, array &$params): void
    {
        /** @var ?class-string $handlerClassName */
        $handlerClassName = $emailAccount->get('smtpHandler');

        if (!$handlerClassName) {
            return;
        }

        try {
            $handler = $this->injectableFactory->create($handlerClassName);
        }
        catch (Throwable $e) {
            $this->log->error(
                "EmailAccount: Could not create Smtp Handler for account {$emailAccount->getId()}. Error: " .
                $e->getMessage()
            );

            return;
        }

        if (method_exists($handler, 'applyParams')) {
            $handler->applyParams($emailAccount->getId(), $params);
        }
    }
}
