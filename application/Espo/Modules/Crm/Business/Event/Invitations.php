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

namespace Espo\Modules\Crm\Business\Event;

use Laminas\Mail\Message;

use Espo\ORM\Entity;

use Espo\Core\Utils\Util;

use Espo\Core\{
    ORM\EntityManager,
    Mail\EmailSender,
    Mail\SmtpParams,
    Utils\Config,
    Utils\File\Manager as FileManager,
    Utils\DateTime as DateTimeUtil,
    Utils\NumberUtil,
    Utils\Language,
    Utils\TemplateFileManager,
    Htmlizer\HtmlizerFactory as HtmlizerFactory,
};

use DateTime;

class Invitations
{
    protected $smtpParams;

    protected $ics;

    protected $entityManager;

    protected $emailSender;

    protected $config;

    protected $dateTime;

    protected $language;

    protected $number;

    protected $templateFileManager;

    protected $fileManager;

    protected $htmlizerFactory;

    public function __construct(
        EntityManager $entityManager,
        ?SmtpParams $smtpParams,
        EmailSender $emailSender,
        Config $config,
        FileManager $fileManager,
        DateTimeUtil $dateTime,
        NumberUtil $number,
        Language $language,
        TemplateFileManager $templateFileManager,
        HtmlizerFactory $htmlizerFactory
    ) {
        $this->entityManager = $entityManager;
        $this->smtpParams = $smtpParams;
        $this->emailSender = $emailSender;
        $this->config = $config;
        $this->dateTime = $dateTime;
        $this->language = $language;
        $this->number = $number;
        $this->fileManager = $fileManager;
        $this->templateFileManager = $templateFileManager;
        $this->htmlizerFactory = $htmlizerFactory;
    }

    public function sendInvitation(Entity $entity, Entity $invitee, string $link): void
    {
        $uid = $this->entityManager->getEntity('UniqueId');

        $uid->set('data', [
            'eventType' => $entity->getEntityType(),
            'eventId' => $entity->getId(),
            'inviteeId' => $invitee->getId(),
            'inviteeType' => $invitee->getEntityType(),
            'link' => $link,
        ]);

        if ($entity->get('dateEnd')) {
            $terminateAt = $entity->get('dateEnd');
        }
        else {
            $dt = new DateTime();
            $dt->modify('+1 month');

            $terminateAt = $dt->format('Y-m-d H:i:s');
        }

        $uid->set([
            'targetId' => $entity->getId(),
            'targetType' => $entity->getEntityType(),
            'terminateAt' => $terminateAt,
        ]);

        $this->entityManager->saveEntity($uid);

        $emailAddress = $invitee->get('emailAddress');

        if (empty($emailAddress)) {
            return;
        }

        $email = $this->entityManager->getEntity('Email');
        $email->set('to', $emailAddress);

        $subjectTpl = $this->templateFileManager->getTemplate('invitation', 'subject', $entity->getEntityType(), 'Crm');
        $bodyTpl = $this->templateFileManager->getTemplate('invitation', 'body', $entity->getEntityType(), 'Crm');

        $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

        $data = [];

        $siteUrl = rtrim($this->config->get('siteUrl'), '/');
        $recordUrl = $siteUrl . '/#' . $entity->getEntityType() . '/view/' . $entity->getId();

        $data['recordUrl'] = $recordUrl;
        $data['acceptLink'] = $siteUrl . '?entryPoint=eventConfirmation&action=accept&uid=' . $uid->get('name');
        $data['declineLink'] = $siteUrl . '?entryPoint=eventConfirmation&action=decline&uid=' . $uid->get('name');
        $data['tentativeLink'] = $siteUrl . '?entryPoint=eventConfirmation&action=tentative&uid=' . $uid->get('name');

        if ($invitee->getEntityType() === 'User') {
            $data['isUser'] = true;

            $htmlizer = $this->htmlizerFactory->createForUser($invitee);
        }
        else {
            $htmlizer = $this->htmlizerFactory->createNoAcl();
        }

        $data['inviteeName'] = $invitee->get('name');
        $data['entityType'] = $this->language->translate($entity->getEntityType(), 'scopeNames');
        $data['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($data['entityType']);

        $subject = $htmlizer->render(
            $entity,
            $subjectTpl,
            'invitation-email-subject-' . $entity->getEntityType(),
            $data,
            true
        );

        $body = $htmlizer->render(
            $entity,
            $bodyTpl,
            'invitation-email-body-' . $entity->getEntityType(),
            $data,
            false
        );

        $email->set('subject', $subject);
        $email->set('body', $body);
        $email->set('isHtml', true);

        $attachmentName = ucwords($this->language->translate($entity->getEntityType(), 'scopeNames')) . '.ics';

        $attachment = $this->entityManager->getEntity('Attachment');

        $attachment->set([
            'name' => $attachmentName,
            'type' => 'text/calendar',
            'contents' => $this->getIscContents($entity),
        ]);

        $message = new Message();

        $sender = $this->emailSender->create();

        if ($this->smtpParams) {
            $sender->withSmtpParams($this->smtpParams);
        }

        $sender
            ->withMessage($message)
            ->withAttachments([$attachment])
            ->send($email);

        $this->entityManager->removeEntity($email);
    }

    protected function getIscContents(Entity $entity): string
    {
        $user = $this->entityManager
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, 'assignedUser')
            ->findOne();

        $who = '';
        $email = '';

        if ($user) {
            $who = $user->get('name');
            $email = $user->get('emailAddress');
        }

        $ics = new Ics('//EspoCRM//EspoCRM Calendar//EN', [
            'startDate' => strtotime($entity->get('dateStart')),
            'endDate' => strtotime($entity->get('dateEnd')),
            'uid' => $entity->getId(),
            'summary' => $entity->get('name'),
            'who' => $who,
            'email' => $email,
            'description' => $entity->get('description'),
        ]);

        return $ics->get();
    }
}
