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

namespace Espo\Core\Password;

use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;

use Espo\Entities\User;
use Espo\Entities\PasswordChangeRequest;
use Espo\Entities\Portal;

use Espo\Repositories\Portal as PortalRepository;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Error;

use Espo\Core\Field\DateTime;

use Espo\Core\{
    Authentication\Logins\Espo as EspoLogin,
    ORM\EntityManager,
    Utils\Config,
    Mail\EmailSender,
    Htmlizer\HtmlizerFactory as HtmlizerFactory,
    Utils\TemplateFileManager,
    Utils\Log,
    Job\QueueName,
};

class Recovery
{
    /**
     * Milliseconds.
     */
    private const REQUEST_DELAY = 3000;

    private const REQUEST_LIFETIME = '3 hours';

    private const NEW_USER_REQUEST_LIFETIME = '2 days';

    private const EXISTING_USER_REQUEST_LIFETIME = '2 days';

    protected EntityManager $entityManager;

    protected Config $config;

    protected EmailSender $emailSender;

    protected HtmlizerFactory $htmlizerFactory;

    protected TemplateFileManager $templateFileManager;

    private Log $log;

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        EmailSender $emailSender,
        HtmlizerFactory $htmlizerFactory,
        TemplateFileManager $templateFileManager,
        Log $log
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->emailSender = $emailSender;
        $this->htmlizerFactory = $htmlizerFactory;
        $this->templateFileManager = $templateFileManager;
        $this->log = $log;
    }

    public function getRequest(string $id): PasswordChangeRequest
    {
        $config = $this->config;

        if ($config->get('passwordRecoveryDisabled')) {
            throw new Forbidden("Password recovery: Disabled.");
        }

        $request = $this->entityManager
            ->getRDBRepository('PasswordChangeRequest')
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
            ->getRDBRepository('PasswordChangeRequest')
            ->where([
                'requestId' => $id,
            ])
            ->findOne();

        if ($request) {
            $this->entityManager->removeEntity($request);
        }
    }

    public function request(string $emailAddress, string $userName, ?string $url): bool
    {
        $config = $this->config;

        $noExposure = $config->get('passwordRecoveryNoExposure') ?? false;

        if ($config->get('passwordRecoveryDisabled')) {
            throw new Forbidden("Password recovery: Disabled.");
        }

        $user = $this->entityManager
            ->getRDBRepository('User')
            ->where([
                'userName' => $userName,
                'emailAddress' => $emailAddress,
            ])
            ->findOne();

        if (!$user) {
            $this->fail("Password recovery: User {$emailAddress} not found.", 404);

            return false;
        }

        if (!$user->isActive()) {
            $this->fail("Password recovery: User {$user->id} is not active.");

            return false;
        }

        if ($user->isApi() || $user->isSystem() || $user->isSuperAdmin()) {
            $this->fail("Password recovery: User {$user->id} is not allowed.");

            return false;
        }

        if ($config->get('passwordRecoveryForInternalUsersDisabled')) {
            if ($user->isRegular() || $user->isAdmin()) {
                $this->fail(
                    "Password recovery: User {$user->id} is not allowed, disabled for internal users."
                );

                return false;
            }
        }

        if ($config->get('passwordRecoveryForAdminDisabled')) {
            if ($user->isAdmin()) {
                $this->fail(
                    "Password recovery: User {$user->id} is not allowed, disabled for admin users."
                );

                return false;
            }
        }

        if (!$user->isAdmin() && $config->get('authenticationMethod', EspoLogin::NAME) !== EspoLogin::NAME) {
            $this->fail(
                "Password recovery: User {$user->id} is not allowed, authentication method is not 'Espo'."
            );

            return false;
        }

        $existingRequest = $this->entityManager
            ->getRDBRepository('PasswordChangeRequest')
            ->where([
                'userId' => $user->getId(),
            ])
            ->findOne();

        if ($existingRequest) {
            if (!$noExposure) {
                throw new Forbidden(Json::encode(['reason' => 'Already-Sent']));
            }

            $this->fail("Password recovery: Denied for {$user->id}, already sent.");

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

    private function createRequestNoSave(User $user, ?string $url = null): PasswordChangeRequest
    {
        $this->checkUser($user);

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

    public function createAndSendRequestForExistingUser(User $user, ?string $url = null): PasswordChangeRequest
    {
        $this->checkUser($user);

        if (!$user->getEmailAddressGroup()->getPrimary()) {
            throw new Error("No email address.");
        }

        $emailAddress = $user->getEmailAddressGroup()->getPrimary()->getAddress();

        $entity = $this->createRequestNoSave($user, $url);

        $this->entityManager->saveEntity($entity);

        $lifetime = $this->config->get('passwordChangeRequestExistingUserLifetime') ??
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
        $this->entityManager->createEntity('Job', [
            'serviceName' => 'User',
            'methodName' => 'removeChangePasswordRequestJob',
            'data' => ['id' => $id],
            'executeTime' => DateTime::createNow()
                ->modify('+' . $lifetime)
                ->getString(),
            'queue' => QueueName::Q1,
        ]);
    }

    private function getDelay(): int
    {
        return $this->config->get('passwordRecoveryRequestDelay') ?? self::REQUEST_DELAY;
    }

    protected function delay(?int $delay = null): void
    {
        if ($delay === null) {
            $delay = $this->getDelay();
        }

        usleep($delay * 1000);
    }

    protected function send(string $requestId, string $emailAddress, User $user): void
    {
        if (!$emailAddress) {
            return;
        }

        $email = $this->entityManager->getNewEntity('Email');

        if (!$this->emailSender->hasSystemSmtp() && !$this->config->get('internalSmtpServer')) {
            throw new Error("Password recovery: SMTP credentials are not defined.");
        }

        $sender = $this->emailSender->create();

        $subjectTpl = $this->templateFileManager->getTemplate('passwordChangeLink', 'subject', 'User');
        $bodyTpl = $this->templateFileManager->getTemplate('passwordChangeLink', 'body', 'User');

        $siteUrl = $this->config->getSiteUrl();

        if ($user->isPortal()) {
            /** @var Portal|null $portal */
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
                throw new Error("Portal user does not belong to any potral.");
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

        $email->set([
            'subject' => $subject,
            'body' => $body,
            'to' => $emailAddress,
            'isSystem' => true,
        ]);

        if (!$this->emailSender->hasSystemSmtp()) {
            $sender->withSmtpParams([
                'server' => $this->config->get('internalSmtpServer'),
                'port' => $this->config->get('internalSmtpPort'),
                'auth' => $this->config->get('internalSmtpAuth'),
                'username' => $this->config->get('internalSmtpUsername'),
                'password' => $this->config->get('internalSmtpPassword'),
                'security' => $this->config->get('internalSmtpSecurity'),
                'fromAddress' => $this->config->get(
                    'internalOutboundEmailFromAddress',
                    $this->config->get('outboundEmailFromAddress')
                ),
            ]);
        }

        $sender->send($email);
    }

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
