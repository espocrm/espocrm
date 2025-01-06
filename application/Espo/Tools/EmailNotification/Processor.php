<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\EmailNotification;

use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Core\Notification\EmailNotificationHandler;
use Espo\Core\Mail\SenderParams;
use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Entities\Note;
use Espo\ORM\Collection;
use Espo\Repositories\Portal as PortalRepository;
use Espo\Entities\Email;
use Espo\Entities\Notification;
use Espo\Entities\Portal;
use Espo\Entities\Preferences;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\SelectBuilder as SelectBuilder;
use Espo\Core\Htmlizer\Htmlizer;
use Espo\Core\Htmlizer\HtmlizerFactory as HtmlizerFactory;
use Espo\Core\InjectableFactory;
use Espo\Core\Mail\EmailSender as EmailSender;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\TemplateFileManager;
use Espo\Core\Utils\Util;
use Espo\Tools\Stream\NoteAccessControl;

use Michelf\Markdown;

use Exception;
use DateTime;
use Throwable;

class Processor
{
    private const HOURS_THRESHOLD = 5;
    private const PROCESS_MAX_COUNT = 200;

    private ?Htmlizer $htmlizer = null;

    /** @var array<string,?EmailNotificationHandler>  */
    private $emailNotificationEntityHandlerHash = [];
    /** @var array<string,?Portal> */
    private $userIdPortalCacheMap = [];

    public function __construct(
        private EntityManager $entityManager,
        private HtmlizerFactory $htmlizerFactory,
        private EmailSender $emailSender,
        private Config $config,
        private InjectableFactory $injectableFactory,
        private TemplateFileManager $templateFileManager,
        private Metadata $metadata,
        private Language $language,
        private Log $log,
        private NoteAccessControl $noteAccessControl,
        private ApplicationConfig $applicationConfig,
    ) {}

    public function process(): void
    {
        $mentionEmailNotifications = $this->config->get('mentionEmailNotifications');
        $streamEmailNotifications = $this->config->get('streamEmailNotifications');
        $portalStreamEmailNotifications = $this->config->get('portalStreamEmailNotifications');

        $typeList = [];

        if ($mentionEmailNotifications) {
            $typeList[] = Notification::TYPE_MENTION_IN_POST;
        }

        if ($streamEmailNotifications || $portalStreamEmailNotifications) {
            $typeList[] = Notification::TYPE_NOTE;
        }

        if (empty($typeList)) {
            return;
        }

        $fromDt = new DateTime();
        $fromDt->modify('-' . self::HOURS_THRESHOLD . ' hours');

        $where = [
            'createdAt>' => $fromDt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
            'read' => false,
            'emailIsProcessed' => false,
        ];

        $delay = $this->config->get('emailNotificationsDelay');

        if ($delay) {
            $delayDt = new DateTime();
            $delayDt->modify('-' . $delay . ' seconds');

            $where[] = ['createdAt<' => $delayDt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT)];
        }

        $queryList = [];

        foreach ($typeList as $type) {
            $itemBuilder = null;

            if ($type === Notification::TYPE_MENTION_IN_POST) {
                $itemBuilder = $this->getNotificationQueryBuilderMentionInPost();
            }

            if ($type === Notification::TYPE_NOTE) {
                $itemBuilder = $this->getNotificationQueryBuilderNote();
            }

            if (!$itemBuilder) {
                continue;
            }

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

        $sql = $this->entityManager
            ->getQueryComposer()
            ->compose($unionQuery);

        /** @var Collection<Notification> $notifications */
        $notifications = $this->entityManager
            ->getRDBRepository(Notification::ENTITY_TYPE)
            ->findBySql($sql);

        foreach ($notifications as $notification) {
            $notification->set('emailIsProcessed', true);

            $type = $notification->getType();

            try {
                if ($type === Notification::TYPE_NOTE) {
                    $this->processNotificationNote($notification);
                } else if ($type === Notification::TYPE_MENTION_IN_POST) {
                    $this->processNotificationMentionInPost($notification);
                } else {
                    // For bc.
                    $methodName = 'processNotification' . ucfirst($type ?? 'Dummy');

                    if (method_exists($this, $methodName)) {
                        $this->$methodName($notification);
                    }
                }
            } catch (Throwable $e) {
                $this->log->error("Email notification: {$e->getMessage()}", ['exception' => $e]);
            }

            $this->entityManager->saveEntity($notification);
        }
    }

