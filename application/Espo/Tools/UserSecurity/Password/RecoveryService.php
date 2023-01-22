<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\UserSecurity\Password;

use Espo\Core\Job\JobSchedulerFactory;
use Espo\Core\Mail\Exceptions\NoSmtp;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;

use Espo\Entities\Email;
use Espo\Entities\User;
use Espo\Entities\PasswordChangeRequest;
use Espo\Entities\Portal;

use Espo\Repositories\Portal as PortalRepository;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Error;

use Espo\Core\Field\DateTime;

use Espo\Core\Authentication\Logins\Espo as EspoLogin;
use Espo\Core\Htmlizer\HtmlizerFactory as HtmlizerFactory;
use Espo\Core\Job\QueueName;
use Espo\Core\Mail\EmailSender;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\TemplateFileManager;
use Espo\Tools\UserSecurity\Password\Jobs\RemoveRecoveryRequest;

class RecoveryService
{
    /** Milliseconds. */
    private const REQUEST_DELAY = 3000;
    private const REQUEST_LIFETIME = '3 hours';
    private const NEW_USER_REQUEST_LIFETIME = '2 days';
    private const EXISTING_USER_REQUEST_LIFETIME = '2 days';

    private EntityManager $entityManager;
    private Config $config;
    private EmailSender $emailSender;
    private HtmlizerFactory $htmlizerFactory;
    private TemplateFileManager $templateFileManager;
    private Log $log;
    private JobSchedulerFactory $jobSchedulerFactory;

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        EmailSender $emailSender,
        HtmlizerFactory $htmlizerFactory,
        TemplateFileManager $templateFileManager,
        Log $log,
        JobSchedulerFactory $jobSchedulerFactory
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->emailSender = $emailSender;
        $this->htmlizerFactory = $htmlizerFactory;
        $this->templateFileManager = $templateFileManager;
        $this->log = $log;
        $this->jobSchedulerFactory = $jobSchedulerFactory;
    }

    /**
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function getRequest(string $id): PasswordChangeRequest
    {
        $config = $this->config;

        if ($config->get('passwordRecoveryDisabled')) {
            throw new Forbidden("Password recovery: Disabled.");
        }

        $request = $this->entityManager
            ->getRDBRepository(PasswordChangeRequest::ENTITY_TYPE)
            ->where([
                'requestId' => $id,
            ])
            ->findOne();

        if (!$request) {
            throw new NotFound("Password recovery: Request not found by id.");
        }

        $userId = $request->get('userId');

        if (!$userId) {
            throw new Error();
        }

        return $request;
    }

    public function removeRequest(string $id): void
    {
        $request = $this->entityManager
            ->getRDBRepository(PasswordChangeRequest::ENTITY_TYPE)
            ->where([
                'requestId' => $id,
            ])
            ->findOne();

        if ($request) {
            $this->entityManager->removeEntity($request);
        }
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     * @throws SendingError
     */
    public function request(string $emailAddress, string $userName, ?string $url): bool
    {
        $config = $this->config;

        $noExposure = $config->get('passwordRecoveryNoExposure') ?? false;

        if ($config->get('passwordRecoveryDisabled')) {
            throw new Forbidden("Password recovery: Disabled.");
        }

        /** @var ?User $user */
        $user = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->where([
                'userName' => $userName,
                'emailAddress' => $emailAddress,
            ])
            ->findOne();

        if (!$user) {
            $this->fail("Password recovery: User {$emailAddress} not found.", 404);

            return false;
        }

        $userId = $user->getId();

        if (!$user->isActive()) {
            $this->fail("Password recovery: User {$userId} is not active.");

            return false;
        }

        if ($user->isApi() || $user->isSystem() || $user->isSuperAdmin()) {
            $this->fail("Password recovery: User {$userId} is not allowed.");

            return false;
        }

        if ($config->get('passwordRecoveryForInternalUsersDisabled')) {
            if ($user->isRegular() || $user->isAdmin()) {
                $this->fail("Password recovery: User {$userId} is not allowed, disabled for internal users.");

                return false;
            }
        }

        if ($config->get('passwordRecoveryForAdminDisabled')) {
            if ($user->isAdmin()) {
                $this->fail("Password recovery: User {$userId} is not allowed, disabled for admin users.");

                return false;
            }
        }

        if (
            !$user->isAdmin() &&
            $config->get('authenticationMethod', EspoLogin::NAME) !== EspoLogin::NAME
        ) {
            $this->fail("Password recovery: User {$userId} is not allowed, authentication method is not 'Espo'.");

            return false;
        }

        $existingRequest = $this->entityManager
            ->getRDBRepository(PasswordChangeRequest::ENTITY_TYPE)
            ->where([
                'userId' => $user->getId(),
            ])
            ->findOne();

        if ($existingRequest) {
            if (!$noExposure) {
                throw new Forbidden(Json::encode(['reason' => 'Already-Sent']));
            }

            $this->fail("Password recovery: Denied for {$userId}, already sent.");

            return false;
        }

        $request = $this->createRequestNoSave($user, $url);

        $microtime = microtime(true);

        $this->send($request->getRequestId(), $emailAddress, $user);

        $this->entityManager->saveEntity($request);

        $lifetime = $config->get('passwordRecoveryRequestLifetime') ?? self::REQUEST_LIFETIME;

        $this->createCleanupRequestJob($request->getId(), $lifetime);

        $timeDiff = $this->getDelay() - floor((microtime(true) - $microtime) / 1000);

        if ($noExposure && $timeDiff > 0) {
            $this->delay((int) $timeDiff);
        }

        return true;
    }

    /**
     * @throws Error
     */
    private function createRequestNoSave(User $user, ?string $url = null): PasswordChangeRequest
    {
        $this->checkUser($user);

        /** @var PasswordChangeRequest $entity */
        $entity = $this->entityManager->getNewEntity(PasswordChangeRequest::ENTITY_TYPE);

        $entity->set([
            'userId' => $user->getId(),
            'requestId' => Util::generateCryptId(),
            'url' => $url,
        ]);

        return $entity;
    }

    public function createRequestForNewUser(User $user, ?string $url = null): PasswordChangeRequest
    {
        $this->checkUser($user);

        $entity = $this->createRequestNoSave($user, $url);

        $this->entityManager->saveEntity($entity);

        $lifetime = $this->config->get('passwordChangeRequestNewUserLifetime') ?? self::NEW_USER_REQUEST_LIFETIME;

        $this->createCleanupRequestJob($entity->getId(), $lifetime);

        return $entity;
    }

    /**
     * @throws Error
     */
    public function createAndSendRequestForExistingUser(User $user, ?string $url = null): PasswordChangeRequest
    {
        $this->checkUser($user);

        if (!$user->getEmailAddressGroup()->getPrimary()) {
            throw new Error("No email address.");
        }

        $emailAddress = $user->getEmailAddressGroup()->getPrimary()->getAddress();

        $entity = $this->createRequestNoSave($user, $url);

        $this->entityManager->saveEntity($entity);

        $lifetime =
            $this->config->get('passwordChangeRequestExistingUserLifetime') ??
            self::EXISTING_USER_REQUEST_LIFETIME;

        $this->createCleanupRequestJob($entity->getId(), $lifetime);

        $this->send($entity->getRequestId(), $emailAddress, $user);

        return $entity;
    }

    /**
     * @throws Error
     */
    private function checkUser(User $user): void
    {
        if (
            !$user->isActive() ||
            !(
                $user->isAdmin() ||
                $user->isRegular() ||
                $user->isPortal()
            )
        ) {
            throw new Error("User is not allowed for password change request.");
        }
    }

    private function createCleanupRequestJob(string $id, string $lifetime): void
    {
        $this->jobSchedulerFactory
            ->create()
            ->setClassName(RemoveRecoveryRequest::class)
            ->setData(['id' => $id])
            ->setTime(
                DateTime::createNow()
                    ->modify('+' . $lifetime)
                    ->getDateTime()
            )
            ->setQueue(QueueName::Q1)
            ->schedule();
    }

    private function getDelay(): int
    {
        return $this->config->get('passwordRecoveryRequestDelay') ?? self::REQUEST_DELAY;
    }

    private function delay(?int $delay = null): void
    {
        if ($delay === null) {
            $delay = $this->getDelay();
        }

        usleep($delay * 1000);
    }

    /**
     * @throws Error
     * @throws SendingError
     */
    private function send(string $requestId, string $emailAddress, User $user): void
    {
        if (!$emailAddress) {
            return;
        }

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        if (!$this->emailSender->hasSystemSmtp() && !$this->config->get('internalSmtpServer')) {
            throw new Error("Password recovery: SMTP credentials are not defined.");
        }

        $sender = $this->emailSender->create();

        $subjectTpl = $this->templateFileManager->getTemplate('passwordChangeLink', 'subject', 'User');
        $bodyTpl = $this->templateFileManager->getTemplate('passwordChangeLink', 'body', 'User');

        $siteUrl = $this->config->getSiteUrl();

        if ($user->isPortal()) {
            /** @var ?Portal $portal */
            $portal = $this->entityManager
                ->getRDBRepository(Portal::ENTITY_TYPE)
                ->distinct()
                ->join('users')
                ->where([
                    'isActive' => true,
                    'users.id' => $user->getId(),
                ])
                ->findOne();

            if (!$portal) {
                throw new Error("Portal user does not belong to any portal.");
            }

            $this->getPortalRepository()->loadUrlField($portal);

            $siteUrl = $portal->getUrl();

            if (!$siteUrl) {
                throw new Error("Portal does not have URL.");
            }
        }

        $data = [];

        $link = $siteUrl . '?entryPoint=changePassword&id=' . $requestId;

        $data['link'] = $link;

        $htmlizer = $this->htmlizerFactory->create(true);

        $subject = $htmlizer->render($user, $subjectTpl, null, $data, true);
        $body = $htmlizer->render($user, $bodyTpl, null, $data, true);

        $email
            ->setSubject($subject)
            ->setBody($body)
            ->addToAddress($emailAddress);

        $email->set([
            'isSystem' => true,
        ]);

        if (!$this->emailSender->hasSystemSmtp()) {
            $server = $this->config->get('internalSmtpServer');
            $port = $this->config->get('internalSmtpPort');

            if (!$server || $port === null) {
                throw new NoSmtp();
            }

            $smtpParams = SmtpParams
                ::create($server, $port)
                ->withAuth($this->config->get('internalSmtpAuth'))
                ->withUsername($this->config->get('internalSmtpUsername'))
                ->withPassword($this->config->get('internalSmtpPassword'))
                ->withSecurity($this->config->get('internalSmtpSecurity'))
                ->withFromName(
                    $this->config->get('internalOutboundEmailFromAddress') ??
                    $this->config->get('outboundEmailFromAddress')
                );

            $sender->withSmtpParams($smtpParams);
        }

        $sender->send($email);
    }

    /**
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    private function fail(?string $msg = null, int $errorCode = 403): void
    {
        $noExposure = $this->config->get('passwordRecoveryNoExposure') ?? false;

        if ($msg) {
            $this->log->warning($msg);
        }

        if (!$noExposure) {
            if ($errorCode === 403) {
                throw new Forbidden();
            }

            if ($errorCode === 404) {
                throw new NotFound();
            }

            throw new Error();
        }

        $this->delay();
    }

    private function getPortalRepository(): PortalRepository
    {
        /** @var PortalRepository */
        return $this->entityManager->getRDBRepository(Portal::ENTITY_TYPE);
    }
}
