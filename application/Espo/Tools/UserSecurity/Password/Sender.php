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

use Espo\Core\Exceptions\Error;
use Espo\Core\Htmlizer\HtmlizerFactory;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Mail\Exceptions\NoSmtp;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Mail\Sender as EmailSenderSender;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\TemplateFileManager;
use Espo\Entities\Email;
use Espo\Entities\PasswordChangeRequest;
use Espo\Entities\Portal;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Repositories\Portal as PortalRepository;

class Sender
{
    private Config $config;
    private EmailSender $emailSender;
    private EntityManager $entityManager;
    private HtmlizerFactory $htmlizerFactory;
    private TemplateFileManager $templateFileManager;

    public function __construct(
        Config $config,
        EmailSender $emailSender,
        EntityManager $entityManager,
        HtmlizerFactory $htmlizerFactory,
        TemplateFileManager $templateFileManager
    ) {
        $this->config = $config;
        $this->emailSender = $emailSender;
        $this->entityManager = $entityManager;
        $this->htmlizerFactory = $htmlizerFactory;
        $this->templateFileManager = $templateFileManager;
    }

    /**
     * Send access info for a new user.
     *
     * @throws Error
     * @throws NoSmtp
     * @throws SendingError
     */
    public function sendAccessInfo(User $user, PasswordChangeRequest $request): void
    {
        $emailAddress = $user->getEmailAddress();

        if (!$emailAddress) {
            throw new Error("No email address.");
        }

        [$subjectTpl, $bodyTpl, $data] = $this->getAccessInfoTemplateData($user, null, $request);

        if ($data === null) {
            throw new Error("Could not send access info.");
        }

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $htmlizer = $this->htmlizerFactory->createNoAcl();

        $subject = $htmlizer->render($user, $subjectTpl ?? '', null, $data, true);
        $body = $htmlizer->render($user, $bodyTpl ?? '', null, $data, true);

        $email
            ->addToAddress($emailAddress)
            ->setSubject($subject)
            ->setBody($body);

        $this->createSender()->send($email);
    }

    /**
     * Send a plain password in email.
     *
     * @throws SendingError
     * @throws Error
     */
    public function sendPassword(User $user, string $password): void
    {
        $emailAddress = $user->getEmailAddress();

        if (empty($emailAddress)) {
            return;
        }

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        if (!$this->isSmtpConfigured()) {
            return;
        }

        [$subjectTpl, $bodyTpl, $data] = $this->getAccessInfoTemplateData($user, $password);

        if ($data === null) {
            return;
        }

        $htmlizer = $this->htmlizerFactory->createNoAcl();

        $subject = $htmlizer->render($user, $subjectTpl ?? '', null, $data, true);
        $body = $htmlizer->render($user, $bodyTpl ?? '', null, $data, true);

        $email
            ->setSubject($subject)
            ->setBody($body)
            ->addToAddress($emailAddress);

        $this->createSender()->send($email);
    }

    /**
     * @throws NoSmtp
     */
    private function createSender(): EmailSenderSender
    {
        $sender = $this->emailSender->create();

        $smtpParams = $this->getSmtpParams();

        if ($smtpParams) {
            $sender = $sender->withSmtpParams($smtpParams);
        }

        return $sender;
    }

    /**
     * @return array{?string, ?string, ?array<string, mixed>}
     */
    private function getAccessInfoTemplateData(
        User $user,
        ?string $password = null,
        ?PasswordChangeRequest $passwordChangeRequest = null
    ): array {

        $data = [];

        if ($password !== null) {
            $data['password'] = $password;
        }

        $urlSuffix = '';

        if ($passwordChangeRequest !== null) {
            $urlSuffix = '?entryPoint=changePassword&id=' . $passwordChangeRequest->getRequestId();
        }

        $siteUrl = $this->config->getSiteUrl() . '/' . $urlSuffix;

        if ($user->isPortal()) {
            $subjectTpl = $this->templateFileManager
                ->getTemplate('accessInfoPortal', 'subject', User::ENTITY_TYPE);
            $bodyTpl = $this->templateFileManager
                ->getTemplate('accessInfoPortal', 'body', User::ENTITY_TYPE);

            $urlList = [];

            $portalList = $this->entityManager
                ->getRDBRepositoryByClass(Portal::class)
                ->distinct()
                ->join('users')
                ->where([
                    'isActive' => true,
                    'users.id' => $user->getId(),
                ])
                ->find();

            foreach ($portalList as $portal) {
                /** @var Portal $portal */
                $this->getPortalRepository()->loadUrlField($portal);

                $urlList[] = $portal->getUrl() . $urlSuffix;
            }

            if (count($urlList) === 0) {
                return [null, null, null];
            }

            $data['siteUrlList'] = $urlList;

            return [$subjectTpl, $bodyTpl, $data];
        }

        $subjectTpl = $this->templateFileManager->getTemplate('accessInfo', 'subject', User::ENTITY_TYPE);
        $bodyTpl = $this->templateFileManager->getTemplate('accessInfo', 'body', User::ENTITY_TYPE);

        $data['siteUrl'] = $siteUrl;

        return [$subjectTpl, $bodyTpl, $data];
    }

    private function isSmtpConfigured(): bool
    {
        return
            $this->emailSender->hasSystemSmtp() ||
            $this->config->get('internalSmtpServer');
    }

    private function getPortalRepository(): PortalRepository
    {
        /** @var PortalRepository */
        return $this->entityManager->getRDBRepository(Portal::ENTITY_TYPE);
    }

    /**
     * @throws NoSmtp
     */
    private function getSmtpParams(): ?SmtpParams
    {
        if ($this->emailSender->hasSystemSmtp()) {
            return null;
        }

        $server = $this->config->get('internalSmtpServer');

        if (!$server) {
            throw new NoSmtp("No SMTP configured to send access info.");
        }

        /** @var int $port */
        $port = $this->config->get('internalSmtpPort');

        return SmtpParams
            ::create($server, $port)
            ->withAuth($this->config->get('internalSmtpAuth'))
            ->withUsername($this->config->get('internalSmtpUsername'))
            ->withPassword($this->config->get('internalSmtpPassword'))
            ->withSecurity($this->config->get('internalSmtpSecurity'))
            ->withFromAddress(
                $this->config->get('internalOutboundEmailFromAddress') ??
                $this->config->get('outboundEmailFromAddress')
            );
    }
}
