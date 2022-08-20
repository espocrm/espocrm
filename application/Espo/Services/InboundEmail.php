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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Mail\Account\GroupAccount\Service as AccountService;

use Espo\Services\Record as RecordService;

use Espo\Entities\InboundEmail as InboundEmailEntity;
use Espo\Entities\User;

use Espo\Core\Di;

use Throwable;

/**
 * @extends Record<\Espo\Entities\InboundEmail>
 */
class InboundEmail extends RecordService implements

    Di\CryptAware,
    Di\EmailSenderAware
{
    use Di\CryptSetter;
    use Di\EmailSenderSetter;

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

    public function findAccountForSending(string $emailAddress): ?InboundEmailEntity
    {
        /** @var ?InboundEmailEntity $inboundEmail */
        $inboundEmail = $this->entityManager
            ->getRDBRepository('InboundEmail')
            ->where([
                'status' => 'Active',
                'useSmtp' => true,
                'smtpHost!=' => null,
                'emailAddress' => $emailAddress,
            ])
            ->findOne();

        return $inboundEmail;
    }

    /**
     * @param string $emailAddress
     * @return InboundEmailEntity|null
     */
    public function findSharedAccountForUser(User $user, $emailAddress)
    {
        $groupEmailAccountPermission = $this->getAclManager()->get($user, 'groupEmailAccountPermission');

        if (!$groupEmailAccountPermission || $groupEmailAccountPermission === 'no') {
            return null;
        }

        if ($groupEmailAccountPermission === 'team') {
            /** @var string[] $teamIdList */
            $teamIdList = $user->getLinkMultipleIdList('teams');

            if (!count($teamIdList)) {
                return null;
            }

            /** @var ?InboundEmailEntity */
            return $this->entityManager
                ->getRDBRepository(InboundEmailEntity::ENTITY_TYPE)
                ->distinct()
                ->join('teams')
                ->where([
                    'status' => 'Active',
                    'useSmtp' => true,
                    'smtpIsShared' => true,
                    'teamsMiddle.teamId' => $teamIdList,
                    'emailAddress' => $emailAddress,
                ])
                ->findOne();
        }

        if ($groupEmailAccountPermission === 'all') {
            /** @var ?InboundEmailEntity */
            return $this->entityManager
                ->getRDBRepository(InboundEmailEntity::ENTITY_TYPE)
                ->where([
                    'status' => 'Active',
                    'useSmtp' => true,
                    'smtpIsShared' => true,
                    'emailAddress' => $emailAddress,
                ])
                ->findOne();
        }

        return null;
    }

    public function storeSentMessage(InboundEmailEntity $emailAccount, Message $message): void
    {
        /** @var AccountService $service */
        $service = $this->injectableFactory->create(AccountService::class);

        $service->storeSentMessage($emailAccount->getId(), $message);
    }

    /**
     * @return ?array<string,mixed>
     */
    public function getSmtpParamsFromAccount(InboundEmailEntity $emailAccount): ?array
    {
        $smtpParams = [];

        $smtpParams['server'] = $emailAccount->get('smtpHost');

        if ($smtpParams['server']) {
            $smtpParams['port'] = $emailAccount->get('smtpPort');
            $smtpParams['auth'] = $emailAccount->get('smtpAuth');
            $smtpParams['security'] = $emailAccount->get('smtpSecurity');
            $smtpParams['username'] = $emailAccount->get('smtpUsername');
            $smtpParams['password'] = $emailAccount->get('smtpPassword');

            if ($emailAccount->get('smtpAuth')) {
                $smtpParams['authMechanism'] = $emailAccount->get('smtpAuthMechanism');
            }

            if ($emailAccount->get('fromName')) {
                $smtpParams['fromName'] = $emailAccount->get('fromName');
            }

            if ($emailAccount->get('emailAddress')) {
                $smtpParams['fromAddress'] = $emailAccount->get('emailAddress');
            }

            if (array_key_exists('password', $smtpParams) && is_string($smtpParams['password'])) {
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
    public function applySmtpHandler(InboundEmailEntity $emailAccount, array &$params): void
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
                "InboundEmail: Could not create Smtp Handler for account {$emailAccount->id}. Error: " .
                    $e->getMessage()
            );

            return;
        }

        if (method_exists($handler, 'applyParams')) {
            $handler->applyParams($emailAccount->getId(), $params);
        }
    }
}
