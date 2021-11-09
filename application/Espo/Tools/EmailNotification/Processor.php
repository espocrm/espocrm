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

use Espo\Core\Notification\EmailNotificationHandler;
use Espo\Core\Mail\SenderParams;

use Espo\Repositories\Portal as PortalRepository;

use Espo\{
    ORM\Entity,
    ORM\EntityManager,
    ORM\Query\SelectBuilder as SelectBuilder,
    Entities\User as UserEntity,
    Entities\Note as NoteEntity,
};

use Espo\Core\{
    Htmlizer\HtmlizerFactory as HtmlizerFactory,
    Htmlizer\Htmlizer,
    Utils\Config,
    Utils\Metadata,
    Utils\Language,
    InjectableFactory,
    Utils\TemplateFileManager,
    Mail\EmailSender as EmailSender,
    Utils\Util,
    Utils\Log,
};

use Espo\Tools\Stream\NoteAccessControl;

use Michelf\Markdown;

use Exception;
use DateTime;
use stdClass;
use Throwable;

class Processor
{
    private const HOURS_THERSHOLD = 5;

    private const PROCESS_MAX_COUNT = 200;

    private $htmlizer;

    private $entityManager;

    private $htmlizerFactory;

    private $emailSender;

    private $config;

    private $injectableFactory;

    private $templateFileManager;

    private $metadata;

    private $language;

    private $log;

    private $noteAccessControl;

    public function __construct(
        EntityManager $entityManager,
        HtmlizerFactory $htmlizerFactory,
        EmailSender $emailSender,
        Config $config,
        InjectableFactory $injectableFactory,
        TemplateFileManager $templateFileManager,
        Metadata $metadata,
        Language $language,
        Log $log,
        NoteAccessControl $noteAccessControl
    ) {
        $this->entityManager = $entityManager;
        $this->htmlizerFactory = $htmlizerFactory;
        $this->emailSender = $emailSender;
        $this->config = $config;
        $this->injectableFactory = $injectableFactory;
        $this->templateFileManager = $templateFileManager;
        $this->metadata = $metadata;
        $this->language = $language;
        $this->log = $log;
        $this->noteAccessControl = $noteAccessControl;
    }

    private $emailNotificationEntityHandlerHash = [];

    private $userIdPortalCacheMap = [];

    public function process(): void
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

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->union()
            ->order('number')
            ->limit(0, self::PROCESS_MAX_COUNT);

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $unionQuery = $builder->build();

        $sql = $this->entityManager->getQueryComposer()->compose($unionQuery);

        $notificationList = $this->entityManager
            ->getRDBRepository('Notification')
            ->findBySql($sql);

