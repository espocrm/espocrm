<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\ORM\Entity;
use \Espo\Entities;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\BadRequest;

class Email extends Record
{
    protected function init()
    {
        parent::init();
        $this->addDependencyList([
            'container',
            'preferences',
            'fileManager',
            'crypt',
            'serviceFactory',
            'fileStorageManager'
        ]);
    }

    private $streamService = null;

    protected $getEntityBeforeUpdate = true;

    protected $skipSelectTextAttributes = true;

    protected $allowedForUpdateAttributeList = [
        'parentType', 'parentId', 'parentName', 'teamsIds', 'teamsNames', 'assignedUserId', 'assignedUserName'
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
        'hasAttachment'
    ];

    private $fromEmailAddressNameCache = [];

    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
    }

    protected function getFileStorageManager()
    {
        return $this->getInjection('fileStorageManager');
    }

    protected function getMailSender()
    {
        return $this->getInjection('container')->get('mailSender');
    }

    protected function getPreferences()
    {
        return $this->injections['preferences'];
    }

    protected function getCrypt()
    {
        return $this->injections['crypt'];
    }

    protected function getServiceFactory()
    {
        return $this->injections['serviceFactory'];
    }

    public function getUserSmtpParams(string $userId)
    {
        $user = $this->getEntityManager()->getEntity('User', $userId);
        if (!$user) return;

        $fromAddress = $user->get('emailAddress');
        if ($fromAddress)
            $fromAddress = strtolower($fromAddress);

        $preferences = $this->getEntityManager()->getEntity('Preferences', $user->id);
        if (!$preferences) return;

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

    protected function send(Entities\Email $entity)
    {
        $emailSender = $this->getMailSender();

        $userAddressList = [];
        foreach ($this->getUser()->get('emailAddresses') as $ea) {
            $userAddressList[] = $ea->get('lower');
        }

        $primaryUserAddress = strtolower($this->getUser()->get('emailAddress'));
        $fromAddress = strtolower($entity->get('from'));
        $originalFromAddress = $entity->get('from');

        if (empty($fromAddress)) {
            throw new Error("Can't send with empty from address.");
        }

        $inboundEmail = null;
        $emailAccount = null;

        $smtpParams = null;
        if (in_array($fromAddress, $userAddressList)) {
            if ($primaryUserAddress === $fromAddress) {
                $smtpParams = $this->getPreferences()->getSmtpParams();
                if ($smtpParams) {
                    if (array_key_exists('password', $smtpParams)) {
                        $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
                    }
                }
            }

            $emailAccountService = $this->getServiceFactory()->create('EmailAccount');
            $emailAccount = $emailAccountService->findAccountForUser($this->getUser(), $originalFromAddress);

            if (!$smtpParams) {
                if ($emailAccount && $emailAccount->get('useSmtp')) {
                    $smtpParams = $emailAccountService->getSmtpParamsFromAccount($emailAccount);
                }
            }
            if ($smtpParams) {
                $smtpParams['fromName'] = $this->getUser()->get('name');
            }
        }

        if ($smtpParams) {
            if ($emailAddress) {
                $this->applySmtpHandler($this->getUser()->id, $emailAddress, $smtpParams);
            }
            $emailSender->useSmtp($smtpParams);
        }

        if (!$smtpParams) {
            $inboundEmailService = $this->getServiceFactory()->create('InboundEmail');
            $inboundEmail = $inboundEmailService->findSharedAccountForUser($this->getUser(), $originalFromAddress);
            if ($inboundEmail) {
                $smtpParams = $inboundEmailService->getSmtpParamsFromAccount($inboundEmail);
            }
            if ($smtpParams) {
                $emailSender->useSmtp($smtpParams);
            }
        }

        if (!$smtpParams && $fromAddress === strtolower($this->getConfig()->get('outboundEmailFromAddress'))) {
            if (!$this->getConfig()->get('outboundEmailIsShared')) {
                throw new Error('Can not use system SMTP. System account is not shared.');
            }
            $emailSender->setParams([
                'fromName' => $this->getConfig()->get('outboundEmailFromName')
            ]);
        }

        if (!$smtpParams && !$this->getConfig()->get('outboundEmailIsShared')) {
            throw new Error('No SMTP params found for '.$fromAddress.'.');
        }

        if (!$smtpParams) {
            if (in_array($fromAddress, $userAddressList)) {
                $emailSender->setParams([
                    'fromName' => $this->getUser()->get('name')
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

        $message = null;

        $this->validateEmailAddresses($entity);

        try {
            $emailSender->send($entity, $params, $message);
        } catch (\Exception $e) {
            $entity->set('status', 'Failed');
            $this->getEntityManager()->saveEntity($entity, array(
                'silent' => true
            ));
            throw new Error($e->getMessage(), $e->getCode());
        }

        if ($message) {
            if ($inboundEmail) {
                $entity->addLinkMultipleId('inboundEmails', $inboundEmail->id);

                if ($inboundEmail->get('storeSentEmails')) {
                    try {
                        $inboundEmailService = $this->getServiceFactory()->create('InboundEmail');
                        $inboundEmailService->storeSentMessage($inboundEmail, $message);
                    } catch (\Exception $e) {
                        $GLOBALS['log']->error("Could not store sent email (Group Email Account {$inboundEmail->id}): " . $e->getMessage());
                    }
                }
            } else if ($emailAccount) {
                $entity->addLinkMultipleId('emailAccounts', $emailAccount->id);
                if ($emailAccount->get('storeSentEmails')) {
                    try {
                        $emailAccountService = $this->getServiceFactory()->create('EmailAccount');
                        $emailAccountService->storeSentMessage($emailAccount, $message);
                    } catch (\Exception $e) {
                        $GLOBALS['log']->error("Could not store sent email (Email Account {$emailAccount->id}): " . $e->getMessage());
                    }
                }
            }
        }

        if ($parent) {
            $this->getStreamService()->noteEmailSent($parent, $entity);
        }

        $entity->set('isJustSent', true);

        $this->getEntityManager()->saveEntity($entity, ['isJustSent' => true]);
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
                        $handler = $this->getInjection('injectableFactory')->createByClassName($handlerClassName);
                    } catch (\Throwable $e) {
                        $GLOBALS['log']->error("Send Email: Could not create Smtp Handler for {$emailAddress}. Error: " . $e->getMessage());
                    }
                    if (method_exists($handler, 'applyParams')) {
                        $handler->applyParams($userId, $emailAddress, $params);
                    }
                }
            }
        }
    }

    public function validateEmailAddresses(\Espo\Entities\Email $entity)
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

    public function create($data)
    {
        $entity = parent::create($data);

        if ($entity && $entity->get('status') == 'Sending') {
            $this->send($entity);
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
            $this->send($entity);
        }

        $this->loadAdditionalFields($entity);
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

    public function getEntity($id = null)
    {
        $entity = parent::getEntity($id);

        if (!empty($entity) && !empty($id) && !$entity->get('isRead')) {
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

    public function markAllAsRead($userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET is_read = 1
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . "
        ";
        $pdo->query($sql);

        $sql = "
            UPDATE notification SET `read` = 1
            WHERE
                `deleted` = 0 AND
                `type` = 'EmailReceived' AND
                `related_type` = 'Email' AND
                `read` = 0 AND
                `user_id` = " . $pdo->quote($userId) . "
        ";
        $pdo->query($sql);

        return true;
    }

    public function markAsRead($id, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET is_read = 1
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);

        $this->markNotificationAsRead($id, $userId);

        return true;
    }

    public function markAsNotRead($id, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET is_read = 0
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);
        return true;
    }

    public function markAsImportant($id, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET is_important = 1
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);
        return true;
    }

    public function markAsNotImportant($id, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET is_important = 0
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);
        return true;
    }

    public function moveToTrash($id, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET in_trash = 1
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);

        $this->markNotificationAsRead($id, $userId);

        return true;
    }

    public function markNotificationAsRead($id, $userId)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = "
            UPDATE notification SET `read` = 1
            WHERE
                `deleted` = 0 AND
                `type` = 'EmailReceived' AND
                `related_type` = 'Email' AND
                `related_id` = " . $pdo->quote($id) ." AND
                `read` = 0 AND
                `user_id` = " . $pdo->quote($userId) . "
        ";
        $pdo->query($sql);
    }

    public function retrieveFromTrash($id, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET in_trash = 0
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);
        return true;
    }

    public function moveToFolder($id, $folderId, $userId = null)
    {
        if (!$userId) {
            $userId = $this->getUser()->id;
        }
        if ($folderId === 'inbox') {
            $folderId = null;
        }
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            UPDATE email_user SET folder_id = " . $this->getEntityManager()->getQuery()->quote($folderId) . "
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($userId) . " AND
                email_id = " . $pdo->quote($id) . "
        ";
        $pdo->query($sql);
        return true;
    }

    static public function parseFromName($string)
    {
        $fromName = '';
        if ($string) {
            if (stripos($string, '<') !== false) {
                $fromName = trim(preg_replace('/(<.*>)/', '', $string), '" ');
            }
        }
        return $fromName;
    }

    static public function parseFromAddress($string)
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
        foreach ($this->getUser()->get('emailAddresses') as $ea) {
            $userEmailAdddressIdList[] = $ea->id;
        }

        $status = $entity->get('status');
        if (in_array($entity->get('fromEmailAddressId'), $userEmailAdddressIdList) || $entity->get('createdById') === $this->getUser()->id) {
            $entity->loadLinkMultipleField('toEmailAddresses');
            $idList = $entity->get('toEmailAddressesIds');
            $names = $entity->get('toEmailAddressesNames');

            if (!empty($idList)) {
                $arr = [];
                foreach ($idList as $emailAddressId) {
                    $person = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddressId($emailAddressId, null, true);
                    if ($person) {
                        $arr[] = $person->get('name');
                    } else {
                        $arr[] = $names->$emailAddressId;
                    }
                }
                $entity->set('personStringData', 'To: ' . implode(', ', $arr));
            }
        } else {
            $fromEmailAddressId = $entity->get('fromEmailAddressId');
            if (!empty($fromEmailAddressId)) {
                if (!array_key_exists($fromEmailAddressId, $this->fromEmailAddressNameCache)) {
                    $person = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddressId($fromEmailAddressId, null, true);
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
        }
    }

    public function loadUserColumnFields(Entity $entity)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = "
            SELECT is_read AS 'isRead', is_important AS 'isImportant', in_trash AS 'inTrash' FROM email_user
            WHERE
                deleted = 0 AND
                user_id = " . $pdo->quote($this->getUser()->id) . " AND
                email_id = " . $pdo->quote($entity->id) . "
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();
        if ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $isRead = !empty($row['isRead']) ? true : false;
            $isImportant = !empty($row['isImportant']) ? true : false;
            $inTrash = !empty($row['inTrash']) ? true : false;

            $entity->set('isRead', $isRead);
            $entity->set('isImportant', $isImportant);
            $entity->set('inTrash', $inTrash);
        } else {
            $entity->set('isRead', null);
            $entity->clear('isImportant');
            $entity->clear('inTrash');
        }
    }

    public function loadNameHash(Entity $entity, array $fieldList = ['from', 'to', 'cc', 'bcc', 'replyTo'])
    {
        $this->getEntityManager()->getRepository('Email')->loadNameHash($entity, $fieldList);
    }

    protected function getSelectParams($params)
    {
        $searchByEmailAddress = false;
        if (!empty($params['where']) && is_array($params['where'])) {
            foreach ($params['where'] as $i => $p) {
                if (!empty($p['attribute']) && $p['attribute'] == 'emailAddress') {
                    $searchByEmailAddress = true;
                    $emailAddress = $p['value'];
                    unset($params['where'][$i]);
                }

            }
        }

        $selectManager = $this->getSelectManager($this->getEntityType());

        $selectParams = $selectManager->getSelectParams($params, true);

        if ($searchByEmailAddress) {
            $selectManager->whereEmailAddress($emailAddress, $selectParams);
        }

        return $selectParams;
    }

    public function copyAttachments($emailId, $parentType, $parentId)
    {
        return $this->getCopiedAttachments($emailId, $parentType, $parentId);
    }

    public function getCopiedAttachments($id, $parentType = null, $parentId = null)
    {
        $ids = array();
        $names = new \stdClass();

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

                if ($this->getFileStorageManager()->isFile($source)) {
                    $this->getEntityManager()->saveEntity($attachment);
                    $contents = $this->getFileStorageManager()->getContents($source);
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

    public function sendTestEmail($data)
    {
        $smtpParams = $data;

        if (empty($smtpParams['auth'])) {
            unset($smtpParams['username']);
            unset($smtpParams['password']);
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

        if ($userId) {
            if ($fromAddress) {
                $this->applySmtpHandler($userId, $fromAddress, $smtpParams);
            }
        }

        $emailSender = $this->getMailSender();
        $emailSender->useSmtp($smtpParams)->send($email);

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
        $data = array();

        $selectManager = $this->getSelectManager($this->getEntityType());
        $selectParams = $selectManager->getEmptySelectParams();
        $selectManager->applyAccess($selectParams);

        $draftsSelectParams = $selectParams;

        $selectParams['whereClause'][] = $selectManager->getWherePartIsNotReadIsTrue();

        $folderIdList = ['inbox', 'drafts'];

        $emailFolderList = $this->getEntityManager()->getRepository('EmailFolder')->where(['assignedUserId' => $this->getUser()->id])->find();
        foreach ($emailFolderList as $folder) {
            $folderIdList[] = $folder->id;
        }

        foreach ($folderIdList as $folderId) {
            if ($folderId === 'drafts') {
                $folderSelectParams = $draftsSelectParams;
            } else {
                $folderSelectParams = $selectParams;
            }
            $selectManager->applyFolder($folderId, $folderSelectParams);
            $selectManager->addUsersJoin($folderSelectParams);
            $data[$folderId] = $this->getEntityManager()->getRepository('Email')->count($folderSelectParams);
        }

        return $data;
    }

    public function isPermittedAssignedUser(Entity $entity)
    {
        return true;
    }

    public function isPermittedAssignedUsers(Entity $entity)
    {
        return true;
    }
}
