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

namespace Espo\Tools\EmailNotification;

use Espo\{
    ORM\Entity,
    ORM\EntityManager,
    ORM\QueryParams\SelectBuilder as SelectBuilder,
    Entities\User as UserEntity,
};

use Espo\Core\{
    Htmlizer\Factory as HtmlizerFactory,
    Htmlizer\Htmlizer,
    Utils\Config,
    Utils\Metadata,
    Utils\Language,
    Select\SelectBuilderFactory,
    InjectableFactory,
    Utils\TemplateFileManager,
    Mail\EmailSender as EmailSender,
    Utils\Util,
};

use Michelf\Markdown;

use Exception;
use DateTime;
use StdClass;

class Processor
{
    const HOURS_THERSHOLD = 5;

    const PROCESS_MAX_COUNT = 200;

    protected $htmlizer;

    protected $entityManager;

    protected $htmlizerFactory;

    protected $emailSender;

    protected $config;

    protected $selectBuilderFactory;

    protected $injectableFactory;

    protected $templateFileManager;

    protected $metadata;

    public function __construct(
        EntityManager $entityManager,
        HtmlizerFactory $htmlizerFactory,
        EmailSender $emailSender,
        Config $config,
        SelectBuilderFactory $selectBuilderFactory,
        InjectableFactory $injectableFactory,
        TemplateFileManager $templateFileManager,
        Metadata $metadata,
        Language $language
    ) {
        $this->entityManager = $entityManager;
        $this->htmlizerFactory = $htmlizerFactory;
        $this->emailSender = $emailSender;
        $this->config = $config;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->injectableFactory = $injectableFactory;
        $this->templateFileManager = $templateFileManager;
        $this->metadata = $metadata;
        $this->language = $language;
    }

    protected $emailNotificationEntityHandlerHash = [];

    protected $userIdPortalCacheMap = [];

    public function process() : void
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

        if (empty($typeList)) {
            return;
        }

        $fromDt = new DateTime();

        $fromDt->modify('-' . self::HOURS_THERSHOLD . ' hours');

        $where = [
            'createdAt>' => $fromDt->format('Y-m-d H:i:s'),
            'read' => false,
            'emailIsProcessed' => false,
        ];

        $delay = $this->config->get('emailNotificationsDelay');

        if ($delay) {
            $delayDt = new DateTime();

            $delayDt->modify('-' . $delay . ' seconds');

            $where[] = ['createdAt<' => $delayDt->format('Y-m-d H:i:s')];
        }

        $queryList = [];

        foreach ($typeList as $type) {
            $methodName = 'getNotificationQueryBuilder' . $type;

            $itemBuilder = $this->$methodName();

            $itemBuilder->where($where);

            $queryList[] = $itemBuilder->build();
        }

        $builder = $this->entityManager->getQueryBuilder()
            ->union()
            ->order('number')
            ->limit(0, self::PROCESS_MAX_COUNT);

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $unionQuery = $builder->build();

        $sql = $this->entityManager->getQueryComposer()->compose($unionQuery);

        $notificationList = $this->entityManager->getRepository('Notification')->findBySql($sql);

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

    protected function getNotificationQueryBuilderMentionInPost() : SelectBuilder
    {
        return $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from('Notification')
            ->where([
                'type' => 'MentionInPost',
            ]);
    }

    protected function getNotificationQueryBuilderNote() : SelectBuilder
    {
        $noteNotificationTypeList = $this->config->get('streamEmailNotificationsTypeList', []);

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from('Notification')
            ->join('Note', 'note', ['note.id:' => 'relatedId'])
            ->where([
                'type' => 'Note',
                'relatedType' => 'Note',
                'note.type' => $noteNotificationTypeList,
            ]);

        $entityList = $this->config->get('streamEmailNotificationsEntityList');

        if (empty($entityList)) {
            $builder->where([
                'relatedParentType' => null,
            ]);
        }
        else {
            $builder->where([
                'OR' => [
                    [
                        'relatedParentType' => $entityList,
                    ],
                    [
                        'relatedParentType' => null,
                    ],
                ],
            ]);
        }

        $forInternal = $this->config->get('streamEmailNotifications');
        $forPortal = $this->config->get('portalStreamEmailNotifications');

        if ($forInternal && !$forPortal) {
            $builder->where([
                'user.type!=' => 'portal',
            ]);
        }
        else if (!$forInternal && $forPortal) {
            $builder->where([
                'user.type' => 'portal',
            ]);
        }

        return $builder;
    }

