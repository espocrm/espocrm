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

namespace Espo\Tools\Email;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\ErrorSilent;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\FieldValidation\FieldValidationManager;
use Espo\Core\InjectableFactory;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Crypt;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Log;
use Espo\Entities\Email;
use Espo\Entities\EmailAccount;
use Espo\Entities\InboundEmail;
use Espo\Entities\User;
use Espo\Entities\UserData;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;
use Espo\Repositories\UserData as UserDataRepository;
use Espo\Services\EmailAccount as EmailAccountService;
use Espo\Services\InboundEmail as InboundEmailService;
use Espo\Services\Stream as StreamService;
use Exception;
use Laminas\Mail\Message;
use Throwable;

/**
 * Email sending service.
 */
class SendService
{
    /** @var (Email::STATUS_*)[] */
    private array $notAllowedStatusList = [
        Email::STATUS_ARCHIVED,
        Email::STATUS_SENT,
        Email::STATUS_BEING_IMPORTED,
    ];

    private User $user;
    private EntityManager $entityManager;
    private FieldValidationManager $fieldValidationManager;
    private EmailSender $emailSender;
    private EmailAccountService $emailAccountService;
    private InboundEmailService $inboundEmailService;
    private StreamService $streamService;
    private Config $config;
    private Log $log;
    private InjectableFactory $injectableFactory;
    private Acl $acl;
    private Crypt $crypt;

    public function __construct(
        User $user,
        EntityManager $entityManager,
        FieldValidationManager $fieldValidationManager,
        EmailSender $emailSender,
        EmailAccountService $emailAccountService,
        InboundEmailService $inboundEmailService,
        StreamService $streamService,
        Config $config,
        Log $log,
        InjectableFactory $injectableFactory,
        Acl $acl,
        Crypt $crypt
    ) {
        $this->user = $user;
        $this->entityManager = $entityManager;
        $this->fieldValidationManager = $fieldValidationManager;
        $this->emailSender = $emailSender;
        $this->emailAccountService = $emailAccountService;
        $this->inboundEmailService = $inboundEmailService;
        $this->streamService = $streamService;
        $this->config = $config;
        $this->log = $log;
        $this->injectableFactory = $injectableFactory;
        $this->acl = $acl;
        $this->crypt = $crypt;
    }