    protected function getNotificationQueryBuilderMentionInPost(): SelectBuilder
    {
        return $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from(Notification::ENTITY_TYPE)
            ->where([
                'type' => Notification::TYPE_MENTION_IN_POST,
            ]);
    }

    protected function getNotificationQueryBuilderNote(): SelectBuilder
    {
        $noteNotificationTypeList = $this->config->get('streamEmailNotificationsTypeList', []);

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from(Notification::ENTITY_TYPE)
            ->join(Note::ENTITY_TYPE, 'note', ['note.id:' => 'relatedId'])
            ->where([
                'type' => Notification::TYPE_NOTE,
                'relatedType' => Note::ENTITY_TYPE,
                'note.type' => $noteNotificationTypeList,
            ]);

        $entityList = $this->config->get('streamEmailNotificationsEntityList');

        if (empty($entityList)) {
            $builder->where([
                'relatedParentType' => null,
            ]);
        } else {
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
                'user.type!=' => User::TYPE_PORTAL,
            ]);
        } else if (!$forInternal && $forPortal) {
            $builder->where([
                'user.type' => User::TYPE_PORTAL,
            ]);
        }

        return $builder;
    }

    protected function processNotificationMentionInPost(Notification $notification): void
    {
        if (!$notification->get('userId')) {
            return;
        }

        $userId = $notification->get('userId');

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            return;
        }

        $emailAddress = $user->get('emailAddress');

        if (!$emailAddress) {
            return;
        }

        $preferences = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $userId);

        if (!$preferences) {
            return;
        }

        if (!$preferences->get('receiveMentionEmailNotifications')) {
            return;
        }

        if (!$notification->getRelated() || $notification->getRelated()->getEntityType() !== Note::ENTITY_TYPE) {
            return;
        }

        /** @var ?Note $note */
        $note = $this->entityManager->getEntityById(Note::ENTITY_TYPE, $notification->getRelated()->getId());

        if (!$note) {
            return;
        }

        $parent = null;

        $parentId = $note->getParentId();
        $parentType = $note->getParentType();

        $data = [];

        if ($parentId && $parentType) {
            $parent = $this->entityManager->getEntityById($parentType, $parentId);

            if (!$parent) {
                return;
            }

            $data['url'] = "{$this->getSiteUrl($user)}/#$parentType/view/$parentId";
            $data['parentName'] = $parent->get(Field::NAME);
            $data['parentType'] = $parentType;
            $data['parentId'] = $parentId;
        } else {
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

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $email
            ->setSubject($subject)
            ->setBody($body)
            ->setIsHtml()
            ->addToAddress($emailAddress);
        $email->set('isSystem', true);

        if ($parentId && $parentType) {
            $email->setParent(LinkParent::create($parentType, $parentId));
        }

        $senderParams = SenderParams::create();

        if ($parent && $parentType) {
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
        } catch (Exception $e) {
            $this->log->error("Email notification: {$e->getMessage()}", ['exception' => $e]);
        }
    }

    protected function processNotificationNote(Notification $notification): void
    {
        if (!$notification->getRelated()) {
            return;
        }

        if ($notification->getRelated()->getEntityType() !== Note::ENTITY_TYPE) {
            return;
        }

        /** @var ?Note $note */
        $note = $this->entityManager->getEntityById(Note::ENTITY_TYPE, $notification->getRelated()->getId());

        if (!$note) {
            return;
        }

        $noteNotificationTypeList = $this->config->get('streamEmailNotificationsTypeList', []);

        if (!in_array($note->getType(), $noteNotificationTypeList)) {
            return;
        }

        if (!$notification->getUserId()) {
            return;
        }

        $userId = $notification->getUserId();

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            return;
        }

        $emailAddress = $user->getEmailAddress();

        if (!$emailAddress) {
            return;
        }

        $preferences = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $userId);

        if (!$preferences) {
            return;
        }

        if (!$preferences->get('receiveStreamEmailNotifications')) {
            return;
        }

        $type = $note->getType();

        if ($type === Note::TYPE_POST) {
            $this->processNotificationNotePost($note, $user);

            return;
        }

        if ($type === Note::TYPE_STATUS) {
            $this->processNotificationNoteStatus($note, $user);

            return;
        }

        if ($type === Note::TYPE_EMAIL_RECEIVED) {
            $this->processNotificationNoteEmailReceived($note, $user);

            return;
        }

        /** For bc. */
        $methodName = 'processNotificationNote' . $type;

        if (method_exists($this, $methodName)) {
            $this->$methodName($note, $user);
        }
    }

    protected function getHandler(string $type, string $entityType): ?EmailNotificationHandler
    {
        $key = $type . '-' . $entityType;

        if (!array_key_exists($key, $this->emailNotificationEntityHandlerHash)) {
            $this->emailNotificationEntityHandlerHash[$key] = null;

            /** @var ?class-string<EmailNotificationHandler> $className */
            $className = $this->metadata
                ->get(['notificationDefs', $entityType, 'emailNotificationHandlerClassNameMap', $type]);

            if ($className && class_exists($className)) {
                $handler = $this->injectableFactory->create($className);

                $this->emailNotificationEntityHandlerHash[$key] = $handler;
            }
        }

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $this->emailNotificationEntityHandlerHash[$key];
    }

    protected function processNotificationNotePost(Note $note, User $user): void
    {
        $parentId = $note->getParentId();
        $parentType = $note->getParentType();

        $emailAddress = $user->getEmailAddress();

        if (!$emailAddress) {
            return;
        }

        $data = [];

        $data['userName'] = $note->get('createdByName');

        $post = Markdown::defaultTransform($note->getPost() ?? '');

        $data['post'] = $post;

        $parent = null;

        if ($parentId && $parentType) {
            $parent = $this->entityManager->getEntityById($parentType, $parentId);

            if (!$parent) {
                return;
            }

            $data['url'] = "{$this->getSiteUrl($user)}/#$parentType/view/$parentId";
            $data['parentName'] = $parent->get(Field::NAME);
            $data['parentType'] = $parentType;
            $data['parentId'] = $parentId;

            $data['name'] = $data['parentName'];

            $data['entityType'] = $this->language->translateLabel($parentType, 'scopeNames');
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
        } else {
            $data['url'] = "{$this->getSiteUrl($user)}/#Notification";

            $subjectTpl = $this->templateFileManager->getTemplate('notePostNoParent', 'subject');
            $bodyTpl = $this->templateFileManager->getTemplate('notePostNoParent', 'body');

            $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

            $subject = $this->getHtmlizer()->render($note, $subjectTpl, 'note-post-email-subject', $data, true);
            $body = $this->getHtmlizer()->render($note, $bodyTpl, 'note-post-email-body', $data, true);
        }

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $email
            ->setSubject($subject)
            ->setBody($body)
            ->setIsHtml()
            ->addToAddress($emailAddress);

        $email->set('isSystem', true);

        if ($parentId && $parentType) {
            $email->setParent(LinkParent::create($parentType, $parentId));
        }

        $senderParams = SenderParams::create();

        if ($parent) {
            $handler = $this->getHandler('notePost', $parent->getEntityType());

            if ($handler) {
                $handler->prepareEmail($email, $parent, $user);

                $senderParams = $handler->getSenderParams($parent, $user) ?? $senderParams;
            }
        }

        try {
            $this->emailSender
                ->withParams($senderParams)
                ->send($email);
        } catch (Exception $e) {
            $this->log->error("Email notification: {$e->getMessage()}", ['exception' => $e]);
        }
    }

    private function getSiteUrl(User $user): string
    {
        $portal = null;

        if (!$user->isPortal()) {
            return $this->applicationConfig->getSiteUrl();
        }

        if (!array_key_exists($user->getId(), $this->userIdPortalCacheMap)) {
            $this->userIdPortalCacheMap[$user->getId()] = null;

            $portalIdList = $user->getLinkMultipleIdList('portals');

            $defaultPortalId = $this->config->get('defaultPortalId');

            $portalId = null;

            if (in_array($defaultPortalId, $portalIdList)) {
                $portalId = $defaultPortalId;
            } else if (count($portalIdList)) {
                $portalId = $portalIdList[0];
            }

            if ($portalId) {
                /** @var ?Portal $portal */
                $portal = $this->entityManager->getEntityById(Portal::ENTITY_TYPE, $portalId);
            }

            if ($portal) {
                $this->getPortalRepository()->loadUrlField($portal);

                $this->userIdPortalCacheMap[$user->getId()] = $portal;
            }
        } else {
            $portal = $this->userIdPortalCacheMap[$user->getId()];
        }

        if ($portal) {
            return rtrim($portal->get('url'), '/');
        }

        return $this->applicationConfig->getSiteUrl();
    }

    protected function processNotificationNoteStatus(Note $note, User $user): void
    {
        $this->noteAccessControl->apply($note, $user);

        $parentId = $note->getParentId();
        $parentType = $note->getParentType();

        $emailAddress = $user->getEmailAddress();

        if (!$emailAddress) {
            return;
        }

        $data = [];

        if (!$parentId || !$parentType) {
            return;
        }

        $parent = $this->entityManager->getEntityById($parentType, $parentId);

        if (!$parent) {
            return;
        }

        $note->loadParentNameField('superParent');

        $data['url'] = "{$this->getSiteUrl($user)}/#$parentType/view/$parentId";
        $data['parentName'] = $parent->get(Field::NAME);
        $data['parentType'] = $parentType;
        $data['parentId'] = $parentId;
        $data['superParentName'] = $note->get('superParentName');
        $data['superParentType'] = $note->getSuperParentType();
        $data['superParentId'] = $note->getSuperParentId();
        $data['name'] = $data['parentName'];
        $data['entityType'] = $this->language->translateLabel($parentType, 'scopeNames');
        $data['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($data['entityType']);

        $noteData = $note->getData();

        if (!isset($noteData->value) || !isset($note->field)) {
            return;
        }

        $data['value'] = $noteData->value;
        $data['field'] = $field = $noteData->field;

        if (!is_string($field)) {
            return;
        }

        $data['valueTranslated'] = $this->language->translateOption($data['value'], $data['field'], $parentType);
        $data['fieldTranslated'] = $this->language->translateLabel($field, 'fields', $parentType);
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

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $email
            ->setSubject($subject)
            ->setBody($body)
            ->setIsHtml()
            ->addToAddress($emailAddress)
            ->setParent(LinkParent::create($parentType, $parentId));

        $email->set('isSystem', true);

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
        } catch (Exception $e) {
            $this->log->error("Email notification: {$e->getMessage()}", ['exception' => $e]);
        }
    }

    protected function processNotificationNoteEmailReceived(Note $note, User $user): void
    {
        $parentId = $note->get('parentId');
        $parentType = $note->getParentType();

        $allowedEntityTypeList = $this->config->get('streamEmailNotificationsEmailReceivedEntityTypeList');

        if (
            is_array($allowedEntityTypeList) &&
            !in_array($parentType, $allowedEntityTypeList)
        ) {
            return;
        }

        $emailAddress = $user->getEmailAddress();

        if (!$emailAddress) {
            return;
        }

        $noteData = $note->getData();

        if (!isset($noteData->emailId)) {
            return;
        }

        $emailSubject = $this->entityManager->getEntityById(Email::ENTITY_TYPE, $noteData->emailId);

        if (!$emailSubject) {
            return;
        }

        $emailAddresses = $this->entityManager
            ->getRelation($user, 'emailAddresses')
            ->find();

        foreach ($emailAddresses as $ea) {
            if (
                $this->entityManager->getRelation($emailSubject, 'toEmailAddresses')->isRelated($ea) ||
                $this->entityManager->getRelation($emailSubject, 'ccEmailAddresses')->isRelated($ea)
            ) {
                return;
            }
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

        if (!$parentId || !$parentType) {
            return;
        }

        $parent = $this->entityManager->getEntityById($parentType, $parentId);

        if (!$parent) {
            return;
        }

        $data['url'] = "{$this->getSiteUrl($user)}/#$parentType/view/$parentId";
        $data['parentName'] = $parent->get(Field::NAME);
        $data['parentType'] = $parentType;
        $data['parentId'] = $parentId;

        $data['name'] = $data['parentName'];

        $data['entityType'] = $this->language->translateLabel($parentType, 'scopeNames');
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

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $email
            ->setSubject($subject)
            ->setBody($body)
            ->setIsHtml()
            ->addToAddress($emailAddress)
            ->setParent(LinkParent::create($parentType, $parentId));

        $email->set('isSystem', true);

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
        } catch (Exception $e) {
            $this->log->error("Email notification: {$e->getMessage()}", ['exception' => $e]);
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
        return $this->entityManager->getRepository(Portal::ENTITY_TYPE);
    }
}
