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

namespace Espo\Services;

use Laminas\Mail\Message;

use Espo\Services\EmailAccount as EmailAccountService;
use Espo\Services\InboundEmail as InboundEmailService;
use Espo\Entities\Preferences;
use Espo\Entities\Attachment;
use Espo\Entities\UserData;
use Espo\Repositories\UserData as UserDataRepository;

use Espo\{
    ORM\Entity,
    Entities\User,
    Entities\Email as EmailEntity,
};

use Espo\Core\{
    Exceptions\Error,
    Exceptions\ErrorSilent,
    Exceptions\Forbidden,
    Exceptions\NotFound,
    Exceptions\BadRequest,
    Di,
    Select\Where\Item as WhereItem,
    Mail\Sender,
    Mail\SmtpParams,
    Record\CreateParams,
};

use Exception;
use Throwable;
use stdClass;

class Email extends Record implements

    Di\EmailSenderAware,
    Di\CryptAware,
    Di\FileStorageManagerAware
{
    use Di\EmailSenderSetter;
    use Di\CryptSetter;
    use Di\FileStorageManagerSetter;

    protected $getEntityBeforeUpdate = true;

    protected $allowedForUpdateAttributeList = [
        'parentType',
        'parentId',
        'parentName',
        'teamsIds',
        'teamsNames',
        'assignedUserId',
        'assignedUserName',
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

    public function getUserSmtpParams(string $userId): ?SmtpParams
    {
        $user = $this->entityManager->getEntity('User', $userId);

        if (!$user) {
            return null;
        }

        $fromAddress = $user->get('emailAddress');

        if ($fromAddress) {
            $fromAddress = strtolower($fromAddress);
        }

        /** @var Preferences|null $preferences */
        $preferences = $this->entityManager->getEntity('Preferences', $user->getId());

        if (!$preferences) {
            return null;
        }

        $smtpParams = $preferences->getSmtpParams();

        if ($smtpParams) {
            if (array_key_exists('password', $smtpParams)) {
                $smtpParams['password'] = $this->crypt->decrypt($smtpParams['password']);
            }
        }

        if (!$smtpParams && $fromAddress) {
            $emailAccountService = $this->getEmailAccountService();

            $emailAccount = $emailAccountService->findAccountForUser($user, $fromAddress);

            if ($emailAccount && $emailAccount->get('useSmtp')) {
                $smtpParams = $emailAccountService->getSmtpParamsFromAccount($emailAccount);
            }
        }

        if (!$smtpParams) {
            return null;
        }

        $smtpParams['fromName'] = $user->get('name');

        if ($fromAddress) {
            $this->applySmtpHandler($user->getId(), $fromAddress, $smtpParams);

            $smtpParams['fromAddress'] = $fromAddress;
        }

        return SmtpParams::fromArray($smtpParams);
    }

    public function sendEntity(EmailEntity $entity, ?User $user = null): void
    {
        $emailSender = $this->emailSender->create();

        $userAddressList = [];

        if ($user) {
            $emailAddressCollection = $this->entityManager
                ->getRDBRepository('User')
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
            $primaryUserAddress = strtolower($user->get('emailAddress'));

            if ($primaryUserAddress === $fromAddress) {
                /** @var Preferences|null $preferences */
                $preferences = $this->entityManager->getEntity('Preferences', $user->getId());

                if ($preferences) {
                    $smtpParams = $preferences->getSmtpParams();

                    if ($smtpParams) {
                        if (array_key_exists('password', $smtpParams)) {
                            $smtpParams['password'] = $this->crypt->decrypt($smtpParams['password']);
                        }
                    }
                }
            }

            $emailAccountService = $this->getEmailAccountService();

            $emailAccount = $emailAccountService->findAccountForUser($user, $originalFromAddress);

            if (!$smtpParams) {
                if ($emailAccount && $emailAccount->get('useSmtp')) {
                    $smtpParams = $emailAccountService->getSmtpParamsFromAccount($emailAccount);
                }
            }

            if ($smtpParams) {
                $smtpParams['fromName'] = $user->get('name');
            }
        }

        if ($user) {
            if ($smtpParams) {
                $this->applySmtpHandler($user->getId(), $fromAddress, $smtpParams);

                $emailSender->withSmtpParams($smtpParams);
            }
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

        if (!$smtpParams && $fromAddress === strtolower($this->config->get('outboundEmailFromAddress'))) {
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

        if (!$smtpParams) {
            if ($user && in_array($fromAddress, $userAddressList)) {
                $emailSender->withParams([
                    'fromName' => $user->get('name')
                ]);
            }
        }

        $params = [];

        $parent = null;

        if ($entity->get('parentType') && $entity->get('parentId')) {
            $parent = $this->entityManager
                ->getEntity($entity->get('parentType'), $entity->get('parentId'));

            if ($parent) {
                if ($entity->get('parentType') == 'Case') {
                    if ($parent->get('inboundEmailId')) {
                        $inboundEmail = $this->entityManager
                            ->getEntity('InboundEmail', $parent->get('inboundEmailId'));

                        if ($inboundEmail && $inboundEmail->get('replyToAddress')) {
                            $params['replyToAddress'] = $inboundEmail->get('replyToAddress');
                        }
                    }
                }
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

            throw ErrorSilent::createWithBody('sendingFail', json_encode($errorData));
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

    protected function applySmtpHandler(string $userId, string $emailAddress, array &$params)
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

        $handlerClassName = $smtpHandlers->$emailAddress;

        $handler = null;

        try {
            $handler = $this->getInjection('injectableFactory')->create($handlerClassName);
        }
        catch (Throwable $e) {
            $this->log->error(
                "Email sending: Could not create Smtp Handler for {$emailAddress}. Error: " .
                $e->getMessage() . "."
            );
        }

        if (method_exists($handler, 'applyParams')) {
            $handler->applyParams($userId, $emailAddress, $params);
        }
    }

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

    public function create(stdClass $data, CreateParams $params): Entity
    {
        /** @var EmailEntity */
        $entity = parent::create($data, $params);

        if ($entity->get('status') === EmailEntity::STATUS_SENDING) {
            $this->sendEntity($entity, $this->getUser());
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

    protected function afterUpdateEntity(Entity $entity, $data)
    {
        /** @var EmailEntity $entity */

        if ($entity->get('status') === EmailEntity::STATUS_SENDING) {
            $this->sendEntity($entity, $this->getUser());
        }

        $this->loadAdditionalFields($entity);

        if (!isset($data->from) && !isset($data->to) && !isset($data->cc)) {
            $entity->clear('nameHash');
            $entity->clear('idHash');
            $entity->clear('typeHash');
        }
    }

    public function getEntity(?string $id = null): ?Entity
    {
        $entity = parent::getEntity($id);

        if ($entity && $id && !$entity->get('isRead')) {
            $this->markAsRead($entity->getId());
        }

        return $entity;
    }

    public function markAsReadByIdList(array $idList, $userId = null)
    {
        foreach ($idList as $id) {
            $this->markAsRead($id, $userId);
        }

        return true;
    }

    public function markAsNotReadByIdList(array $idList, $userId = null)
    {
        foreach ($idList as $id) {
            $this->markAsNotRead($id, $userId);
        }

        return true;
    }

    public function markAsImportantByIdList(array $idList, $userId = null)
    {
        foreach ($idList as $id) {
            $this->markAsImportant($id, $userId);
        }

        return true;
    }

    public function markAsNotImportantByIdList(array $idList, $userId = null)
    {
        foreach ($idList as $id) {
            $this->markAsNotImportant($id, $userId);
        }

        return true;
    }

    public function moveToTrashByIdList(array $idList, $userId = null)
    {
        foreach ($idList as $id) {
            $this->moveToTrash($id, $userId);
        }

        return true;
    }

    public function moveToFolderByIdList(array $idList, $folderId, $userId = null)
    {
        foreach ($idList as $id) {
            $this->moveToFolder($id, $folderId, $userId);
        }

        return true;
    }

    public function retrieveFromTrashByIdList(array $idList, $userId = null)
    {
        foreach ($idList as $id) {
            $this->retrieveFromTrash($id, $userId);
        }

        return true;
    }

    public function markAllAsRead(?string $userId = null)
    {
        $userId = $userId ?? $this->getUser()->getId();

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

        $update = $this->entityManager->getQueryBuilder()->update()
            ->in('Notification')
            ->set(['read' => true])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'relatedType' => 'Email',
                'read' => false,
                'type' => 'EmailReceived',
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);

        return true;
    }

    public function markAsRead(string $id, ?string $userId = null)
    {
        $userId = $userId ?? $this->getUser()->getId();

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

    public function markAsNotRead(string $id, ?string $userId = null)
    {
        $userId = $userId ?? $this->getUser()->getId();

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

    public function markAsImportant(string $id, ?string $userId = null)
    {
        $userId = $userId ?? $this->getUser()->getId();

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

    public function markAsNotImportant(string $id, ?string $userId = null)
    {
        $userId = $userId ?? $this->getUser()->getId();

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

    public function moveToTrash(string $id, ?string $userId = null)
    {
        $userId = $userId ?? $this->getUser()->getId();

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

    public function retrieveFromTrash(string $id, ?string $userId = null)
    {
        $userId = $userId ?? $this->getUser()->getId();

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

    public function markNotificationAsRead(string $id, string $userId)
    {
        $update = $this->entityManager->getQueryBuilder()->update()
            ->in('Notification')
            ->set(['read' => true])
            ->where([
                'deleted' => false,
                'userId' => $userId,
                'relatedType' => 'Email',
                'relatedId' => $id,
                'read' => false,
                'type' => 'EmailReceived',
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);
    }

    public function moveToFolder(string $id, ?string $folderId, ?string $userId = null)
    {
        $userId = $userId ?? $this->getUser()->getId();

        if ($folderId === 'inbox') {
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

        if ($string) {
            if (stripos($string, '<') !== false) {
                $fromName = trim(preg_replace('/(<.*>)/', '', $string), '" ');
            }
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

        if (!$this->getAcl()->checkEntity($email, 'read')) {
            throw new Forbidden();
        }

        $email->loadLinkMultipleField('attachments');

        $attachmentsIds = $email->get('attachmentsIds');

        foreach ($attachmentsIds as $attachmentId) {
            /** @var Attachment|null $source */
            $source = $this->entityManager->getEntity('Attachment', $attachmentId);

            if ($source) {
                $attachment = $this->entityManager->getEntity('Attachment');

                $attachment->set('role', 'Attachment');
                $attachment->set('type', $source->get('type'));
                $attachment->set('size', $source->get('size'));
                $attachment->set('global', $source->get('global'));
                $attachment->set('name', $source->get('name'));
                $attachment->set('sourceId', $source->getSourceId());
                $attachment->set('storage', $source->get('storage'));

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

                    $names->{$attachment->getId()} = $attachment->get('name');
                }
            }
        }

        return (object) [
            'ids' => $ids,
            'names' => $names,
        ];
    }

    public function sendTestEmail(array $data)
    {
        $smtpParams = $data;

        if (empty($smtpParams['auth'])) {
            unset($smtpParams['username']);
            unset($smtpParams['password']);
            unset($smtpParams['authMechanism']);
        }

        $userId = $data['userId'] ?? null;
        $fromAddress = $data['fromAddress'] ?? null;

        if ($userId) {
            if ($userId !== $this->getUser()->getId() && !$this->getUser()->isAdmin()) {
                throw new Forbidden();
            }
        }

        $email = $this->entityManager->getEntity('Email');

        $email->set([
            'subject' => 'EspoCRM: Test Email',
            'isHtml' => false,
            'to' => $data['emailAddress'],
        ]);

        $type = $data['type'] ?? null;
        $id = $data['id'] ?? null;

        if ($type === 'emailAccount' && $id) {
            $emailAccount = $this->entityManager->getEntity('EmailAccount', $id);

            if ($emailAccount && $emailAccount->get('smtpHandler')) {
                $this->getEmailAccountService()->applySmtpHandler($emailAccount, $smtpParams);
            }
        }

        if ($type === 'inboundEmail' && $id) {
            $inboundEmail = $this->entityManager->getEntity('InboundEmail', $id);

            if ($inboundEmail && $inboundEmail->get('smtpHandler')) {
                $this->getInboundEmailService()->applySmtpHandler($inboundEmail, $smtpParams);
            }
        }

        if ($userId) {
            if ($fromAddress) {
                $this->applySmtpHandler($userId, $fromAddress, $smtpParams);
            }
        }

        $emailSender = $this->emailSender;

        try {
            $emailSender
                ->withSmtpParams($smtpParams)
                ->send($email);
        }
        catch (Exception $e) {
            $this->log->warning("Email sending:" . $e->getMessage() . "; " . $e->getCode());

            $errorData = [
                'message' => $e->getMessage(),
            ];

            throw ErrorSilent::createWithBody('sendingFail', json_encode($errorData));
        }

        return true;
    }

    protected function beforeUpdateEntity(Entity $entity, $data)
    {
        /** @var EmailEntity $entity */

        $skipFilter = false;

        if ($this->getUser()->isAdmin()) {
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
            $entity->set('status', 'Archived');
        }

        if (!$skipFilter) {
            foreach ($entity->getAttributeList() as $attribute) {
                if (in_array($attribute, $this->allowedForUpdateAttributeList)) {
                    continue;
                }

                $entity->clear($attribute);
            }
        }

        if ($entity->get('status') == EmailEntity::STATUS_SENDING) {
            $messageId = Sender::generateMessageId($entity);

            $entity->set('messageId', '<' . $messageId . '>');
        }
    }

    public function getFoldersNotReadCounts(): stdClass
    {
        $data = [];

        $selectBuilder = $this->selectBuilderFactory
            ->create()
            ->from('Email')
            ->withAccessControlFilter();

        $draftsSelecBuilder = clone $selectBuilder;

        $selectBuilder->withWhere(
            WhereItem::fromRaw([
                'type' => 'isTrue',
                'attribute' => 'isNotRead',
            ])
        );

        $folderIdList = ['inbox', 'drafts'];

        $emailFolderList = $this->entityManager
            ->getRDBRepository('EmailFolder')
            ->where([
                'assignedUserId' => $this->getUser()->getId(),
            ])
            ->find();

        foreach ($emailFolderList as $folder) {
            $folderIdList[] = $folder->getId();
        }

        foreach ($folderIdList as $folderId) {
            $itemSelectBuilder = clone $selectBuilder;

            if ($folderId === 'drafts') {
                $itemSelectBuilder = clone $draftsSelecBuilder;
            }

            $itemSelectBuilder->withWhere(
                WhereItem::fromRaw([
                   'type' => 'inFolder',
                   'attribute' => 'folderId',
                   'value' => $folderId,
                ])
            );

            $data[$folderId] = $this->entityManager
                ->getRDBRepository('Email')
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
            ->getRDBRepository('Email')
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