    /**
     * Send an email entity.
     *
     * @params Email $entity An email entity.
     * @params ?User $user A user from what to send.
     *
     * @throws BadRequest If not valid.
     * @throws SendingError On error while sending.
     * @throws Error An error.
     */
    public function send(Email $entity, ?User $user = null): void
    {
        if (in_array($entity->getStatus(), $this->notAllowedStatusList)) {
            throw new Error("Can't send email with status `{$entity->getStatus()}`.");
        }

        if (!$this->fieldValidationManager->check($entity, 'to', 'required')) {
            $entity->set('status', Email::STATUS_DRAFT);

            $this->entityManager->saveEntity($entity, ['silent' => true]);

            throw new BadRequest("Empty To address.");
        }

        $emailSender = $this->emailSender->create();

        $userAddressList = [];

        if ($user) {
            /** @var Collection<\Espo\Entities\EmailAddress> $emailAddressCollection */
            $emailAddressCollection = $this->entityManager
                ->getRDBRepositoryByClass(User::class)
                ->getRelation($user, 'emailAddresses')
                ->find();

            foreach ($emailAddressCollection as $ea) {
                $userAddressList[] = $ea->getLower();
            }
        }

        $originalFromAddress = $entity->getFromAddress();

        if (!$originalFromAddress) {
            throw new Error("Email sending: Can't send with empty 'from' address.");
        }

        $fromAddress = strtolower($originalFromAddress);

        $inboundEmail = null;
        $emailAccount = null;

        $smtpParams = null;

        if ($user && in_array($fromAddress, $userAddressList)) {
            $emailAccount = $this->emailAccountService->findAccountForUserForSending($user, $originalFromAddress);

            if ($emailAccount && $emailAccount->isAvailableForSending()) {
                $smtpParams = $this->emailAccountService->getSmtpParamsFromAccount($emailAccount);
            }

            if ($smtpParams) {
                $smtpParams['fromName'] = $user->getName();
            }
        }

        if ($user && $smtpParams) {
            $this->applySmtpHandler($user->getId(), $fromAddress, $smtpParams);

            $emailSender->withSmtpParams($smtpParams);
        }

        if (!$smtpParams) {
            $inboundEmail = $user ?
                $this->inboundEmailService->findSharedAccountForUser($user, $originalFromAddress) :
                $this->inboundEmailService->findAccountForSending($originalFromAddress);

            if ($inboundEmail) {
                $smtpParams = $this->inboundEmailService->getSmtpParamsFromAccount($inboundEmail);
            }

            if ($smtpParams) {
                $emailSender->withSmtpParams($smtpParams);
            }
        }

        if (
            !$smtpParams &&
            $fromAddress === strtolower($this->config->get('outboundEmailFromAddress'))
        ) {
            if (!$this->config->get('outboundEmailIsShared')) {
                throw new Error("Email sending: Can not use system SMTP. System SMTP is not shared.");
            }

            $emailSender->withParams([
                'fromName' => $this->config->get('outboundEmailFromName'),
            ]);
        }

        if (!$smtpParams && !$this->config->get('outboundEmailIsShared')) {
            throw new Error("Email sending: No SMTP params found for {$fromAddress}.");
        }

        if (
            !$smtpParams &&
            $user &&
            in_array($fromAddress, $userAddressList)
        ) {
            $emailSender->withParams(['fromName' => $user->getName()]);
        }

        $params = [];

        $parent = null;
        $parentId = $entity->getParentId();
        $parentType = $entity->getParentType();

        if ($parentType && $parentId) {
            $parent = $this->entityManager->getEntityById($parentType, $parentId);
        }

        // @todo Refactor? Move to a separate class? Make extensible?
        if (
            $parent instanceof CaseObj &&
            $parent->getInboundEmailId()
        ) {
            /** @var string $inboundEmailId */
            $inboundEmailId = $parent->getInboundEmailId();

            /** @var ?InboundEmail $inboundEmail */
            $inboundEmail = $this->entityManager->getEntityById(InboundEmail::ENTITY_TYPE, $inboundEmailId);

            if ($inboundEmail && $inboundEmail->getReplyToAddress()) {
                $params['replyToAddress'] = $inboundEmail->getReplyToAddress();
            }
        }

        $this->validateEmailAddresses($entity);

        $message = new Message();

        $repliedMessageId = $this->getRepliedEmailMessageId($entity);

        if ($repliedMessageId) {
            $message->getHeaders()->addHeaderLine('In-Reply-To', $repliedMessageId);
            $message->getHeaders()->addHeaderLine('References', $repliedMessageId);
        }

        try {
            $emailSender
                ->withParams($params)
                ->withMessage($message)
                ->send($entity);
        }
        catch (Exception $e) {
            $entity->set('status', Email::STATUS_DRAFT);

            $this->entityManager->saveEntity($entity, ['silent' => true]);

            $this->log->error("Email sending:" . $e->getMessage() . "; " . $e->getCode());

            $errorData = [
                'id' => $entity->getId(),
                'message' => $e->getMessage(),
            ];

            throw ErrorSilent::createWithBody('sendingFail', Json::encode($errorData));
        }

        if ($inboundEmail) {
            $entity->addLinkMultipleId('inboundEmails', $inboundEmail->getId());
        }
        else if ($emailAccount) {
            $entity->addLinkMultipleId('emailAccounts', $emailAccount->getId());
        }

        $this->entityManager->saveEntity($entity, ['isJustSent' => true]);

        if ($inboundEmail) {
            if ($inboundEmail->storeSentEmails()) {
                try {
                    $this->inboundEmailService->storeSentMessage($inboundEmail, $message);
                }
                catch (Exception $e) {
                    $this->log->error(
                        "Email sending: Could not store sent email (Group Email Account {$inboundEmail->getId()}): " .
                        $e->getMessage() . "."
                    );
                }
            }
        }
        else if ($emailAccount) {
            if ($emailAccount->storeSentEmails()) {
                try {
                    $this->emailAccountService->storeSentMessage($emailAccount, $message);
                }
                catch (Exception $e) {
                    $this->log->error(
                        "Email sending: Could not store sent email (Email Account {$emailAccount->getId()}): " .
                        $e->getMessage() . "."
                    );
                }
            }
        }

        if ($parent) {
            $this->streamService->noteEmailSent($parent, $entity);
        }
    }

