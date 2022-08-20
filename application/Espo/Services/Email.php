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

use Espo\Services\EmailAccount as EmailAccountService;
use Espo\Services\InboundEmail as InboundEmailService;

use Espo\Repositories\UserData as UserDataRepository;

use Espo\Core\Utils\Json;

use Espo\{
    Modules\Crm\Entities\CaseObj,
    ORM\Entity,
    Entities\User,
    Entities\Email as EmailEntity,
};

use Espo\Entities\EmailFolder;
use Espo\Entities\InboundEmail;
use Espo\Entities\EmailAccount;
use Espo\Entities\Notification;
use Espo\Entities\Preferences;
use Espo\Entities\Attachment;
use Espo\Entities\UserData;

use Espo\Core\{
    Acl\Table,
    Exceptions\Error,
    Exceptions\ErrorSilent,
    Exceptions\Forbidden,
    Exceptions\NotFound,
    Exceptions\BadRequest,
    Di,
    Mail\Exceptions\SendingError,
    Select\Where\Item as WhereItem,
    Mail\Sender,
    Mail\SmtpParams,
    Record\CreateParams};

use Exception;
use Throwable;
use stdClass;

/**
 * @extends Record<\Espo\Entities\Email>
 */
class Email extends Record implements

    Di\EmailSenderAware,
    Di\CryptAware,
    Di\FileStorageManagerAware
{
    use Di\EmailSenderSetter;
    use Di\CryptSetter;
    use Di\FileStorageManagerSetter;

    protected $getEntityBeforeUpdate = true;

    /**
     * @var string[]
     */
    protected $allowedForUpdateFieldList = [
        'parent',
        'teams',
        'assignedUser',
    ];

    protected $mandatorySelectAttributeList = [
        'name',
        'createdById',
        'dateSent',
        'fromString',
        'fromEmailAddressId',
        'fromEmailAddressName',
        'parentId',
        'parentType',
        'isHtml',
        'isReplied',
        'status',
        'accountId',
        'folderId',
        'messageId',
        'sentById',
        'replyToString',
        'hasAttachment',
    ];

    private const FOLDER_INBOX = 'inbox';
    private const FOLDER_DRAFTS = 'drafts';

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

        /** @var ?Preferences $preferences */
        $preferences = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $user->getId());

        if (!$preferences) {
            return null;
        }

        $smtpParams = $preferences->getSmtpParams();

        if ($smtpParams && array_key_exists('password', $smtpParams)) {
            $smtpParams['password'] = $this->crypt->decrypt($smtpParams['password']);
        }

        if (!$smtpParams && $fromAddress) {
            $emailAccountService = $this->getEmailAccountService();

            $emailAccount = $emailAccountService->findAccountForUser($user, $fromAddress);

            if ($emailAccount && $emailAccount->isAvailableForSending()) {
                $smtpParams = $emailAccountService->getSmtpParamsFromAccount($emailAccount);
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

    /**
     * @throws BadRequest
     * @throws SendingError
     * @throws Error
     */
    public function sendEntity(EmailEntity $entity, ?User $user = null): void
    {
        if (!$this->fieldValidationManager->check($entity, 'to', 'required')) {
            $entity->set('status', EmailEntity::STATUS_DRAFT);

            $this->entityManager->saveEntity($entity, ['silent' => true]);

            throw new BadRequest("Empty To address.");
        }

        $emailSender = $this->emailSender->create();

        $userAddressList = [];

        if ($user) {
            $emailAddressCollection = $this->entityManager
                ->getRDBRepository(User::ENTITY_TYPE)
                ->getRelation($user, 'emailAddresses')
                ->find();

            foreach ($emailAddressCollection as $ea) {
                $userAddressList[] = $ea->get('lower');
            }
        }

        $fromAddress = strtolower($entity->get('from'));
        $originalFromAddress = $entity->get('from');

        if (!$fromAddress) {
            throw new Error("Email sending: Can't send with empty 'from' address.");
        }

        $inboundEmail = null;
        $emailAccount = null;

        $smtpParams = null;

        if ($user && in_array($fromAddress, $userAddressList)) {
            $primaryUserAddress = strtolower($user->getEmailAddress() ?? '');

            if ($primaryUserAddress === $fromAddress) {
                /** @var ?Preferences $preferences */
                $preferences = $this->entityManager->getEntity(Preferences::ENTITY_TYPE, $user->getId());

                if ($preferences) {
                    $smtpParams = $preferences->getSmtpParams();

                    if ($smtpParams && array_key_exists('password', $smtpParams)) {
                        $smtpParams['password'] = $this->crypt->decrypt($smtpParams['password']);
                    }
                }
            }

            $emailAccountService = $this->getEmailAccountService();

            $emailAccount = $emailAccountService->findAccountForUser($user, $originalFromAddress);

            if (!$smtpParams) {
                if ($emailAccount && $emailAccount->isAvailableForSending()) {
                    $smtpParams = $emailAccountService->getSmtpParamsFromAccount($emailAccount);
                }
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
            $inboundEmailService = $this->getInboundEmailService();

            if ($user) {
                $inboundEmail = $inboundEmailService->findSharedAccountForUser($user, $originalFromAddress);
            } else {
                $inboundEmail = $inboundEmailService->findAccountForSending($originalFromAddress);
            }

            if ($inboundEmail) {
                $smtpParams = $inboundEmailService->getSmtpParamsFromAccount($inboundEmail);
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
        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');

        if ($parentType && $parentId) {
            $parent = $this->entityManager->getEntityById($parentType, $parentId);
        }

        if (
            $parent &&
            $parent->getEntityType() == CaseObj::ENTITY_TYPE &&
            $parent->get('inboundEmailId')
        ) {
            /** @var string $inboundEmailId */
            $inboundEmailId = $parent->get('inboundEmailId');

            /** @var ?InboundEmail $inboundEmail */
            $inboundEmail = $this->entityManager->getEntityById(InboundEmail::ENTITY_TYPE, $inboundEmailId);

            if ($inboundEmail && $inboundEmail->get('replyToAddress')) {
                $params['replyToAddress'] = $inboundEmail->get('replyToAddress');
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
            $entity->set('status', EmailEntity::STATUS_DRAFT);

            $this->entityManager->saveEntity($entity, ['silent' => true]);

            $this->log->error("Email sending:" . $e->getMessage() . "; " . $e->getCode());

            $errorData = [
                'id' => $entity->getId(),
                'message' => $e->getMessage(),
            ];

            throw ErrorSilent::createWithBody('sendingFail', Json::encode($errorData));
        }

        $this->entityManager->saveEntity($entity, ['isJustSent' => true]);

        if ($inboundEmail) {
            $entity->addLinkMultipleId('inboundEmails', $inboundEmail->getId());

            if ($inboundEmail->get('storeSentEmails')) {
                try {
                    $inboundEmailService = $this->getInboundEmailService();

                    $inboundEmailService->storeSentMessage($inboundEmail, $message);
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
            $entity->addLinkMultipleId('emailAccounts', $emailAccount->getId());

            if ($emailAccount->get('storeSentEmails')) {
                try {
                    $emailAccountService = $this->getEmailAccountService();

                    $emailAccountService->storeSentMessage($emailAccount, $message);
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
            $this->getStreamService()->noteEmailSent($parent, $entity);
        }
    }

    /**
     * @param array<string,mixed> $params
     */
    protected function applySmtpHandler(string $userId, string $emailAddress, array &$params): void
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
     * @throws Error
     */
    public function validateEmailAddresses(EmailEntity $entity): void
    {
        $from = $entity->get('from');

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

    /**
     * @throws BadRequest
     * @throws Error
     * @throws \Espo\Core\Exceptions\Forbidden
     * @throws \Espo\Core\Exceptions\Conflict
     * @throws \Espo\Core\Exceptions\BadRequest
     */
    public function create(stdClass $data, CreateParams $params): Entity
    {
        /** @var EmailEntity $entity */
        $entity = parent::create($data, $params);

        if ($entity->get('status') === EmailEntity::STATUS_SENDING) {
            $this->sendEntity($entity, $this->user);
        }

        return $entity;
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        /** @var EmailEntity $entity */

        if ($entity->get('status') === EmailEntity::STATUS_SENDING) {
            $messageId = Sender::generateMessageId($entity);

            $entity->set('messageId', '<' . $messageId . '>');
        }
    }

    /**
     * @throws BadRequest
     * @throws Error
     */
    protected function afterUpdateEntity(Entity $entity, $data)
    {
        /** @var EmailEntity $entity */

        if ($entity->get('status') === EmailEntity::STATUS_SENDING) {
            $this->sendEntity($entity, $this->user);
        }

        $this->loadAdditionalFields($entity);

        if (!isset($data->from) && !isset($data->to) && !isset($data->cc)) {
            $entity->clear('nameHash');
            $entity->clear('idHash');
            $entity->clear('typeHash');
        }
    }

    public function getEntity(string $id): ?Entity
    {
        $entity = parent::getEntity($id);

        if ($entity && !$entity->get('isRead')) {
            $this->markAsRead($entity->getId());
        }

        return $entity;
    }

    /**
     * @param string[] $idList
     */
    public function markAsReadByIdList(array $idList, ?string $userId = null): bool
    {
        foreach ($idList as $id) {
            $this->markAsRead($id, $userId);
        }

        return true;
    }

    /**
     * @param string[] $idList
     */
    public function markAsNotReadByIdList(array $idList, ?string $userId = null): bool
    {
        foreach ($idList as $id) {
            $this->markAsNotRead($id, $userId);
        }

        return true;
    }

    /**
     * @param string[] $idList
     */
    public function markAsImportantByIdList(array $idList, ?string $userId = null): bool
    {
        foreach ($idList as $id) {
            $this->markAsImportant($id, $userId);
        }

        return true;
    }

    /**
     * @param string[] $idList
     */
    public function markAsNotImportantByIdList(array $idList, ?string $userId = null): bool
    {
        foreach ($idList as $id) {
            $this->markAsNotImportant($id, $userId);
        }

        return true;
    }

    /**
     * @param string[] $idList
     */
    public function moveToTrashByIdList(array $idList, ?string $userId = null): bool
    {
        foreach ($idList as $id) {
            $this->moveToTrash($id, $userId);
        }

        return true;
    }

    /**
     * @param string[] $idList
     */
    public function moveToFolderByIdList(array $idList, ?string $folderId, ?string $userId = null): bool
    {
        foreach ($idList as $id) {
            $this->moveToFolder($id, $folderId, $userId);
        }

        return true;
    }

    /**
     * @param string[] $idList
     */
    public function retrieveFromTrashByIdList(array $idList, ?string $userId = null): bool
    {
        foreach ($idList as $id) {
            $this->retrieveFromTrash($id, $userId);
        }

        return true;
    }

    public function markAllAsRead(?string $userId = null): bool
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager->getQueryBuilder()->update()
            ->in('EmailUser')
            ->set(['isRead' => true])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'isRead' => false,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        $update = $this->entityManager->getQueryBuilder()
            ->update()
            ->in(Notification::ENTITY_TYPE)
            ->set(['read' => true])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'relatedType' => EmailEntity::ENTITY_TYPE,
                'read' => false,
                'type' => Notification::TYPE_EMAIL_RECEIVED,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        return true;
    }

    public function markAsRead(string $id, ?string $userId = null): bool
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager->getQueryBuilder()->update()
            ->in('EmailUser')
            ->set(['isRead' => true])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        $this->markNotificationAsRead($id, $userId);

        return true;
    }

    public function markAsNotRead(string $id, ?string $userId = null): bool
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager->getQueryBuilder()->update()
            ->in('EmailUser')
            ->set(['isRead' => false])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        return true;
    }

    public function markAsImportant(string $id, ?string $userId = null): bool
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager->getQueryBuilder()->update()
            ->in('EmailUser')
            ->set(['isImportant' => true])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        return true;
    }

    public function markAsNotImportant(string $id, ?string $userId = null): bool
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager->getQueryBuilder()->update()
            ->in('EmailUser')
            ->set(['isImportant' => false])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        return true;
    }

    public function moveToTrash(string $id, ?string $userId = null): bool
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager->getQueryBuilder()->update()
            ->in('EmailUser')
            ->set(['inTrash' => true])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        $this->markNotificationAsRead($id, $userId);

        return true;
    }

    public function retrieveFromTrash(string $id, ?string $userId = null): bool
    {
        $userId = $userId ?? $this->user->getId();

        $update = $this->entityManager->getQueryBuilder()->update()
            ->in('EmailUser')
            ->set(['inTrash' => false])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        return true;
    }

    public function markNotificationAsRead(string $id, string $userId): void
    {
        $update = $this->entityManager->getQueryBuilder()->update()
            ->in(Notification::ENTITY_TYPE)
            ->set(['read' => true])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'relatedType' => EmailEntity::ENTITY_TYPE,
                'relatedId' => $id,
                'read' => false,
                'type' => Notification::TYPE_EMAIL_RECEIVED,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);
    }

    public function moveToFolder(string $id, ?string $folderId, ?string $userId = null): bool
    {
        $userId = $userId ?? $this->user->getId();

        if ($folderId === self::FOLDER_INBOX) {
            $folderId = null;
        }

        $update = $this->entityManager->getQueryBuilder()->update()
            ->in('EmailUser')
            ->set([
                'folderId' => $folderId,
                'inTrash' => false,
            ])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'emailId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        return true;
    }

    static public function parseFromName(?string $string): string
    {
        $fromName = '';

        if ($string && stripos($string, '<') !== false) {
            /** @var string $replasedString */
            $replasedString = preg_replace('/(<.*>)/', '', $string);

            $fromName = trim($replasedString, '" ');
        }

        return $fromName;
    }

    static public function parseFromAddress(?string $string): string
    {
        $fromAddress = '';

        if ($string) {
            if (stripos($string, '<') !== false) {
                if (preg_match('/<(.*)>/', $string, $matches)) {
                    $fromAddress = trim($matches[1]);
                }
            } else {
                $fromAddress = $string;
            }
        }

        return $fromAddress;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function getCopiedAttachments(
        string $id,
        ?string $parentType = null,
        ?string $parentId = null,
        ?string $field = null
    ): stdClass {

        $ids = [];
        $names = (object) [];

        if (empty($id)) {
            throw new BadRequest();
        }

        /** @var EmailEntity|null $email */
        $email = $this->entityManager->getEntity(EmailEntity::ENTITY_TYPE, $id);

        if (!$email) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntity($email, Table::ACTION_READ)) {
            throw new Forbidden();
        }

        $email->loadLinkMultipleField('attachments');

        $attachmentsIds = $email->get('attachmentsIds');

        foreach ($attachmentsIds as $attachmentId) {
            /** @var ?Attachment $source */
            $source = $this->entityManager->getEntityById(Attachment::ENTITY_TYPE, $attachmentId);

            if ($source) {
                /** @var Attachment $attachment */
                $attachment = $this->entityManager->getNewEntity(Attachment::ENTITY_TYPE);

                $attachment->set('role', Attachment::ROLE_ATTACHMENT);
                $attachment->set('type', $source->getType());
                $attachment->set('size', $source->getSize());
                $attachment->set('global', $source->get('global'));
                $attachment->set('name', $source->getName());
                $attachment->set('sourceId', $source->getSourceId());
                $attachment->set('storage', $source->getStorage());

                if ($field) {
                    $attachment->set('field', $field);
                }

                if ($parentType) {
                    $attachment->set('parentType', $parentType);
                }

                if ($parentType && $parentId) {
                    $attachment->set('parentId', $parentId);
                }

                if ($this->fileStorageManager->exists($source)) {
                    $this->entityManager->saveEntity($attachment);

                    $contents = $this->fileStorageManager->getContents($source);

                    $this->fileStorageManager->putContents($attachment, $contents);

                    $ids[] = $attachment->getId();

                    $names->{$attachment->getId()} = $attachment->getName();
                }
            }
        }

        return (object) [
            'ids' => $ids,
            'names' => $names,
        ];
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    private function obtainSendTestEmailPassword(?string $type, ?string $id): ?string
    {
        if ($type === 'preferences') {
            if (!$id) {
                return null;
            }

            if (!$this->user->isAdmin() && $id !== $this->user->getId()) {
                throw new Forbidden();
            }

            $preferences = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $id);

            if (!$preferences) {
                throw new NotFound();
            }

            return $this->crypt->decrypt($preferences->get('smtpPassword'));
        }

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

            $emailAccount = $this->entityManager->getEntity(InboundEmail::ENTITY_TYPE, $id);

            if (!$emailAccount) {
                throw new NotFound();
            }

            return $this->crypt->decrypt($emailAccount->get('smtpPassword'));
        }

        return $this->config->get('smtpPassword');
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

        /** @var EmailEntity $email */
        $email = $this->entityManager->getNewEntity(EmailEntity::ENTITY_TYPE);

        $email->set([
            'subject' => 'EspoCRM: Test Email',
            'isHtml' => false,
            'to' => $data['emailAddress'],
        ]);

        if ($type === 'emailAccount' && $id) {
            /** @var ?EmailAccount $emailAccount */
            $emailAccount = $this->entityManager->getEntityById(EmailAccount::ENTITY_TYPE, $id);

            if ($emailAccount && $emailAccount->get('smtpHandler')) {
                $this->getEmailAccountService()->applySmtpHandler($emailAccount, $smtpParams);
            }
        }

        if ($type === 'inboundEmail' && $id) {
            /** @var ?InboundEmail $inboundEmail */
            $inboundEmail = $this->entityManager->getEntityById(InboundEmail::ENTITY_TYPE, $id);

            if ($inboundEmail && $inboundEmail->get('smtpHandler')) {
                $this->getInboundEmailService()->applySmtpHandler($inboundEmail, $smtpParams);
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

    protected function beforeUpdateEntity(Entity $entity, $data)
    {
        /** @var EmailEntity $entity */

        $skipFilter = false;

        if ($this->user->isAdmin()) {
            $skipFilter = true;
        }

        if ($entity->isManuallyArchived()) {
            $skipFilter = true;
        } else {
            if ($entity->isAttributeChanged('dateSent')) {
                $entity->set('dateSent', $entity->getFetched('dateSent'));
            }
        }

        if ($entity->get('status') === EmailEntity::STATUS_DRAFT) {
            $skipFilter = true;
        }

        if (
            $entity->get('status') === EmailEntity::STATUS_SENDING &&
            $entity->getFetched('status') === EmailEntity::STATUS_DRAFT
        ) {
            $skipFilter = true;
        }

        if (
            $entity->isAttributeChanged('status') &&
            $entity->getFetched('status') === EmailEntity::STATUS_ARCHIVED
        ) {
            $entity->set('status', EmailEntity::STATUS_ARCHIVED);
        }

        if (!$skipFilter) {
            $this->clearEntityForUpdate($entity);
        }

        if ($entity->getStatus() == EmailEntity::STATUS_SENDING) {
            $messageId = Sender::generateMessageId($entity);

            $entity->set('messageId', '<' . $messageId . '>');
        }
    }

    private function clearEntityForUpdate(EmailEntity $email): void
    {
        $fieldDefsList = $this->entityManager
            ->getDefs()
            ->getEntity(EmailEntity::ENTITY_TYPE)
            ->getFieldList();

        foreach ($fieldDefsList as $fieldDefs) {
            $field = $fieldDefs->getName();

            if ($fieldDefs->getParam('isCustom')) {
                continue;
            }

            if (in_array($field, $this->allowedForUpdateFieldList)) {
                continue;
            }

            $attributeList = $this->fieldUtil->getAttributeList(EmailEntity::ENTITY_TYPE, $field);

            foreach ($attributeList as $attribute) {
                $email->clear($attribute);
            }
        }
    }

    public function getFoldersNotReadCounts(): stdClass
    {
        $data = [];

        $selectBuilder = $this->selectBuilderFactory
            ->create()
            ->from(EmailEntity::ENTITY_TYPE)
            ->withAccessControlFilter();

        $draftsSelectBuilder = clone $selectBuilder;

        $selectBuilder->withWhere(
            WhereItem::fromRaw([
                'type' => 'isTrue',
                'attribute' => 'isNotRead',
            ])
        );

        $folderIdList = [self::FOLDER_INBOX, self::FOLDER_DRAFTS];

        $emailFolderList = $this->entityManager
            ->getRDBRepository(EmailFolder::ENTITY_TYPE)
            ->where([
                'assignedUserId' => $this->user->getId(),
            ])
            ->find();

        foreach ($emailFolderList as $folder) {
            $folderIdList[] = $folder->getId();
        }

        foreach ($folderIdList as $folderId) {
            $itemSelectBuilder = clone $selectBuilder;

            if ($folderId === self::FOLDER_DRAFTS) {
                $itemSelectBuilder = clone $draftsSelectBuilder;
            }

            $itemSelectBuilder->withWhere(
                WhereItem::fromRaw([
                   'type' => 'inFolder',
                   'attribute' => 'folderId',
                   'value' => $folderId,
                ])
            );

            $data[$folderId] = $this->entityManager
                ->getRDBRepository(EmailEntity::ENTITY_TYPE)
                ->clone($itemSelectBuilder->build())
                ->count();
        }

        return (object) $data;
    }

    private function getRepliedEmailMessageId(EmailEntity $email): ?string
    {
        if (!$email->get('repliedId')) {
            return null;
        }

        /** @var EmailEntity|null $replied */
        $replied = $this->entityManager
            ->getRDBRepository(EmailEntity::ENTITY_TYPE)
            ->select(['messageId'])
            ->where([
                'id' => $email->get('repliedId')
            ])
            ->findOne();

        if (!$replied) {
            return null;
        }

        return $replied->getMessageId();
    }

    private function getEmailAccountService(): EmailAccountService
    {
        /** @var EmailAccountService */
        return $this->injectableFactory->create(EmailAccountService::class);
    }

    private function getInboundEmailService(): InboundEmailService
    {
        /** @var InboundEmailService */
        return $this->injectableFactory->create(InboundEmailService::class);
    }

    private function getUserDataRepository(): UserDataRepository
    {
        /** @var UserDataRepository */
        return $this->entityManager->getRepository(UserData::ENTITY_TYPE);
    }
}
