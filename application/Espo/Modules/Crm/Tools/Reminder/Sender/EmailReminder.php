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

namespace Espo\Modules\Crm\Tools\Reminder\Sender;

use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Entities\Email;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Reminder;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Utils\Util;
use Espo\Core\Htmlizer\HtmlizerFactory as HtmlizerFactory;
use Espo\Core\Mail\EmailSender;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\TemplateFileManager;
use RuntimeException;

class EmailReminder
{
    public function __construct(
        private EntityManager $entityManager,
        private TemplateFileManager $templateFileManager,
        private EmailSender $emailSender,
        private Config $config,
        private HtmlizerFactory $htmlizerFactory,
        private Language $language
    ) {}

    /**
     * @throws SendingError
     */
    public function send(Reminder $reminder): void
    {
        $entityType = $reminder->getTargetEntityType();
        $entityId = $reminder->getTargetEntityId();
        $userId = $reminder->getUserId();

        if (!$entityType || !$entityId || !$userId) {
            throw new RuntimeException("Bad reminder.");
        }

        $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);
        $entity = $this->entityManager->getEntityById($entityType, $entityId);

        if (
            !$user ||
            !$entity instanceof CoreEntity ||
            !$user->getEmailAddress()
        ) {
            return;
        }

        if (
            $entity->hasLinkMultipleField('users') &&
            $entity->hasAttribute('usersColumns')
        ) {
            $status = $entity->getLinkMultipleColumn('users', 'status', $user->getId());

            if ($status === Meeting::ATTENDEE_STATUS_DECLINED) {
                return;
            }
        }

        [$subject, $body] = $this->getSubjectBody($entity, $user);

        $email = $this->entityManager->getRDBRepositoryByClass(Email::class)->getNew();

        $email->addToAddress($user->getEmailAddress());
        $email->setSubject($subject);
        $email->setBody($body);
        $email->setIsHtml();

        $this->emailSender->send($email);
    }

    /**
     * @return array{string, string}
     */
    private function getTemplates(CoreEntity $entity): array
    {
        $subjectTpl = $this->templateFileManager
            ->getTemplate('reminder', 'subject', $entity->getEntityType(), 'Crm');

        $bodyTpl = $this->templateFileManager
            ->getTemplate('reminder', 'body', $entity->getEntityType(), 'Crm');

        return [$subjectTpl, $bodyTpl];
    }

    /**
     * @return array{string, string}
     */
    private function getSubjectBody(CoreEntity $entity, User $user): array
    {
        $entityType = $entity->getEntityType();
        $entityId = $entity->getId();

        [$subjectTpl, $bodyTpl] = $this->getTemplates($entity);

        $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

        $siteUrl = rtrim($this->config->get('siteUrl'), '/');
        $translatedEntityType = $this->language->translateLabel($entityType, 'scopeNames');

        $data = [
            'recordUrl' => "$siteUrl/#$entityType/view/$entityId",
            'entityType' => $translatedEntityType,
            'entityTypeLowerFirst' => Util::mbLowerCaseFirst($translatedEntityType),
            'userName' => $user->getName(),
        ];

        $htmlizer = $this->htmlizerFactory->createForUser($user);

        $subject = $htmlizer->render(
            $entity,
            $subjectTpl,
            'reminder-email-subject-' . $entityType,
            $data,
            true
        );

        $body = $htmlizer->render(
            $entity,
            $bodyTpl,
            'reminder-email-body-' . $entityType,
            $data,
            false
        );

        return [$subject, $body];
    }
}
