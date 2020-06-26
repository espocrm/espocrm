<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

use Espo\Core\Utils\Util;

use Espo\Core\{
    ORM\EntityManager,
    Htmlizer\Factory as HtmlizerFactory,
    Mail\Sender,
    Utils\Config,
    Utils\Metadata,
    Utils\Language,
    SelectManagerFactory,
    InjectableFactory,
    Utils\TemplateFileManager,
    Mail\Sender as MailSender,
};

class EmailNotification
{
    const HOURS_THERSHOLD = 5;

    const PROCESS_MAX_COUNT = 200;

    protected $htmlizer;

    protected $entityManager;
    protected $htmlizerFactory;
    protected $mailSender;
    protected $config;
    protected $selectManagerFactory;
    protected $injectableFactory;
    protected $templateFileManager;
    protected $metadata;

    public function __construct(
        EntityManager $entityManager,
        HtmlizerFactory $htmlizerFactory,
        MailSender $mailSender,
        Config $config,
        SelectManagerFactory $selectManagerFactory,
        InjectableFactory $injectableFactory,
        TemplateFileManager $templateFileManager,
        Metadata $metadata,
        Language $language
    ) {
        $this->entityManager = $entityManager;
        $this->htmlizerFactory = $htmlizerFactory;
        $this->mailSender = $mailSender;
        $this->config = $config;
        $this->selectManagerFactory = $selectManagerFactory;
        $this->injectableFactory = $injectableFactory;
        $this->templateFileManager = $templateFileManager;
        $this->metadata = $metadata;
        $this->language = $language;
    }

    protected $emailNotificationEntityHandlerHash = [];

    protected function getHtmlizer()
    {
        if (!$this->htmlizer) {
            $this->htmlizer = $this->htmlizerFactory->create(true);
        }
        return $this->htmlizer;
    }

    protected $userIdPortalCacheMap = [];

