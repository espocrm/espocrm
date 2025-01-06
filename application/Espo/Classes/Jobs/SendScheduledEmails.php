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

namespace Espo\Classes\Jobs;

use Espo\Core\Acl\Table;
use Espo\Core\AclManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Field\DateTime;
use Espo\Core\Job\JobDataLess;
use Espo\Core\Mail\Exceptions\NoSmtp;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\Entities\Email;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Tools\Email\SendService;
use Exception;
use RuntimeException;

/**
 * @noinspection PhpUnused
 */
class SendScheduledEmails implements JobDataLess
{
    private const BATCH_COUNT = 10;

    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private Log $log,
        private SendService $sendService,
        private Language $language,
        private AclManager $aclManager,
        private Config\ApplicationConfig $applicationConfig,
    ) {}

    public function run(): void
    {
        $emails = $this->entityManager
            ->getRDBRepositoryByClass(Email::class)
            ->where([
                'status' => Email::STATUS_DRAFT,
                'sendAt!=' => null,
                'sendAt<' => DateTime::createNow()->toString(),
            ])
            ->order('sendAt')
            ->order(Field::CREATED_AT)
            ->limit(0, $this->getPortion())
            ->sth()
            ->find();

        foreach ($emails as $email) {
            try {
                $this->processEmail($email);
            } catch (SendingError|Exception $e) {
                $this->log->error("Scheduled email send, {$email->getId()}." . $e->getMessage(), ['exception' => $e]);

                $this->processFail($email);
            }
        }
    }

    private function getPortion(): int
    {
        return $this->config->get('emailScheduledBatchCount') ?? self::BATCH_COUNT;
    }


    /**
     * @throws BadRequest
     * @throws Error
     * @throws NoSmtp
     * @throws SendingError
     */
    private function processEmail(Email $email): void
    {
        $user = $this->getUser($email);

        $this->sendService->send($email, $user);

        $this->entityManager->saveEntity($email);
    }

    private function processFail(Email $email): void
    {
        $email->setSendAt(null);
        $this->entityManager->saveEntity($email);

        if (!$email->getCreatedBy()) {
            return;
        }

        $message = $this->language->translateLabel('couldNotSentScheduledEmail', 'messages', 'Email');
        $message = str_replace('{link}', $this->getEmailLink($email), $message);

        $notification = $this->entityManager->getRDBRepositoryByClass(Notification::class)->getNew();

        $notification
            ->setType(Notification::TYPE_MESSAGE)
            ->setUserId($email->getCreatedBy()->getId())
            ->setMessage($message);

        $this->entityManager->saveEntity($notification);
    }

    private function getEmailLink(Email $email): string
    {
        return rtrim($this->applicationConfig->getSiteUrl()) . '#Email/view/' . $email->getId();
    }

    private function getUser(Email $email): User
    {
        if (!$email->getCreatedBy()) {
            throw new RuntimeException("No createdBy in email.");
        }

        $userId = $email->getCreatedBy()->getId();

        $user = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->getById($userId);

        if (!$user) {
            throw new RuntimeException("User $userId not found.");
        }

        if (!$this->aclManager->checkScope($user, Email::ENTITY_TYPE, Table::ACTION_CREATE)) {
            throw new RuntimeException("User $userId don't have access to create emails.");
        }

        return $user;
    }
}