        foreach ($notificationList as $notification) {
            $notification->set('emailIsProcessed', true);

            $type = $notification->get('type');

            $methodName = 'processNotification' . ucfirst($type);

            if (!method_exists($this, $methodName)) {
                continue;
            }

            try {
                $this->$methodName($notification);
            }
            catch (Throwable $e)
            {
                $this->log->error("Email Notification: " . $e->getMessage());
            }

            $this->entityManager->saveEntity($notification);
        }
    }

    protected function getNotificationQueryBuilderMentionInPost(): SelectBuilder
    {
        return $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from('Notification')
            ->where([
                'type' => 'MentionInPost',
            ]);
    }

    protected function getNotificationQueryBuilderNote(): SelectBuilder
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

    protected function processNotificationMentionInPost(Entity $notification): void
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

        $parent = null;

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

        $post = Markdown::defaultTransform(
            $note->get('post') ?? ''
        );

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
                'parentType' => $parentType,
            ]);
        }

        $senderParams = SenderParams::create();

        if ($parent) {
            $handler = $this->getHandler('mention', $parentType);

            if ($handler) {
                $handler->prepareEmail($email, $parent, $user);

                $senderParams = $handler->getSenderParams($parent, $user) ?? $senderParams;
            }
        }

        try {
            $this->emailSender
                ->withParams($senderParams)
                ->send($email);
        }
        catch (Exception $e) {
            $this->log->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    protected function processNotificationNote(Entity $notification): void
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

    protected function getHandler(string $type, string $entityType): ?EmailNotificationHandler
    {
        $key = $type . '-' . $entityType;

        if (!array_key_exists($key, $this->emailNotificationEntityHandlerHash)) {
            $this->emailNotificationEntityHandlerHash[$key] = null;

            $className = $this->metadata
                ->get(['notificationDefs', $entityType, 'emailNotificationHandlerClassNameMap', $type]);

            if ($className && class_exists($className)) {
                $handler = $this->injectableFactory->create($className);

                $this->emailNotificationEntityHandlerHash[$key] = $handler;
            }
        }

        return $this->emailNotificationEntityHandlerHash[$key];
    }

    protected function processNotificationNotePost(NoteEntity $note, UserEntity $user): void
    {
        $parentId = $note->get('parentId');
        $parentType = $note->get('parentType');

        $emailAddress = $user->get('emailAddress');

        if (!$emailAddress) {
            return;
        }

        $data = [];

        $data['userName'] = $note->get('createdByName');

        $post = Markdown::defaultTransform(
            $note->get('post') ?? ''
        );

        $data['post'] = $post;

        $parent = null;

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

        $senderParams = SenderParams::create();

        if ($parent) {
            $handler = $this->getHandler('notePost', $parentType);

            if ($handler) {
                $handler->prepareEmail($email, $parent, $user);

                $senderParams = $handler->getSenderParams($parent, $user) ?? $senderParams;
            }
        }

        try {
            $this->emailSender
                ->withParams($senderParams)
                ->send($email);
        }
        catch (Exception $e) {
            $this->log->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    private function getSiteUrl(UserEntity $user): string
    {
        $portal = null;

        if (!$user->isPortal()) {
            return $this->config->getSiteUrl();
        }

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

                $this->getPortalRepository()->loadUrlField($portal);

                $this->userIdPortalCacheMap[$user->id] = $portal;
            }
        }
        else {
            $portal = $this->userIdPortalCacheMap[$user->id];
        }

        if ($portal) {
            return rtrim($portal->get('url'), '/');
        }

        return $this->config->getSiteUrl();
    }

    protected function processNotificationNoteStatus(NoteEntity $note, UserEntity $user): void
    {
        $this->noteAccessControl->apply($note, $user);

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

        if ($noteData->value === null) {
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
            $note,
            $subjectTpl,
            'note-status-email-subject',
            $data,
            true
        );

        $body = $this->getHtmlizer()->render(
            $note,
            $bodyTpl,
            'note-status-email-body',
            $data,
            true
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

        $senderParams = SenderParams::create();

        $handler = $this->getHandler('status', $parentType);

        if ($handler) {
            $handler->prepareEmail($email, $parent, $user);

            $senderParams = $handler->getSenderParams($parent, $user) ?? $senderParams;
        }

        try {
            $this->emailSender
                ->withParams($senderParams)
                ->send($email);
        }
        catch (Exception $e) {
            $this->log->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    protected function processNotificationNoteEmailReceived(NoteEntity $note, UserEntity $user): void
    {
        $parentId = $note->get('parentId');
        $parentType = $note->get('parentType');

        $allowedEntityTypeList = $this->config->get('streamEmailNotificationsEmailReceivedEntityTypeList');

        if (
            is_array($allowedEntityTypeList) &&
            !in_array($parentType, $allowedEntityTypeList)
        ) {
            return;
        }

        $emailAddress = $user->get('emailAddress');

        if (!$emailAddress) {
            return;
        }

        $noteData = $note->get('data');

        if (!($noteData instanceof stdClass)) {
            return;
        }

        if (!isset($noteData->emailId)) {
            return;
        }

        $emailSubject = $this->entityManager->getEntity('Email', $noteData->emailId);

        if (!$emailSubject) {
            return;
        }

        $emailRepository = $this->entityManager->getRDBRepository('Email');
        $eaList = $user->get('emailAddresses');

        foreach ($eaList as $ea) {
            if (
                $emailRepository->isRelated($emailSubject, 'toEmailAddresses', $ea) ||
                $emailRepository->isRelated($emailSubject, 'ccEmailAddresses', $ea)
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
            $note,
            $subjectTpl,
            'note-email-received-email-subject-' . $parentType,
            $data,
            true
        );

        $body = $this->getHtmlizer()->render(
            $note,
            $bodyTpl,
            'note-email-received-email-body-' . $parentType,
            $data,
            true
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

        $senderParams = SenderParams::create();

        $handler = $this->getHandler('emailReceived', $parentType);

        if ($handler) {
            $handler->prepareEmail($email, $parent, $user);

            $senderParams = $handler->getSenderParams($parent, $user) ?? $senderParams;
        }

        try {
            $this->emailSender
                ->withParams($senderParams)
                ->send($email);
        }
        catch (Exception $e) {
            $this->log->error('EmailNotification: [' . $e->getCode() . '] ' .$e->getMessage());
        }
    }

    private function getHtmlizer(): Htmlizer
    {
        if (!$this->htmlizer) {
            $this->htmlizer = $this->htmlizerFactory->create(true);
        }

        return $this->htmlizer;
    }

    private function getPortalRepository(): PortalRepository
    {
        /** @var PortalRepository */
        return $this->entityManager->getRepository('Portal');
    }
}