    public function notifyAboutAssignmentJob($data)
    {
        if (empty($data->userId)) return;
        if (empty($data->assignerUserId)) return;
        if (empty($data->entityId)) return;
        if (empty($data->entityType)) return;

        $userId = $data->userId;
        $assignerUserId = $data->assignerUserId;
        $entityId = $data->entityId;
        $entityType = $data->entityType;

        $user = $this->entityManager->getEntity('User', $userId);

        if (!$user) return;

        if ($user->isPortal()) return;

        $preferences = $this->entityManager->getEntity('Preferences', $userId);
        if (!$preferences) return;
        if (!$preferences->get('receiveAssignmentEmailNotifications')) return;

        $ignoreList = $preferences->get('assignmentEmailNotificationsIgnoreEntityTypeList') ?? [];
        if (in_array($entityType, $ignoreList)) return;

        $assignerUser = $this->entityManager->getEntity('User', $assignerUserId);
        $entity = $this->entityManager->getEntity($entityType, $entityId);
        if (!$entity) return true;
        if (!$assignerUser) return true;

        $this->loadParentNameFields($entity);

        if (!$entity->hasLinkMultipleField('assignedUsers')) {
            if ($entity->get('assignedUserId') !== $userId) return true;
        }

        $emailAddress = $user->get('emailAddress');
        if (!empty($emailAddress)) {
            $email = $this->entityManager->getEntity('Email');

            $subjectTpl = $this->templateFileManager->getTemplate('assignment', 'subject', $entity->getEntityType());
            $bodyTpl = $this->templateFileManager->getTemplate('assignment', 'body', $entity->getEntityType());

            $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

            $recordUrl = rtrim($this->config->get('siteUrl'), '/') .
                '/#' . $entity->getEntityType() . '/view/' . $entity->id;

            $data = [
                'userName' => $user->get('name'),
                'assignerUserName' => $assignerUser->get('name'),
                'recordUrl' => $recordUrl,
                'entityType' => $this->language->translate($entity->getEntityType(), 'scopeNames')
            ];
            $data['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($data['entityType']);

            $subject = $this->getHtmlizer()->render(
                $entity, $subjectTpl, 'assignment-email-subject-' . $entity->getEntityType(), $data, true
            );
            $body = $this->getHtmlizer()->render(
                $entity, $bodyTpl, 'assignment-email-body-' . $entity->getEntityType(), $data, true
            );

            $email->set([
                'subject' => $subject,
                'body' => $body,
                'isHtml' => true,
                'to' => $emailAddress,
                'isSystem' => true,
                'parentId' => $entity->id,
                'parentType' => $entity->getEntityType()
            ]);
            try {
                $this->mailSender->send($email);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
            }
        }

        return true;
    }

    public function process()
    {
        $mentionEmailNotifications = $this->config->get('mentionEmailNotifications');

        $streamEmailNotifications = $this->config->get('streamEmailNotifications');
        $portalStreamEmailNotifications = $this->config->get('portalStreamEmailNotifications');

        $typeList = [];
        if ($mentionEmailNotifications) {
            $typeList[] = 'MentionInPost';
        }

        if ($streamEmailNotifications || $portalStreamEmailNotifications) {
            $typeList[] = 'Note';
        }

        if (empty($typeList)) return;

        $fromDt = new \DateTime();
        $fromDt->modify('-' . self::HOURS_THERSHOLD . ' hours');

        $where = [
            'createdAt>' => $fromDt->format('Y-m-d H:i:s'),
            'read' => false,
            'emailIsProcessed' => false,
        ];

        $delay = $this->config->get('emailNotificationsDelay');
        if ($delay) {
            $delayDt = new \DateTime();
            $delayDt->modify('-' . $delay . ' seconds');
            $where[] = ['createdAt<' => $delayDt->format('Y-m-d H:i:s')];
        }

        $sqlArr = [];
        foreach ($typeList as $type) {
            $methodName = 'getNotificationSelectParams' . $type;
            $selectParams = $this->$methodName();
            $selectParams['whereClause'][] = $where;

            $sqlArr[] = $this->entityManager->getQuery()->createSelectQuery('Notification', $selectParams);
        }

        $maxCount = intval(self::PROCESS_MAX_COUNT);

        $sql = '' . implode(' UNION ', $sqlArr) . " ORDER BY number LIMIT 0, {$maxCount}";

        $notificationList = $this->entityManager->getRepository('Notification')->findByQuery($sql);

        foreach ($notificationList as $notification) {
            $notification->set('emailIsProcessed', true);

            $type = $notification->get('type');

            $methodName = 'processNotification' . ucfirst($type);
            if (method_exists($this, $methodName)) {
                $this->$methodName($notification);
            }

            $this->entityManager->saveEntity($notification);
        }
    }

    protected function getNotificationSelectParamsMentionInPost()
    {
        $selectManager = $this->selectManagerFactory->create('Notification');

        $selectParams = $selectManager->getEmptySelectParams();

        $selectParams['whereClause']['type'] = 'MentionInPost';

        return $selectParams;
    }

    protected function getNotificationSelectParamsNote()
    {
        $noteNotificationTypeList = $this->config->get('streamEmailNotificationsTypeList', []);

        $selectManager = $this->selectManagerFactory->create('Notification');

        $selectParams = $selectManager->getEmptySelectParams();

        $selectParams['whereClause']['type'] = 'Note';
        $selectParams['whereClause']['relatedType'] = 'Note';

        $selectParams['customJoin'] .= ' JOIN note ON notification.related_id = note.id';

        $selectParams['whereClause']['note.type'] = $noteNotificationTypeList;

        $entityList = $this->config->get('streamEmailNotificationsEntityList');

        if (empty($entityList)) {
            $selectParams['whereClause']['relatedParentType'] = null;
        } else {
            $selectParams['whereClause'][] = [
                'OR' => [
                    [
                        'relatedParentType' => $entityList
                    ],
                    [
                        'relatedParentType' => null
                    ]
                ]
            ];
        }

        $forInternal = $this->config->get('streamEmailNotifications');
        $forPortal = $this->config->get('portalStreamEmailNotifications');

        if ($forInternal && !$forPortal) {
            $selectParams['whereClause']['user.type!='] = 'portal';
        } else if (!$forInternal && $forPortal) {
            $selectParams['whereClause']['user.type'] = 'portal';
        }

        return $selectParams;
    }

    protected function processNotificationMentionInPost(Entity $notification)
    {
        if (!$notification->get('userId')) return;
        $userId = $notification->get('userId');
        $user = $this->entityManager->getEntity('User', $userId);

        if (!$user) return;

        $emailAddress = $user->get('emailAddress');
        if (!$emailAddress) return;

        $preferences = $this->entityManager->getEntity('Preferences', $userId);
        if (!$preferences) return;

        if (!$preferences->get('receiveMentionEmailNotifications')) return;

        if ($notification->get('relatedType') !== 'Note' || !$notification->get('relatedId')) return;
        $note = $this->entityManager->getEntity('Note', $notification->get('relatedId'));
        if (!$note) return;

        $parentId = $note->get('parentId');
        $parentType = $note->get('parentType');

        $data = [];

        if ($parentId && $parentType) {
            $parent = $this->entityManager->getEntity($parentType, $parentId);
            if (!$parent) return;

            $data['url'] = $this->getSiteUrl($user) . '/#' . $parentType . '/view/' . $parentId;
            $data['parentName'] = $parent->get('name');
            $data['parentType'] = $parentType;
            $data['parentId'] = $parentId;
        } else {
            $data['url'] = $this->getSiteUrl($user) . '/#Notification';
        }

        $data['userName'] = $note->get('createdByName');

        $post = $note->get('post') ?? '';
        $post = \Michelf\Markdown::defaultTransform($post);
        $data['post'] = $post;

        $subjectTpl = $this->templateFileManager->getTemplate('mention', 'subject');
        $bodyTpl = $this->templateFileManager->getTemplate('mention', 'body');

        $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

        $subject = $this->getHtmlizer()->render($note, $subjectTpl, 'mention-email-subject', $data, true);
        $body = $this->getHtmlizer()->render($note, $bodyTpl, 'mention-email-body', $data, true);

        $email = $this->entityManager->getEntity('Email');

        $email->set([
            'subject' => $subject,
            'body' => $body,
            'isHtml' => true,
            'to' => $emailAddress,
            'isSystem' => true
        ]);
        if ($parentId && $parentType) {
            $email->set([
                'parentId' => $parentId,
                'parentType' => $parentType
            ]);
        }

        try {
            $this->mailSender->send($email);
        } catch (\Exception $e) {
            $GLOBALS['log']->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    protected function processNotificationNote(Entity $notification)
    {
        if ($notification->get('relatedType') !== 'Note') return;
        if (!$notification->get('relatedId')) return;

        $note = $this->entityManager->getEntity('Note', $notification->get('relatedId'));
        if (!$note) return;

        $noteNotificationTypeList = $this->config->get('streamEmailNotificationsTypeList', []);

        if (!in_array($note->get('type'), $noteNotificationTypeList)) return;

        if (!$notification->get('userId')) return;
        $userId = $notification->get('userId');
        $user = $this->entityManager->getEntity('User', $userId);

        if (!$user) return;

        $emailAddress = $user->get('emailAddress');
        if (!$emailAddress) return;

        $preferences = $this->entityManager->getEntity('Preferences', $userId);
        if (!$preferences) return;
        if (!$preferences->get('receiveStreamEmailNotifications')) return;

        $methodName = 'processNotificationNote' . $note->get('type');

        if (!method_exists($this, $methodName)) return;

        $this->$methodName($note, $user);
    }

    protected function getEmailNotificationEntityHandler($entityType)
    {
        if (!array_key_exists($entityType, $this->emailNotificationEntityHandlerHash)) {
            $this->emailNotificationEntityHandlerHash[$entityType] = null;
            $className = $this->metadata->get(['app', 'emailNotifications', 'handlerClassNameMap', $entityType]);
            if ($className && class_exists($className)) {
                $handler = $this->injectableFactory->create($className);
                if ($handler) {
                    $this->emailNotificationEntityHandlerHash[$entityType] = $handler;
                }
            }
        }

        return $this->emailNotificationEntityHandlerHash[$entityType];
    }

    protected function processNotificationNotePost($note, $user)
    {
        $parentId = $note->get('parentId');
        $parentType = $note->get('parentType');

        $emailAddress = $user->get('emailAddress');
        if (!$emailAddress) return;

        $data = [];

        $data['userName'] = $note->get('createdByName');

        $post = $note->get('post') ?? '';
        $post = \Michelf\Markdown::defaultTransform($post);
        $data['post'] = $post;

        if ($parentId && $parentType) {
            $parent = $this->entityManager->getEntity($parentType, $parentId);
            if (!$parent) return;

            $data['url'] = $this->getSiteUrl($user) . '/#' . $parentType . '/view/' . $parentId;
            $data['parentName'] = $parent->get('name');
            $data['parentType'] = $parentType;
            $data['parentId'] = $parentId;

            $data['name'] = $data['parentName'];

            $data['entityType'] = $this->language->translate($data['parentType'], 'scopeNames');
            $data['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($data['entityType']);

            $subjectTpl = $this->templateFileManager->getTemplate('notePost', 'subject', $parentType);
            $bodyTpl = $this->templateFileManager->getTemplate('notePost', 'body', $parentType);

            $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

            $subject = $this->getHtmlizer()->render($note, $subjectTpl, 'note-post-email-subject-' . $parentType, $data, true);
            $body = $this->getHtmlizer()->render($note, $bodyTpl, 'note-post-email-body-' . $parentType, $data, true);
        } else {
            $data['url'] = $this->getSiteUrl($user) . '/#Notification';

            $subjectTpl = $this->templateFileManager->getTemplate('notePostNoParent', 'subject');
            $bodyTpl = $this->templateFileManager->getTemplate('notePostNoParent', 'body');

            $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

            $subject = $this->getHtmlizer()->render($note, $subjectTpl, 'note-post-email-subject', $data, true);
            $body = $this->getHtmlizer()->render($note, $bodyTpl, 'note-post-email-body', $data, true);
        }

        $email = $this->entityManager->getEntity('Email');

        $email->set([
            'subject' => $subject,
            'body' => $body,
            'isHtml' => true,
            'to' => $emailAddress,
            'isSystem' => true
        ]);
        if ($parentId && $parentType) {
            $email->set([
                'parentId' => $parentId,
                'parentType' => $parentType
            ]);
        }

        $smtpParams = null;
        if ($parentId && $parentType && !empty($parent)) {
            $handler = $this->getEmailNotificationEntityHandler($parentType);
            if ($handler) {
                $prepareEmailMethodName = 'prepareEmail';
                if (method_exists($handler, $prepareEmailMethodName)) {
                    $handler->$prepareEmailMethodName('notePost', $parent, $email, $user);
                }
                $getSmtpParamsMethodName = 'getSmtpParams';
                if (method_exists($handler, $getSmtpParamsMethodName)) {
                    $smtpParams = $handler->$getSmtpParamsMethodName('notePost', $parent, $user);
                }
            }
        }

        try {
            if ($smtpParams) {
                $this->mailSender->setParams($smtpParams);
            }
            $this->mailSender->send($email);
        } catch (\Exception $e) {
            $GLOBALS['log']->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    protected function getSiteUrl(\Espo\Entities\User $user)
    {
        if ($user->isPortal()) {
            if (!array_key_exists($user->id, $this->userIdPortalCacheMap)) {
                $this->userIdPortalCacheMap[$user->id] = null;

                $portalIdList = $user->getLinkMultipleIdList('portals');
                $defaultPortalId = $this->config->get('defaultPortalId');

                $portalId = null;

                if (in_array($defaultPortalId, $portalIdList)) {
                    $portalId = $defaultPortalId;
                } else if (count($portalIdList)) {
                    $portalId = $portalIdList[0];
                }

                if ($portalId) {
                    $portal = $this->entityManager->getEntity('Portal', $portalId);
                    $this->entityManager->getRepository('Portal')->loadUrlField($portal);
                    $this->userIdPortalCacheMap[$user->id] = $portal;
                }
            } else {
                $portal = $this->userIdPortalCacheMap[$user->id];
            }

            if ($portal) {
                $url = $portal->get('url');
                $url = rtrim($url, '/');
                return $url;
            }
        }
        return $this->config->getSiteUrl();
    }

    protected function processNotificationNoteStatus($note, $user)
    {
        $parentId = $note->get('parentId');
        $parentType = $note->get('parentType');

        $emailAddress = $user->get('emailAddress');
        if (!$emailAddress) return;

        $data = [];

        if (!$parentId || !$parentType) return;

        $parent = $this->entityManager->getEntity($parentType, $parentId);
        if (!$parent) return;

        $data['url'] = $this->getSiteUrl($user) . '/#' . $parentType . '/view/' . $parentId;
        $data['parentName'] = $parent->get('name');
        $data['parentType'] = $parentType;
        $data['parentId'] = $parentId;

        $data['name'] = $data['parentName'];

        $data['entityType'] = $this->language->translate($data['parentType'], 'scopeNames');
        $data['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($data['entityType']);

        $noteData = $note->get('data');
        if (empty($noteData)) return;


        $data['value'] = $noteData->value;
        $data['field'] = $noteData->field;
        $data['valueTranslated'] = $this->language->translateOption($data['value'], $data['field'], $parentType);
        $data['fieldTranslated'] = $this->language->translate($data['field'], 'fields', $parentType);
        $data['fieldTranslatedLowerCase'] = Util::mbLowerCaseFirst($data['fieldTranslated']);

        $data['userName'] = $note->get('createdByName');

        $subjectTpl = $this->templateFileManager->getTemplate('noteStatus', 'subject', $parentType);
        $bodyTpl = $this->templateFileManager->getTemplate('noteStatus', 'body', $parentType);
        $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

        $subject = $this->getHtmlizer()->render($note, $subjectTpl, 'note-status-email-subject', $data, true);
        $body = $this->getHtmlizer()->render($note, $bodyTpl, 'note-status-email-body', $data, true);

        $email = $this->entityManager->getEntity('Email');

        $email->set([
            'subject' => $subject,
            'body' => $body,
            'isHtml' => true,
            'to' => $emailAddress,
            'isSystem' => true,
            'parentId' => $parentId,
            'parentType' => $parentType
        ]);

        try {
            $this->mailSender->send($email);
        } catch (\Exception $e) {
            $GLOBALS['log']->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    protected function processNotificationNoteEmailReceived($note, $user)
    {
        $parentId = $note->get('parentId');
        $parentType = $note->get('parentType');

        $allowedEntityTypeList = $this->config->get('streamEmailNotificationsEmailReceivedEntityTypeList');
        if (
            is_array($allowedEntityTypeList)
            &&
            !in_array($parentType, $allowedEntityTypeList)
        ) return;

        $emailAddress = $user->get('emailAddress');
        if (!$emailAddress) return;

        $noteData = $note->get('data');

        if (!($noteData instanceof \StdClass)) return;

        if (!isset($noteData->emailId)) return;
        $email = $this->entityManager->getEntity('Email', $noteData->emailId);
        if (!$email) return;

        $emailRepository = $this->entityManager->getRepository('Email');
        $eaList = $user->get('emailAddresses');
        foreach ($eaList as $ea) {
            if (
                $emailRepository->isRelated($email, 'toEmailAddresses', $ea)
                ||
                $emailRepository->isRelated($email, 'ccEmailAddresses', $ea)
            ) return;
        }

        $data = [];

        $data['fromName'] = '';
        if (isset($noteData->personEntityName)) {
            $data['fromName'] = $noteData->personEntityName;
        } else if (isset($noteData->fromString)) {
            $data['fromName'] = $noteData->fromString;
        }

        $data['subject'] = '';
        if (isset($noteData->emailName)) {
            $data['subject'] = $noteData->emailName;
        }

        $data['post'] = nl2br($note->get('post'));

        if (!$parentId || !$parentType) return;

        $parent = $this->entityManager->getEntity($parentType, $parentId);
        if (!$parent) return;

        $data['url'] = $this->getSiteUrl($user) . '/#' . $parentType . '/view/' . $parentId;
        $data['parentName'] = $parent->get('name');
        $data['parentType'] = $parentType;
        $data['parentId'] = $parentId;

        $data['name'] = $data['parentName'];

        $data['entityType'] = $this->language->translate($data['parentType'], 'scopeNames');
        $data['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($data['entityType']);

        $subjectTpl = $this->templateFileManager->getTemplate('noteEmailReceived', 'subject', $parentType);
        $bodyTpl = $this->templateFileManager->getTemplate('noteEmailReceived', 'body', $parentType);

        $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

        $subject = $this->getHtmlizer()->render($note, $subjectTpl, 'note-email-received-email-subject-' . $parentType, $data, true);
        $body = $this->getHtmlizer()->render($note, $bodyTpl, 'note-email-received-email-body-' . $parentType, $data, true);

        $email = $this->entityManager->getEntity('Email');

        $email->set([
            'subject' => $subject,
            'body' => $body,
            'isHtml' => true,
            'to' => $emailAddress,
            'isSystem' => true
        ]);

        $email->set([
            'parentId' => $parentId,
            'parentType' => $parentType
        ]);

        try {
            $this->mailSender->send($email);
        } catch (\Exception $e) {
            $GLOBALS['log']->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    protected function loadParentNameFields(Entity $entity)
    {
        $fieldDefs = $this->metadata->get(['entityDefs', $entity->getEntityType(), 'fields'], []);
        foreach ($fieldDefs as $field => $defs) {
            if (isset($defs['type']) && $defs['type'] == 'linkParent') {
                $entity->loadParentNameField($field);
            }
        }
    }
}
