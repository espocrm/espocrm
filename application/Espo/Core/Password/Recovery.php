<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
use Espo\Entities\User;
use Espo\Entities\PasswordChangeRequest;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Error;

use Espo\Core\{
    ORM\EntityManager,
    Utils\Config,
    Mail\EmailSender,
    Htmlizer\HtmlizerFactory as HtmlizerFactory,
    Utils\TemplateFileManager,
    Utils\Log,
    Job\QueueName,
};

use DateTime;

class Recovery
{
    /**
     * Milliseconds.
     */
    const REQUEST_DELAY = 3000;

    const REQUEST_LIFETIME = '3 hours';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var EmailSender
     */
    protected $emailSender;

    /**
     * @var HtmlizerFactory
     */
    protected $htmlizerFactory;

    /**
     * @var TemplateFileManager
     */
    protected $templateFileManager;

    /**
     * @var Log
     */
    private $log;

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
        $em = $this->entityManager;

        if ($config->get('passwordRecoveryDisabled')) {
            throw new Forbidden("Password recovery: Disabled.");
        }

        $request = $em
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

    public function removeRequest(string $id)
    {
        $em = $this->entityManager;

        $request = $em
            ->getRDBRepository('PasswordChangeRequest')
            ->where([
                'requestId' => $id,
            ])
            ->findOne();

        if ($request) {
            $em->removeEntity($request);
        }
    }

    public function request(string $emailAddress, string $userName, ?string $url): bool
    {
        $config = $this->config;
        $em = $this->entityManager;

        $noExposure = $config->get('passwordRecoveryNoExposure') ?? false;

        if ($config->get('passwordRecoveryDisabled')) {
            throw new Forbidden("Password recovery: Disabled.");
        }

        $user = $em
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

        if (!$user->isAdmin() && $config->get('authenticationMethod', 'Espo') !== 'Espo') {
            $this->fail(
                "Password recovery: User {$user->id} is not allowed, authentication method is not 'Espo'."
            );

            return false;
        }

        $passwordChangeRequest = $em
            ->getRDBRepository('PasswordChangeRequest')
            ->where([
                'userId' => $user->getId(),
            ])
            ->findOne();

        if ($passwordChangeRequest) {
            if (!$noExposure) {
                throw new Forbidden(json_encode(['reason' => 'Already-Sent']));
            }

            $this->fail("Password recovery: Denied for {$user->id}, already sent.");

            return false;
        }

        $requestId = Util::generateCryptId();

        $passwordChangeRequest = $em->getEntity('PasswordChangeRequest');

        $passwordChangeRequest->set([
            'userId' => $user->id,
            'requestId' => $requestId,
            'url' => $url,
        ]);

        $microtime = microtime(true);

        $this->send($requestId, $emailAddress, $user);

        $em->saveEntity($passwordChangeRequest);

        if (!$passwordChangeRequest->id) {
            throw new Error();
        }

        $lifetime = $config->get('passwordRecoveryRequestLifetime') ?? self::REQUEST_LIFETIME;

        $dt = new DateTime();

        $dt->modify('+' . $lifetime);

        $em->createEntity('Job', [
            'serviceName' => 'User',
            'methodName' => 'removeChangePasswordRequestJob',
            'data' => ['id' => $passwordChangeRequest->id],
            'executeTime' => $dt->format('Y-m-d H:i:s'),
            'queue' => QueueName::Q1,
        ]);

        $timeDiff = $this->getDelay() - floor((microtime(true) - $microtime) / 1000);

        if ($noExposure && $timeDiff > 0) {
            $this->delay((int) $timeDiff);
        }

        return true;
    }

    private function getDelay()
    {
        return $this->config->get('passwordRecoveryRequestDelay') ?? self::REQUEST_DELAY;
    }

    protected function delay(?int $delay = null)
    {
        $delay = $delay ?? $this->getDelay();

        usleep($delay * 1000);
    }

    protected function send(string $requestId, string $emailAddress, User $user)
    {
        $config = $this->config;
        $em = $this->entityManager;
        $htmlizerFactory = $this->htmlizerFactory;

        $templateFileManager = $this->templateFileManager;

        if (!$emailAddress) {
            return;
        }

        $email = $em->getEntity('Email');

        if (!$this->emailSender->hasSystemSmtp() && !$config->get('internalSmtpServer')) {
            throw new Error("Password recovery: SMTP credentials are not defined.");
        }

        $sender = $this->emailSender->create();

        $subjectTpl = $templateFileManager->getTemplate('passwordChangeLink', 'subject', 'User');
        $bodyTpl = $templateFileManager->getTemplate('passwordChangeLink', 'body', 'User');

        $siteUrl = $config->getSiteUrl();

        if ($user->isPortal()) {
            $portal = $em->getRDBRepository('Portal')
                ->distinct()
                ->join('users')
                ->where([
                    'isActive' => true,
                    'users.id' => $user->getId(),
                ])
                ->findOne();

            if ($portal) {
                if ($portal->get('customUrl')) {
                    $siteUrl = $portal->get('customUrl');
                }
            }
        }

        $data = [];

        $link = $siteUrl . '?entryPoint=changePassword&id=' . $requestId;

        $data['link'] = $link;

        $htmlizer = $htmlizerFactory->create(true);

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
                'server' => $config->get('internalSmtpServer'),
                'port' => $config->get('internalSmtpPort'),
                'auth' => $config->get('internalSmtpAuth'),
                'username' => $config->get('internalSmtpUsername'),
                'password' => $config->get('internalSmtpPassword'),
                'security' => $config->get('internalSmtpSecurity'),
                'fromAddress' => $config->get(
                    'internalOutboundEmailFromAddress',
                    $config->get('outboundEmailFromAddress')
                ),
            ]);
        }

        $sender->send($email);
    }

    private function fail(?string $msg = null, int $errorCode = 403)
    {
        $config = $this->config;

        $noExposure = $config->get('passwordRecoveryNoExposure') ?? false;

        if ($msg) {
            $this->log->warning($msg);
        }

        if (!$noExposure) {
            if ($errorCode === 403) {
                throw new Forbidden();
            }
            else if ($errorCode === 404) {
                throw new NotFound();
            }
            else {
                throw new Error();
            }
        }

        $this->delay();
    }
}