    /**
     * @param array{
     *     type?: ?string,
     *     id?: ?string,
     *     username?: ?string,
     *     password?: ?string,
     *     auth?: bool,
     *     authMechanism?: ?string,
     *     userId?: ?string,
     *     fromAddress?: ?string,
     *     fromName?: ?string,
     *     server: string,
     *     port: int,
     *     security: string,
     *     emailAddress: string,
     * } $data
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function sendTestEmail(array $data): void
    {
        $smtpParams = $data;

        if (empty($smtpParams['auth'])) {
            unset($smtpParams['username']);
            unset($smtpParams['password']);
            unset($smtpParams['authMechanism']);
        }

        if (($smtpParams['password'] ?? null) === null) {
            $smtpParams['password'] = $this->obtainSendTestEmailPassword($data['type'] ?? null, $data['id'] ?? null);
        }

        $userId = $data['userId'] ?? null;
        $fromAddress = $data['fromAddress'] ?? null;
        $type = $data['type'] ?? null;
        $id = $data['id'] ?? null;

        if (
            $userId &&
            $userId !== $this->user->getId() &&
            !$this->user->isAdmin()
        ) {
            throw new Forbidden();
        }

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $email->set([
            'subject' => 'EspoCRM: Test Email',
            'isHtml' => false,
            'to' => $data['emailAddress'],
        ]);

        if ($type === 'emailAccount' && $id) {
            /** @var ?EmailAccount $emailAccount */
            $emailAccount = $this->entityManager->getEntityById(EmailAccount::ENTITY_TYPE, $id);