    protected function processNotificationMentionInPost(Entity $notification) : void
    {
        if (!$notification->get('userId')) {
            return;
        }

        $userId = $notification->get('userId');

        $user = $this->entityManager->getEntity('User', $userId);

        if (!$user) {
            return;
        }

        $emailAddress = $user->get('emailAddress');

        if (!$emailAddress) {
            return;
        }

        $preferences = $this->entityManager->getEntity('Preferences', $userId);

        if (!$preferences) {
            return;
        }

        if (!$preferences->get('receiveMentionEmailNotifications')) {
            return;
        }

        if ($notification->get('relatedType') !== 'Note' || !$notification->get('relatedId')) {
            return;
        }

        $note = $this->entityManager->getEntity('Note', $notification->get('relatedId'));

        if (!$note) {
            return;
        }

        $parentId = $note->get('parentId');
        $parentType = $note->get('parentType');

        $data = [];

        if ($parentId && $parentType) {
            $parent = $this->entityManager->getEntity($parentType, $parentId);

            if (!$parent) {
                return;
            }

            $data['url'] = $this->getSiteUrl($user) . '/#' . $parentType . '/view/' . $parentId;
            $data['parentName'] = $parent->get('name');
            $data['parentType'] = $parentType;
            $data['parentId'] = $parentId;
        }
        else {
            $data['url'] = $this->getSiteUrl($user) . '/#Notification';
        }

        $data['userName'] = $note->get('createdByName');

        $post = $note->get('post') ?? ''
            ;
        $post = Markdown::defaultTransform($post);

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
            'isSystem' => true,
        ]);

        if ($parentId && $parentType) {
            $email->set([
                'parentId' => $parentId,
                'parentType' => $parentType
            ]);
        }

        try {
            $this->emailSender->send($email);
        }
        catch (Exception $e) {
            $GLOBALS['log']->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    protected function processNotificationNote(Entity $notification) : void
    {
        if ($notification->get('relatedType') !== 'Note') {
            return;
        }

        if (!$notification->get('relatedId')) {
            return;
        }

        $note = $this->entityManager->getEntity('Note', $notification->get('relatedId'));

        if (!$note) {
            return;
        }

        $noteNotificationTypeList = $this->config->get('streamEmailNotificationsTypeList', []);

        if (!in_array($note->get('type'), $noteNotificationTypeList)) {
            return;
        }

        if (!$notification->get('userId')) {
            return;
        }

        $userId = $notification->get('userId');
        $user = $this->entityManager->getEntity('User', $userId);

        if (!$user) {
            return;
        }

        $emailAddress = $user->get('emailAddress');

        if (!$emailAddress) {
            return;
        }

        $preferences = $this->entityManager->getEntity('Preferences', $userId);

        if (!$preferences) {
            return;
        }

        if (!$preferences->get('receiveStreamEmailNotifications')) {
            return;
        }

        $methodName = 'processNotificationNote' . $note->get('type');

        if (!method_exists($this, $methodName)) {
            return;
        }

        $this->$methodName($note, $user);
    }

    protected function getEmailNotificationEntityHandler(string $entityType) : ?object
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

    protected function processNotificationNotePost($note, $user) : void
    {
        $parentId = $note->get('parentId');
        $parentType = $note->get('parentType');

        $emailAddress = $user->get('emailAddress');

        if (!$emailAddress) {
            return;
        }

        $data = [];

        $data['userName'] = $note->get('createdByName');

        $post = $note->get('post') ?? '';

        $post = Markdown::defaultTransform($post);

        $data['post'] = $post;

        if ($parentId && $parentType) {
            $parent = $this->entityManager->getEntity($parentType, $parentId);

            if (!$parent) {
                return;
            }

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

            $subject = $this->getHtmlizer()->render(
                $note,
                $subjectTpl,
                'note-post-email-subject-' . $parentType,
                $data,
                true
            );

            $body = $this->getHtmlizer()->render(
                $note,
                $bodyTpl,
                'note-post-email-body-' . $parentType,
                $data,
                true
            );
        }
        else {
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
            'isSystem' => true,
        ]);

        if ($parentId && $parentType) {
            $email->set([
                'parentId' => $parentId,
                'parentType' => $parentType,
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

        $sender = $this->emailSender->create();

        try {
            if ($smtpParams) {
                $sender->withSmtpParams($smtpParams);
            }

            $sender->send($email);
        }
        catch (Exception $e) {
            $GLOBALS['log']->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    protected function getSiteUrl(UserEntity $user) : string
    {
        $portal = null;

        if ($user->isPortal()) {
            if (!array_key_exists($user->id, $this->userIdPortalCacheMap)) {
                $this->userIdPortalCacheMap[$user->id] = null;

                $portalIdList = $user->getLinkMultipleIdList('portals');

                $defaultPortalId = $this->config->get('defaultPortalId');

                $portalId = null;

                if (in_array($defaultPortalId, $portalIdList)) {
                    $portalId = $defaultPortalId;
                }
                else if (count($portalIdList)) {
                    $portalId = $portalIdList[0];
                }

                if ($portalId) {
                    $portal = $this->entityManager->getEntity('Portal', $portalId);

                    $this->entityManager->getRepository('Portal')->loadUrlField($portal);

                    $this->userIdPortalCacheMap[$user->id] = $portal;
                }
            }
            else {
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

    protected function processNotificationNoteStatus($note, $user) : void
    {
        $parentId = $note->get('parentId');
        $parentType = $note->get('parentType');

        $emailAddress = $user->get('emailAddress');

        if (!$emailAddress) {
            return;
        }

        $data = [];

        if (!$parentId || !$parentType) {
            return;
        }

        $parent = $this->entityManager->getEntity($parentType, $parentId);

        if (!$parent) {
            return;
        }

        $data['url'] = $this->getSiteUrl($user) . '/#' . $parentType . '/view/' . $parentId;
        $data['parentName'] = $parent->get('name');
        $data['parentType'] = $parentType;
        $data['parentId'] = $parentId;

        $data['name'] = $data['parentName'];

        $data['entityType'] = $this->language->translate($data['parentType'], 'scopeNames');
        $data['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($data['entityType']);

        $noteData = $note->get('data');

        if (empty($noteData)) {
            return;
        }

        $data['value'] = $noteData->value;
        $data['field'] = $noteData->field;
        $data['valueTranslated'] = $this->language->translateOption($data['value'], $data['field'], $parentType);
        $data['fieldTranslated'] = $this->language->translate($data['field'], 'fields', $parentType);
        $data['fieldTranslatedLowerCase'] = Util::mbLowerCaseFirst($data['fieldTranslated']);

        $data['userName'] = $note->get('createdByName');

        $subjectTpl = $this->templateFileManager->getTemplate('noteStatus', 'subject', $parentType);

        $bodyTpl = $this->templateFileManager->getTemplate('noteStatus', 'body', $parentType);

        $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

        $subject = $this->getHtmlizer()->render(
            $note, $subjectTpl, 'note-status-email-subject', $data, true
        );

        $body = $this->getHtmlizer()->render(
            $note, $bodyTpl, 'note-status-email-body', $data, true
        );

        $email = $this->entityManager->getEntity('Email');

        $email->set([
            'subject' => $subject,
            'body' => $body,
            'isHtml' => true,
            'to' => $emailAddress,
            'isSystem' => true,
            'parentId' => $parentId,
            'parentType' => $parentType,
        ]);

        try {
            $this->emailSender->send($email);
        }
        catch (Exception $e) {
            $GLOBALS['log']->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    protected function processNotificationNoteEmailReceived($note, $user) : void
    {
        $parentId = $note->get('parentId');
        $parentType = $note->get('parentType');

        $allowedEntityTypeList = $this->config->get('streamEmailNotificationsEmailReceivedEntityTypeList');

        if (
            is_array($allowedEntityTypeList)
            &&
            !in_array($parentType, $allowedEntityTypeList)
        ) {
            return;
        }

        $emailAddress = $user->get('emailAddress');

        if (!$emailAddress) {
            return;
        }

        $noteData = $note->get('data');

        if (!($noteData instanceof StdClass)) {
            return;
        }

        if (!isset($noteData->emailId)) {
            return;
        }

        $email = $this->entityManager->getEntity('Email', $noteData->emailId);

        if (!$email) {
            return;
        }

        $emailRepository = $this->entityManager->getRepository('Email');
        $eaList = $user->get('emailAddresses');

        foreach ($eaList as $ea) {
            if (
                $emailRepository->isRelated($email, 'toEmailAddresses', $ea)
                ||
                $emailRepository->isRelated($email, 'ccEmailAddresses', $ea)
            ) {
                return;
            }
        }

        $data = [];

        $data['fromName'] = '';

        if (isset($noteData->personEntityName)) {
            $data['fromName'] = $noteData->personEntityName;
        }
        else if (isset($noteData->fromString)) {
            $data['fromName'] = $noteData->fromString;
        }

        $data['subject'] = '';

        if (isset($noteData->emailName)) {
            $data['subject'] = $noteData->emailName;
        }

        $data['post'] = nl2br($note->get('post'));

        if (!$parentId || !$parentType) {
            return;
        }

        $parent = $this->entityManager->getEntity($parentType, $parentId);

        if (!$parent) {
            return;
        }

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

        $subject = $this->getHtmlizer()->render(
            $note, $subjectTpl, 'note-email-received-email-subject-' . $parentType, $data, true
        );

        $body = $this->getHtmlizer()->render(
            $note, $bodyTpl, 'note-email-received-email-body-' . $parentType, $data, true
        );

        $email = $this->entityManager->getEntity('Email');

        $email->set([
            'subject' => $subject,
            'body' => $body,
            'isHtml' => true,
            'to' => $emailAddress,
            'isSystem' => true,
        ]);

        $email->set([
            'parentId' => $parentId,
            'parentType' => $parentType
        ]);

        try {
            $this->emailSender->send($email);
        }
        catch (Exception $e) {
            $GLOBALS['log']->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    protected function getHtmlizer() : Htmlizer
    {
        if (!$this->htmlizer) {
            $this->htmlizer = $this->htmlizerFactory->create(true);
        }

        return $this->htmlizer;
    }
}
