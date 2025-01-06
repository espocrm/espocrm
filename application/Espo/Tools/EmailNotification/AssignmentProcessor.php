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

use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;
use Espo\Core\ORM\Type\FieldType;
use Espo\Entities\Email;
use Espo\Entities\Preferences;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Core\Htmlizer\Htmlizer;
use Espo\Core\Htmlizer\HtmlizerFactory as HtmlizerFactory;
use Espo\Core\Mail\EmailSender as EmailSender;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\TemplateFileManager;
use Espo\Core\Utils\Util;

use Exception;
use LogicException;

class AssignmentProcessor
{
    private ?Htmlizer $htmlizer = null;

    public function __construct(
        private EntityManager $entityManager,
        private HtmlizerFactory $htmlizerFactory,
        private EmailSender $emailSender,
        private Config $config,
        private TemplateFileManager $templateFileManager,
        private Metadata $metadata,
        private Language $language,
        private Log $log
    ) {}

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

        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            return;
        }

        if ($user->isPortal()) {
            return;
        }

        $preferences = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $userId);

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

        $assignerUser = $this->entityManager->getEntityById(User::ENTITY_TYPE, $assignerUserId);

        $entity = $this->entityManager->getEntityById($entityType, $entityId);

        if (!$entity) {
            return;
        }

        if (!$assignerUser) {
            return;
        }

        if (!$entity instanceof Entity) {
            return;
        }

        $this->loadParentNameFields($entity);

        if (!$entity->hasLinkMultipleField(Field::ASSIGNED_USERS)) {
            if ($entity->get('assignedUserId') !== $userId) {
                return;
            }
        }

        $emailAddress = $user->get('emailAddress');

        if (!$emailAddress) {
            return;
        }

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $subjectTpl = $this->templateFileManager->getTemplate('assignment', 'subject', $entity->getEntityType());
        $bodyTpl = $this->templateFileManager->getTemplate('assignment', 'body', $entity->getEntityType());

        $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

        $recordUrl = rtrim($this->config->get('siteUrl'), '/') .
            '/#' . $entity->getEntityType() . '/view/' . $entity->getId();

        $templateData = [
            'userName' => $user->get(Field::NAME),
            'assignerUserName' => $assignerUser->get(Field::NAME),
            'recordUrl' => $recordUrl,
            'entityType' => $this->language->translateLabel($entity->getEntityType(), 'scopeNames'),
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
        } catch (Exception $e) {
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
            if (isset($defs['type']) && $defs['type'] == FieldType::LINK_PARENT) {
                $entity->loadParentNameField($field);
            }
        }
    }
}
