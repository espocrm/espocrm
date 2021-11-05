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

use Espo\Core\ORM\Entity;

use Espo\ORM\EntityManager;

use Espo\Entities\User;

use Espo\Core\{
    Htmlizer\HtmlizerFactory as HtmlizerFactory,
    Htmlizer\Htmlizer,
    Utils\Config,
    Utils\Metadata,
    Utils\Language,
    Utils\TemplateFileManager,
    Mail\EmailSender as EmailSender,
    Utils\Util,
    Utils\Log,
};

use Exception;
use LogicException;

class AssignmentProcessor
{
    private $htmlizer;

    private $entityManager;

    private $htmlizerFactory;

    private $emailSender;

    private $config;

    private $templateFileManager;

    private $metadata;

    private $language;

    private $log;

    public function __construct(
        EntityManager $entityManager,
        HtmlizerFactory $htmlizerFactory,
        EmailSender $emailSender,
        Config $config,
        TemplateFileManager $templateFileManager,
        Metadata $metadata,
        Language $language,
        Log $log
    ) {
        $this->entityManager = $entityManager;
        $this->htmlizerFactory = $htmlizerFactory;
        $this->emailSender = $emailSender;
        $this->config = $config;
        $this->templateFileManager = $templateFileManager;
        $this->metadata = $metadata;
        $this->language = $language;
        $this->log = $log;
    }

    public function process(AssignmentProcessorData $data): void
    {
        $userId = $data->getUserId();
        $assignerUserId = $data->getAssignerUserId();
        $entityId = $data->getEntityId();
        $entityType = $data->getEntityType();

        if (
            !$userId ||
            !$assignerUserId ||
            !$entityId ||
            !$entityType
        ) {
            throw new LogicException();
        }

        /** @var ?User $user */
        $user = $this->entityManager->getEntity('User', $userId);

        if (!$user) {
            return;
        }

        if ($user->isPortal()) {
            return;
        }

        $preferences = $this->entityManager->getEntity('Preferences', $userId);

        if (!$preferences) {
            return;
        }

        if (!$preferences->get('receiveAssignmentEmailNotifications')) {
            return;
        }

        $ignoreList = $preferences->get('assignmentEmailNotificationsIgnoreEntityTypeList') ?? [];

        if (in_array($entityType, $ignoreList)) {
            return;
        }

        $assignerUser = $this->entityManager->getEntity('User', $assignerUserId);

        $entity = $this->entityManager->getEntity($entityType, $entityId);

        if (!$entity) {
            return;
        }

        if (!$assignerUser) {
            return;
        }

        assert($entity instanceof Entity);

        $this->loadParentNameFields($entity);

        if (!$entity->hasLinkMultipleField('assignedUsers')) {
            if ($entity->get('assignedUserId') !== $userId) {
                return;
            }
        }

        $emailAddress = $user->get('emailAddress');

        if (!$emailAddress) {
            return;
        }

        $email = $this->entityManager->getEntity('Email');

        $subjectTpl = $this->templateFileManager->getTemplate('assignment', 'subject', $entity->getEntityType());
        $bodyTpl = $this->templateFileManager->getTemplate('assignment', 'body', $entity->getEntityType());

        $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

        $recordUrl = rtrim($this->config->get('siteUrl'), '/') .
            '/#' . $entity->getEntityType() . '/view/' . $entity->id;

        $templateData = [
            'userName' => $user->get('name'),
            'assignerUserName' => $assignerUser->get('name'),
            'recordUrl' => $recordUrl,
            'entityType' => $this->language->translate($entity->getEntityType(), 'scopeNames'),
        ];

        $templateData['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($templateData['entityType']);

        $subject = $this->getHtmlizer()->render(
            $entity,
            $subjectTpl,
            'assignment-email-subject-' . $entity->getEntityType(),
            $templateData,
            true
        );

        $body = $this->getHtmlizer()->render(
            $entity,
            $bodyTpl,
            'assignment-email-body-' . $entity->getEntityType(),
            $templateData,
            true
        );

        $email->set([
            'subject' => $subject,
            'body' => $body,
            'isHtml' => true,
            'to' => $emailAddress,
            'isSystem' => true,
            'parentId' => $entity->getId(),
            'parentType' => $entity->getEntityType(),
        ]);

        try {
            $this->emailSender->send($email);
        }
        catch (Exception $e) {
            $this->log->error('EmailNotification: [' . $e->getCode() . '] ' . $e->getMessage());
        }
    }

    private function getHtmlizer(): Htmlizer
    {
        if (!$this->htmlizer) {
            $this->htmlizer = $this->htmlizerFactory->create(true);
        }

        return $this->htmlizer;
    }

    private function loadParentNameFields(Entity $entity): void
    {
        $fieldDefs = $this->metadata->get(['entityDefs', $entity->getEntityType(), 'fields'], []);

        foreach ($fieldDefs as $field => $defs) {
            if (isset($defs['type']) && $defs['type'] == 'linkParent') {
                $entity->loadParentNameField($field);
            }
        }
    }
}
