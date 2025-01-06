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

namespace Espo\Tools\UserSecurity\Password;

use Espo\Core\Authentication\Ldap\LdapLogin;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Error;
use Espo\Core\ApplicationState;
use Espo\Core\Authentication\Util\MethodProvider as AuthenticationMethodProvider;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Job\JobSchedulerFactory;
use Espo\Core\Mail\Exceptions\NoSmtp;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Core\Utils\Util;
use Espo\Entities\Email;
use Espo\Entities\SystemData;
use Espo\Entities\User;
use Espo\Entities\PasswordChangeRequest;
use Espo\Entities\Portal;
use Espo\Repositories\Portal as PortalRepository;
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
use Espo\Tools\UserSecurity\Password\Recovery\UrlValidator;

class RecoveryService
{
    /** Milliseconds. */
    private const REQUEST_DELAY = 3000;
    private const REQUEST_LIFETIME = '3 hours';
    private const NEW_USER_REQUEST_LIFETIME = '2 days';
    private const EXISTING_USER_REQUEST_LIFETIME = '2 days';
    private const INTERNAL_SMTP_INTERVAL_PERIOD = '1 hour';

    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private EmailSender $emailSender,
        private HtmlizerFactory $htmlizerFactory,
        private TemplateFileManager $templateFileManager,
        private Log $log,
        private JobSchedulerFactory $jobSchedulerFactory,
        private ApplicationState $applicationState,
        private AuthenticationMethodProvider $authenticationMethodProvider,
        private UrlValidator $urlValidator,
        private ApplicationConfig $applicationConfig,
    ) {}

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
            ->getRDBRepositoryByClass(PasswordChangeRequest::class)
            ->where(['requestId' => $id])
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
            ->getRDBRepositoryByClass(PasswordChangeRequest::class)
            ->where(['requestId' => $id])
            ->findOne();

        if ($request) {
            $this->entityManager->removeEntity($request);
        }
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     */
    public function request(string $emailAddress, string $userName, ?string $url): bool
    {
        $config = $this->config;

        $noExposure = $config->get('passwordRecoveryNoExposure') ?? false;

        if ($config->get('passwordRecoveryDisabled')) {
            throw new Forbidden("Password recovery: Disabled.");
        }

        if ($url) {
            $this->urlValidator->validate($url);
        }

        $user = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where([
                'userName' => $userName,
                'emailAddress' => $emailAddress,
            ])
            ->findOne();

        if (!$user) {
            $this->fail("User $emailAddress not found.", 404);

            return false;
        }

        $userId = $user->getId();

        if (!$user->isActive()) {
            $this->fail("User $userId is not active.");

            return false;
        }

        if (
            !$user->isAdmin() &&
            $this->authenticationMethodProvider->get() !== EspoLogin::NAME &&
            !$this->isPortalLdapDisabled()
        ) {
            $this->fail("User $userId is not allowed, authentication method is not 'Espo'.");

            return false;
        }

        if ($user->isApi() || $user->isSystem() || $user->isSuperAdmin()) {
            $this->fail("User $userId is not allowed.");

            return false;
        }

        if ($config->get('passwordRecoveryForInternalUsersDisabled')) {
            if ($user->isRegular() || $user->isAdmin()) {
                $this->fail("User $userId is not allowed, disabled for internal users.");

                return false;
            }
        }

        if ($config->get('passwordRecoveryForAdminDisabled')) {
            if ($user->isAdmin()) {
                $this->fail("User $userId is not allowed, disabled for admin users.");

                return false;
            }
        }

        if ($this->applicationState->isPortal()) {
            if (!$user->isPortal()) {
                $this->fail("User $userId is not allowed, as it's not portal user.");

                return false;
            }

            $portalId = $this->applicationState->getPortalId();

            if (!$user->getPortals()->hasId($portalId)) {
                $this->fail("User $userId is from another portal.");

                return false;
            }
        }

        $existingRequest = $this->entityManager
            ->getRDBRepositoryByClass(PasswordChangeRequest::class)
            ->where(['userId' => $user->getId()])
            ->findOne();

        if ($existingRequest) {
            if (!$noExposure) {
                throw new ForbiddenSilent('Already-Sent');
            }

            $this->fail("Denied for $userId, already sent.");

            return false;
        }

        $request = $this->createRequestNoSave($user, $url);

        $microtime = microtime(true);

        try {
            $this->send($request->getRequestId(), $emailAddress, $user);
        } catch (SendingError $e) {
            $message = "Email sending error. " . $e->getMessage();

            $this->log->error($message);

            throw new Error("Email sending error.");
        }

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

        $entity = $this->entityManager->getRDBRepositoryByClass(PasswordChangeRequest::class)->getNew();

        $entity->set([
            'userId' => $user->getId(),
            'requestId' => Util::generateCryptId(),
            'url' => $url,
        ]);

        return $entity;
    }

    /**
     * @throws Error
     */
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
     * @throws Forbidden
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

        $lifetime = $this->config->get('passwordChangeRequestExistingUserLifetime') ??
            self::EXISTING_USER_REQUEST_LIFETIME;

        $this->createCleanupRequestJob($entity->getId(), $lifetime);

        try {
            $this->send($entity->getRequestId(), $emailAddress, $user);
        } catch (SendingError $e) {
            $this->log->error("Email sending error. " . $e->getMessage());

            throw new Error("Email sending error.");
        }

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
                    ->toDateTime()
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
     * @throws Forbidden
     */
    private function send(string $requestId, string $emailAddress, User $user): void
    {
        if (!$emailAddress) {
            return;
        }

        $email = $this->entityManager->getRDBRepositoryByClass(Email::class)->getNew();

        if (!$this->emailSender->hasSystemSmtp() && !$this->config->get('internalSmtpServer')) {
            throw new Error("Password recovery: SMTP credentials are not defined.");
        }

        if (!$this->emailSender->hasSystemSmtp()) {
            $this->checkIntervalForInternalSmtp();
        }

        $sender = $this->emailSender->create();

        $subjectTpl = $this->templateFileManager->getTemplate('passwordChangeLink', 'subject', 'User');
        $bodyTpl = $this->templateFileManager->getTemplate('passwordChangeLink', 'body', 'User');

        $siteUrl = $this->applicationConfig->getSiteUrl();

        if ($user->isPortal()) {
            $portal = $this->entityManager
                ->getRDBRepositoryByClass(Portal::class)
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
                throw new NoSmtp("No internal SMTP");
            }

            $smtpParams = SmtpParams::create($server, $port)
                ->withAuth($this->config->get('internalSmtpAuth'))
                ->withUsername($this->config->get('internalSmtpUsername'))
                ->withPassword($this->config->get('internalSmtpPassword'))
                ->withSecurity($this->config->get('internalSmtpSecurity'))
                ->withFromName(
                    $this->config->get('outboundEmailFromName')
                );

            $sender->withSmtpParams($smtpParams);
        }

        $sender->send($email);

        $this->lastPasswordRecoveryDate();
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
            $msg = 'Password recovery: ' . $msg;

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

    /**
     * @throws Forbidden
     */
    private function checkIntervalForInternalSmtp(): void
    {
        /** @var string $period */
        $period = $this->config->get('passwordRecoveryInternalIntervalPeriod') ??
            self::INTERNAL_SMTP_INTERVAL_PERIOD;

        $data = $this->entityManager->getEntityById(SystemData::ENTITY_TYPE, SystemData::ONLY_ID);

        if (!$data) {
            return;
        }

        /** @var ?string $lastPasswordRecoveryDate */
        $lastPasswordRecoveryDate = $data->get('lastPasswordRecoveryDate');

        if (!$lastPasswordRecoveryDate) {
            return;
        }

        $notPassed = DateTime::fromString($lastPasswordRecoveryDate)
            ->modify('+' . $period)
            ->isGreaterThan(DateTime::createNow());

        if (!$notPassed) {
            return;
        }

        throw Forbidden::createWithBody(
            'Internal password recovery attempt interval failure.',
            Error\Body::create()
                ->withMessageTranslation('attemptIntervalFailure')
                ->encode()
        );
    }

    private function lastPasswordRecoveryDate(): void
    {
        $data = $this->entityManager->getEntityById(SystemData::ENTITY_TYPE, SystemData::ONLY_ID);

        if (!$data) {
            return;
        }

        $data->set('lastPasswordRecoveryDate', DateTime::createNow()->toString());

        $this->entityManager->saveEntity($data);
    }

    private function isPortalLdapDisabled(): bool
    {
        return $this->applicationState->isPortal() &&
            $this->authenticationMethodProvider->get() === LdapLogin::NAME &&
            !$this->config->get('ldapPortalUserLdapAuth');
    }
}