            if ($emailAccount && $emailAccount->get('smtpHandler')) {
                $this->emailAccountService->applySmtpHandler($emailAccount, $smtpParams);
            }
        }

        if ($type === 'inboundEmail' && $id) {
            /** @var ?InboundEmail $inboundEmail */
            $inboundEmail = $this->entityManager->getEntityById(InboundEmail::ENTITY_TYPE, $id);

            if ($inboundEmail && $inboundEmail->get('smtpHandler')) {
                $this->inboundEmailService->applySmtpHandler($inboundEmail, $smtpParams);
            }
        }

        if ($userId && $fromAddress) {
            $this->applySmtpHandler($userId, $fromAddress, $smtpParams);
        }

        $emailSender = $this->emailSender;

        try {
            $emailSender
                ->withSmtpParams($smtpParams)
                ->send($email);
        }
        catch (Exception $e) {
            $this->log->warning("Email sending:" . $e->getMessage() . "; " . $e->getCode());

            $errorData = ['message' => $e->getMessage()];

            throw ErrorSilent::createWithBody('sendingFail', Json::encode($errorData));
        }
    }

    /**
     * @throws Error
     */
    public function validateEmailAddresses(Email $entity): void
    {
        $from = $entity->getFromAddress();

        if ($from) {
            if (!filter_var($from, \FILTER_VALIDATE_EMAIL)) {
                throw new Error('From email address is not valid.');
            }
        }

        foreach ($entity->getToAddressList() as $address) {
            if (!filter_var($address, \FILTER_VALIDATE_EMAIL)) {
                throw new Error('To email address is not valid.');
            }
        }

        foreach ($entity->getCcAddressList() as $address) {
            if (!filter_var($address, \FILTER_VALIDATE_EMAIL)) {
                throw new Error('CC email address is not valid.');
            }
        }

        foreach ($entity->getBccAddressList() as $address) {
            if (!filter_var($address, \FILTER_VALIDATE_EMAIL)) {
                throw new Error('BCC email address is not valid.');
            }
        }
    }

    public function getUserSmtpParams(string $userId): ?SmtpParams
    {
        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            return null;
        }

        $fromAddress = $user->getEmailAddress();

        if ($fromAddress) {
            $fromAddress = strtolower($fromAddress);
        }

        $smtpParams = null;

        if ($fromAddress) {
            $emailAccount = $this->emailAccountService->findAccountForUserForSending($user, $fromAddress);

            if ($emailAccount) {
                $smtpParams = $this->emailAccountService->getSmtpParamsFromAccount($emailAccount);
            }
        }

        if (!$smtpParams) {
            return null;
        }

        $smtpParams['fromName'] = $user->getName();

        if ($fromAddress) {
            $this->applySmtpHandler($user->getId(), $fromAddress, $smtpParams);

            $smtpParams['fromAddress'] = $fromAddress;
        }

        return SmtpParams::fromArray($smtpParams);
    }

    private function getRepliedEmailMessageId(Email $email): ?string
    {
        $repliedLink = $email->getReplied();

        if (!$repliedLink) {
            return null;
        }

        /** @var ?Email $replied */
        $replied = $this->entityManager
            ->getRDBRepositoryByClass(Email::class)
            ->select(['messageId'])
            ->where(['id' => $repliedLink->getId()])
            ->findOne();

        if (!$replied) {
            return null;
        }

        return $replied->getMessageId();
    }

    /**
     * @param array<string,mixed> $params
     */
    private function applySmtpHandler(string $userId, string $emailAddress, array &$params): void
    {
        $userData = $this->getUserDataRepository()->getByUserId($userId);

        if (!$userData) {
            return;
        }

        $smtpHandlers = $userData->get('smtpHandlers') ?? (object) [];

        if (!is_object($smtpHandlers)) {
            return;
        }

        if (!isset($smtpHandlers->$emailAddress)) {
            return;
        }

        /** @var class-string<object> $handlerClassName */
        $handlerClassName = $smtpHandlers->$emailAddress;

        try {
            $handler = $this->injectableFactory->create($handlerClassName);
        }
        catch (Throwable $e) {
            $this->log->error(
                "Email sending: Could not create Smtp Handler for {$emailAddress}. Error: " .
                $e->getMessage() . "."
            );

            return;
        }

        if (method_exists($handler, 'applyParams')) {
            $handler->applyParams($userId, $emailAddress, $params);
        }
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    private function obtainSendTestEmailPassword(?string $type, ?string $id): ?string
    {
        if ($type === 'emailAccount') {
            if (!$this->acl->checkScope(EmailAccount::ENTITY_TYPE)) {
                throw new Forbidden();
            }

            if (!$id) {
                return null;
            }

            /** @var ?EmailAccount $emailAccount */
            $emailAccount = $this->entityManager->getEntityById(EmailAccount::ENTITY_TYPE, $id);

            if (!$emailAccount) {
                throw new NotFound();
            }

            if (
                !$this->user->isAdmin() &&
                $emailAccount->get('assignedUserId') !== $this->user->getId()
            ) {
                throw new Forbidden();
            }

            return $this->crypt->decrypt($emailAccount->get('smtpPassword'));
        }

        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        if ($type === 'inboundEmail') {
            if (!$id) {
                return null;
            }

            $emailAccount = $this->entityManager->getEntityById(InboundEmail::ENTITY_TYPE, $id);

            if (!$emailAccount) {
                throw new NotFound();
            }

            return $this->crypt->decrypt($emailAccount->get('smtpPassword'));
        }

        return $this->config->get('smtpPassword');
    }

    private function getUserDataRepository(): UserDataRepository
    {
        /** @var UserDataRepository */
        return $this->entityManager->getRepository(UserData::ENTITY_TYPE);
    }
}
