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

use Espo\{
    ORM\Entity,
    Entities,
};

use Espo\Core\{
    Exceptions\Error,
    Exceptions\ErrorSilent,
    Exceptions\Forbidden,
    Exceptions\NotFound,
    Exceptions\BadRequest,
    Di,
    Select\Where\Item as WhereItem,
};

use Exception;
use Throwable;
use StdClass;

class Email extends Record implements

    Di\EmailSenderAware,
    Di\CryptAware,
    Di\FileStorageManagerAware
{
    use Di\EmailSenderSetter;
    use Di\CryptSetter;
    use Di\FileStorageManagerSetter;

    private $streamService = null;

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

    private $fromEmailAddressNameCache = [];

    protected function getFileStorageManager()
    {
        return $this->fileStorageManager;
    }

    protected function getCrypt()
    {
        return $this->crypt;
    }

    public function getUserSmtpParams(string $userId) : ?array
    {
        $user = $this->getEntityManager()->getEntity('User', $userId);
        if (!$user) return null;

        $fromAddress = $user->get('emailAddress');
        if ($fromAddress)
            $fromAddress = strtolower($fromAddress);

        $preferences = $this->getEntityManager()->getEntity('Preferences', $user->id);
        if (!$preferences) return null;

        $smtpParams = $preferences->getSmtpParams();
        if ($smtpParams) {
            if (array_key_exists('password', $smtpParams)) {
                $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
            }
        }

        if (!$smtpParams && $fromAddress) {
            $emailAccountService = $this->getServiceFactory()->create('EmailAccount');
            $emailAccount = $emailAccountService->findAccountForUser($user, $fromAddress);

            if ($emailAccount && $emailAccount->get('useSmtp')) {
                $smtpParams = $emailAccountService->getSmtpParamsFromAccount($emailAccount);
            }
        }

        if ($smtpParams) {
            $smtpParams['fromName'] = $user->get('name');

            if ($fromAddress) {
                $this->applySmtpHandler($user->id, $fromAddress, $smtpParams);
            }
        }

        return $smtpParams;
    }

    public function sendEntity(Entities\Email $entity, ?Entities\User $user = null)
    {
        $emailSender = $this->emailSender->create();

        $userAddressList = [];

        if ($user) {
            $emailAddressCollection = $this->entityManager
                ->getRepository('User')
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
                $preferences = $this->getEntityManager()->getEntity('Preferences', $user->id);
                if ($preferences) {
                    $smtpParams = $preferences->getSmtpParams();
                    if ($smtpParams) {
                        if (array_key_exists('password', $smtpParams)) {
                            $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
                        }
                    }
                }
            }

            $emailAccountService = $this->getServiceFactory()->create('EmailAccount');
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
                if ($fromAddress) {
                    $this->applySmtpHandler($user->id, $fromAddress, $smtpParams);
                }

                $emailSender->withSmtpParams($smtpParams);
            }
        }

        if (!$smtpParams) {
            $inboundEmailService = $this->getServiceFactory()->create('InboundEmail');

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

        if (!$smtpParams && $fromAddress === strtolower($this->getConfig()->get('outboundEmailFromAddress'))) {
            if (!$this->getConfig()->get('outboundEmailIsShared')) {
                throw new Error("Email sending: Can not use system SMTP. System SMTP is not shared.");
            }

            $emailSender->withParams([
                'fromName' => $this->getConfig()->get('outboundEmailFromName'),
            ]);
        }

        if (!$smtpParams && !$this->getConfig()->get('outboundEmailIsShared')) {
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
            $parent = $this->getEntityManager()->getEntity($entity->get('parentType'), $entity->get('parentId'));

            if ($parent) {
                if ($entity->get('parentType') == 'Case') {
                    if ($parent->get('inboundEmailId')) {
                        $inboundEmail = $this->getEntityManager()->getEntity('InboundEmail', $parent->get('inboundEmailId'));
                        if ($inboundEmail && $inboundEmail->get('replyToAddress')) {
                            $params['replyToAddress'] = $inboundEmail->get('replyToAddress');
                        }
                    }
                }
            }
        }

        $this->validateEmailAddresses($entity);

        $message = new Message();

        try {
            $emailSender
                ->withParams($params)
                ->withMessage($message)
                ->send($entity);
        }
        catch (Exception $e) {
            $entity->set('status', 'Draft');

            $this->getEntityManager()->saveEntity($entity, ['silent' => true]);

            $GLOBALS['log']->error("Email sending:" . $e->getMessage() . "; " . $e->getCode());

            $errorData = [
                'id' => $entity->id,
                'message' => $e->getMessage(),
            ];

            throw ErrorSilent::createWithBody('sendingFail', json_encode($errorData));
        }

        $this->getEntityManager()->saveEntity($entity, ['isJustSent' => true]);

        if ($message) {
            if ($inboundEmail) {
                $entity->addLinkMultipleId('inboundEmails', $inboundEmail->id);

                if ($inboundEmail->get('storeSentEmails')) {
                    try {
                        $inboundEmailService = $this->getServiceFactory()->create('InboundEmail');
                        $inboundEmailService->storeSentMessage($inboundEmail, $message);
                    }
                    catch (Exception $e) {
                        $GLOBALS['log']->error(
                            "Email sending: Could not store sent email (Group Email Account {$inboundEmail->id}): " .
                            $e->getMessage() . "."
                        );
                    }
                }
            } else if ($emailAccount) {
                $entity->addLinkMultipleId('emailAccounts', $emailAccount->id);

                if ($emailAccount->get('storeSentEmails')) {
                    try {
                        $emailAccountService = $this->getServiceFactory()->create('EmailAccount');
                        $emailAccountService->storeSentMessage($emailAccount, $message);
                    }
                    catch (Exception $e) {
                        $GLOBALS['log']->error(
                            "Email sending: Could not store sent email (Email Account {$emailAccount->id}): " .
                            $e->getMessage() . "."
                        );
                    }
                }
            }
        }

        if ($parent) {
            $this->getStreamService()->noteEmailSent($parent, $entity);
        }
    }

    protected function applySmtpHandler(string $userId, string $emailAddress, array &$params)
    {
        $userData = $this->getEntityManager()->getRepository('UserData')->getByUserId($userId);
        if ($userData) {
            $smtpHandlers = $userData->get('smtpHandlers') ?? (object) [];
            if (is_object($smtpHandlers)) {
                if (isset($smtpHandlers->$emailAddress)) {
                    $handlerClassName = $smtpHandlers->$emailAddress;
                    try {
                        $handler = $this->getInjection('injectableFactory')->create($handlerClassName);
                    }
                    catch (Throwable $e) {
                        $GLOBALS['log']->error(
                            "Email sending: Could not create Smtp Handler for {$emailAddress}. Error: " . $e->getMessage() . "."
                        );
                    }
                    if (method_exists($handler, 'applyParams')) {
                        $handler->applyParams($userId, $emailAddress, $params);
                        return;
                    }
                }
            }
        }
    }

    public function validateEmailAddresses(Entities\Email $entity)
    {
        $from = $entity->get('from');
        if ($from) {
            if (!filter_var($from, \FILTER_VALIDATE_EMAIL)) {
                throw new Error('From email address is not valid.');
            }
        }

        foreach ($entity->getToList() as $address) {
            if (!filter_var($address, \FILTER_VALIDATE_EMAIL)) {
                throw new Error('To email address is not valid.');
            }
        }

        foreach ($entity->getCcList() as $address) {
            if (!filter_var($address, \FILTER_VALIDATE_EMAIL)) {
                throw new Error('CC email address is not valid.');
            }
        }

        foreach ($entity->getBccList() as $address) {
            if (!filter_var($address, \FILTER_VALIDATE_EMAIL)) {
                throw new Error('BCC email address is not valid.');
            }
        }
    }

    protected function getStreamService()
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->getServiceFactory()->create('Stream');
        }
        return $this->streamService;
    }

    public function create(StdClass $data) : Entity
    {
        $entity = parent::create($data);

        if ($entity && $entity->get('status') == 'Sending') {
            $this->sendEntity($entity, $this->getUser());
        }

        return $entity;
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        if ($entity->get('status') == 'Sending') {
            $messageId = \Espo\Core\Mail\Sender::generateMessageId($entity);
            $entity->set('messageId', '<' . $messageId . '>');
        }
    }

    protected function afterUpdateEntity(Entity $entity, $data)
    {
        if ($entity && $entity->get('status') == 'Sending') {
            $this->sendEntity($entity, $this->getUser());
        }

        $this->loadAdditionalFields($entity);

        if (!isset($data->from) && !isset($data->to) && !isset($data->cc)) {
            $entity->clear('nameHash');
            $entity->clear('idHash');
            $entity->clear('typeHash');
        }
    }

    public function loadFromField(Entity $entity)
    {
        $this->getEntityManager()->getRepository('Email')->loadFromField($entity);
    }

    public function loadToField(Entity $entity)
    {
        $this->getEntityManager()->getRepository('Email')->loadToField($entity);
    }

    public function loadCcField(Entity $entity)
    {
        $this->getEntityManager()->getRepository('Email')->loadCcField($entity);
    }

    public function loadBccField(Entity $entity)
    {
        $this->getEntityManager()->getRepository('Email')->loadBccField($entity);
    }

    public function loadReplyToField(Entity $entity)
    {
        $this->getEntityManager()->getRepository('Email')->loadReplyToField($entity);
    }

    public function getEntity(?string $id = null) : ?Entity
    {
        $entity = parent::getEntity($id);

        if ($entity && $id && !$entity->get('isRead')) {
            $this->markAsRead($entity->id);
        }

        return $entity;
    }

    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);

        $this->loadFromField($entity);
        $this->loadToField($entity);
        $this->loadCcField($entity);
        $this->loadBccField($entity);
        $this->loadReplyToField($entity);

        $this->loadNameHash($entity);

        $this->loadUserColumnFields($entity);
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
        $userId = $userId ?? $this->getUser()->id;

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
        $userId = $userId ?? $this->getUser()->id;

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
        $userId = $userId ?? $this->getUser()->id;

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
        $userId = $userId ?? $this->getUser()->id;

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
        $userId = $userId ?? $this->getUser()->id;

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
        $userId = $userId ?? $this->getUser()->id;

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
        $userId = $userId ?? $this->getUser()->id;

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
        $userId = $userId ?? $this->getUser()->id;

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

    static public function parseFromName(?string $string) : string
    {
        $fromName = '';

        if ($string) {
            if (stripos($string, '<') !== false) {
                $fromName = trim(preg_replace('/(<.*>)/', '', $string), '" ');
            }
        }

        return $fromName;
    }

    static public function parseFromAddress(?string $string) : string
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

    public function loadAdditionalFieldsForList(Entity $entity)
    {
        parent::loadAdditionalFieldsForList($entity);

        $userEmailAdddressIdList = [];

        $eaCollection = $this->entityManager
            ->getRepository('User')
            ->getRelation($this->getUser(), 'emailAddresses')
            ->find();

        foreach ($eaCollection as $ea) {
            $userEmailAdddressIdList[] = $ea->id;
        }

        if (
            in_array($entity->get('fromEmailAddressId'), $userEmailAdddressIdList) ||
            $entity->get('createdById') === $this->getUser()->id
        ) {
            $entity->loadLinkMultipleField('toEmailAddresses');
            $idList = $entity->get('toEmailAddressesIds');
            $names = $entity->get('toEmailAddressesNames');

            if (!empty($idList)) {
                $arr = [];

                foreach ($idList as $emailAddressId) {
                    $person = $this->getEntityManager()->getRepository('EmailAddress')
                        ->getEntityByAddressId($emailAddressId, null, true);
                    if ($person) {
                        $arr[] = $person->get('name');
                    } else {
                        $arr[] = $names->$emailAddressId;
                    }
                }

                $entity->set('personStringData', 'To: ' . implode(', ', $arr));
            }

            return;
        }

        $fromEmailAddressId = $entity->get('fromEmailAddressId');

        if (empty($fromEmailAddressId)) {
            return;
        }

        if (!array_key_exists($fromEmailAddressId, $this->fromEmailAddressNameCache)) {
            $person = $this->getEntityManager()->getRepository('EmailAddress')
                ->getEntityByAddressId($fromEmailAddressId, null, true);

            if ($person) {
                $fromName = $person->get('name');
            } else {
                $fromName = null;
            }

            $this->fromEmailAddressNameCache[$fromEmailAddressId] = $fromName;
        }

        $fromName = $this->fromEmailAddressNameCache[$fromEmailAddressId];

        if (!$fromName) {
            $fromName = $entity->get('fromName');
            if (!$fromName) {
                $fromName = $entity->get('fromEmailAddressName');
            }
        }

        $entity->set('personStringData', $fromName);
    }

    public function loadUserColumnFields(Entity $entity)
    {
        $emailUser = $this->entityManager->getRepository('EmailUser')
            ->select(['isRead', 'isImportant', 'inTrash'])
            ->where([
                'deleted' => false,
                'userId' => $this->getUser()->id,
                'emailId' => $entity->id,
            ])
            ->findOne();

        if (!$emailUser) {
            $entity->set('isRead', null);
            $entity->clear('isImportant');
            $entity->clear('inTrash');

            return;
        }

        $entity->set([
            'isRead' => $emailUser->get('isRead'),
            'isImportant' => $emailUser->get('isImportant'),
            'inTrash' => $emailUser->get('inTrash'),
        ]);
    }

    public function loadNameHash(Entity $entity, array $fieldList = ['from', 'to', 'cc', 'bcc', 'replyTo'])
    {
        $this->getEntityManager()->getRepository('Email')->loadNameHash($entity, $fieldList);
    }

    public function copyAttachments(string $emailId, ?string $parentType, ?string $parentId)
    {
        return $this->getCopiedAttachments($emailId, $parentType, $parentId);
    }

    public function getCopiedAttachments(string $id, ?string $parentType = null, ?string $parentId = null)
    {
        $ids = [];
        $names = (object) [];

        if (empty($id)) {
            throw new BadRequest();
        }
        $email = $this->getEntityManager()->getEntity('Email', $id);
        if (!$email) {
            throw new NotFound();
        }
        if (!$this->getAcl()->checkEntity($email, 'read')) {
            throw new Forbidden();
        }
        $email->loadLinkMultipleField('attachments');
        $attachmentsIds = $email->get('attachmentsIds');

        foreach ($attachmentsIds as $attachmentId) {
            $source = $this->getEntityManager()->getEntity('Attachment', $attachmentId);
            if ($source) {
                $attachment = $this->getEntityManager()->getEntity('Attachment');
                $attachment->set('role', 'Attachment');
                $attachment->set('type', $source->get('type'));
                $attachment->set('size', $source->get('size'));
                $attachment->set('global', $source->get('global'));
                $attachment->set('name', $source->get('name'));
                $attachment->set('sourceId', $source->getSourceId());
                $attachment->set('storage', $source->get('storage'));

                if (!empty($parentType) && !empty($parentId)) {
                    $attachment->set('parentType', $parentType);
                    $attachment->set('parentId', $parentId);
                }

                if ($this->getFileStorageManager()->exists($source)) {
                    $this->getEntityManager()->saveEntity($attachment);

                    $contents = $this->getFileStorageManager()->getContents($source) ?? '';

                    $this->getFileStorageManager()->putContents($attachment, $contents);

                    $ids[] = $attachment->id;

                    $names->{$attachment->id} = $attachment->get('name');
                }
            }
        }

        return [
            'ids' => $ids,
            'names' => $names
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
            if ($userId !== $this->getUser()->id && !$this->getUser()->isAdmin()) {
                throw new Forbidden();
            }
        }

        $email = $this->getEntityManager()->getEntity('Email');

        $email->set([
            'subject' => 'EspoCRM: Test Email',
            'isHtml' => false,
            'to' => $data['emailAddress'],
        ]);

        $type = $data['type'] ?? null;
        $id = $data['id'] ?? null;

        if ($type === 'emailAccount' && $id) {
            $emailAccount = $this->getEntityManager()->getEntity('EmailAccount', $id);

            if ($emailAccount && $emailAccount->get('smtpHandler')) {
                $this->getServiceFactory()->create('EmailAccount')->applySmtpHandler($emailAccount, $smtpParams);
            }
        }

        if ($type === 'inboundEmail' && $id) {
            $inboundEmail = $this->getEntityManager()->getEntity('InboundEmail', $id);

            if ($inboundEmail && $inboundEmail->get('smtpHandler')) {
                $this->getServiceFactory()->create('InboundEmail')->applySmtpHandler($inboundEmail, $smtpParams);
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
            $GLOBALS['log']->warning("Email sending:" . $e->getMessage() . "; " . $e->getCode());

            $errorData = [
                'message' => $e->getMessage(),
            ];

            throw ErrorSilent::createWithBody('sendingFail', json_encode($errorData));
        }

        return true;
    }

    protected function beforeUpdateEntity(Entity $entity, $data)
    {
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

        if ($entity->get('status') === 'Draft') {
            $skipFilter = true;
        }

        if ($entity->get('status') === 'Sending' && $entity->getFetched('status') === 'Draft') {
            $skipFilter = true;
        }

        if ($entity->isAttributeChanged('status') && $entity->getFetched('status') === 'Archived') {
            $entity->set('status', 'Archived');
        }

        if (!$skipFilter) {
            foreach ($entity->getAttributeList() as $attribute) {
                if (in_array($attribute, $this->allowedForUpdateAttributeList)) continue;
                $entity->clear($attribute);
            }
        }

        if ($entity->get('status') == 'Sending') {
            $messageId = \Espo\Core\Mail\Sender::generateMessageId($entity);
            $entity->set('messageId', '<' . $messageId . '>');
        }
    }

    public function getFoldersNotReadCounts()
    {
        $data = [];

        $selectBuilder = $this->selectBuilderFactory
            ->create()
            ->from('Email')
            ->withAccessControlFilter();

        $draftsSelectParams = clone $selectBuilder;

        $selectBuilder->withWhere(
            WhereItem::fromRaw([
                'type' => 'isTrue',
                'attribute' => 'isNotRead',
            ])
        );

        $folderIdList = ['inbox', 'drafts'];

        $emailFolderList = $this->getEntityManager()
            ->getRepository('EmailFolder')
            ->where([
                'assignedUserId' => $this->getUser()->id,
            ])
            ->find();

        foreach ($emailFolderList as $folder) {
            $folderIdList[] = $folder->id;
        }

        foreach ($folderIdList as $folderId) {
            $itemSelectBuilder = $selectBuilder;

            if ($folderId === 'drafts') {
                $itemSelectBuilder = $draftsSelectParams;
            }

            $itemSelectBuilder->withWhere(
                WhereItem::fromRaw([
                   'type' => 'inFolder',
                   'attribute' => 'folderId',
                   'value' => $folderId,
                ])
            );

            $data[$folderId] = $this->entityManager
                ->getRepository('Email')
                ->clone($itemSelectBuilder->build())
                ->count();
        }

        return $data;
    }

    public function isPermittedAssignedUser(Entity $entity) : bool
    {
        return true;
    }

    public function isPermittedAssignedUsers(Entity $entity) : bool
    {
        return true;
    }
}
