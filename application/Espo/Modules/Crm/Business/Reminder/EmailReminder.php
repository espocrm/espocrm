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

namespace Espo\Modules\Crm\Business\Reminder;

use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Core\Utils\Util;

use Espo\Core\{
    ORM\EntityManager,
    Utils\TemplateFileManager,
    Mail\EmailSender,
    Utils\Config,
    Htmlizer\HtmlizerFactory as HtmlizerFactory,
    Utils\Language,
};

class EmailReminder
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EmailSender
     */
    protected $emailSender;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var TemplateFileManager
     */
    protected $templateFileManager;

    /**
     * @var Language
     */
    protected $language;

    /**
     * @var HtmlizerFactory
     */
    protected $htmlizerFactory;

    public function __construct(
        EntityManager $entityManager,
        TemplateFileManager $templateFileManager,
        EmailSender $emailSender,
        Config $config,
        HtmlizerFactory $htmlizerFactory,
        Language $language
    ) {
        $this->entityManager = $entityManager;
        $this->templateFileManager = $templateFileManager;
        $this->emailSender = $emailSender;
        $this->config = $config;
        $this->language = $language;
        $this->htmlizerFactory = $htmlizerFactory;
    }

    public function send(Entity $reminder): void
    {
        $user = $this->entityManager->getEntity('User', $reminder->get('userId'));
        $entity = $this->entityManager->getEntity($reminder->get('entityType'), $reminder->get('entityId'));

        if (!$user || !$entity) {
            return;
        }

        $emailAddress = $user->get('emailAddress');

        if (!$emailAddress) {
            return;
        }

        if (!$entity instanceof CoreEntity) {
            return;
        }

        if ($entity->hasLinkMultipleField('users')) {
            $entity->loadLinkMultipleField('users', ['status' => 'acceptanceStatus']);
            $status = $entity->getLinkMultipleColumn('users', 'status', $user->getId());

            if ($status === 'Declined') {
                return;
            }
        }

        $email = $this->entityManager->getEntity('Email');

        $email->set('to', $emailAddress);

        $subjectTpl = $this->templateFileManager
            ->getTemplate('reminder', 'subject', $entity->getEntityType(), 'Crm');

        $bodyTpl = $this->templateFileManager
            ->getTemplate('reminder', 'body', $entity->getEntityType(), 'Crm');

        $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

        $data = [];

        $siteUrl = rtrim($this->config->get('siteUrl'), '/');
        $recordUrl = $siteUrl . '/#' . $entity->getEntityType() . '/view/' . $entity->getId();

        $data['recordUrl'] = $recordUrl;
        $data['entityType'] = $this->language->translate($entity->getEntityType(), 'scopeNames');
        $data['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($data['entityType']);
        $data['userName'] = $user->get('name');

        $htmlizer = $this->htmlizerFactory->createForUser($user);

        $subject = $htmlizer->render(
            $entity,
            $subjectTpl,
            'reminder-email-subject-' . $entity->getEntityType(),
            $data,
            true
        );

        $body = $htmlizer->render(
            $entity,
            $bodyTpl,
            'reminder-email-body-' . $entity->getEntityType(),
            $data,
            false
        );

        $email->set('subject', $subject);
        $email->set('body', $body);
        $email->set('isHtml', true);

        $this->emailSender->send($email);
    }
}
