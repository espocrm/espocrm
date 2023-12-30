<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\Crm\Business\Event;

use Espo\Core\Exceptions\Error;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Espo\Entities\UniqueId;
use Laminas\Mail\Message;

use Espo\ORM\Entity;
use Espo\Entities\User;
use Espo\Core\Utils\Util;
use Espo\Core\Htmlizer\HtmlizerFactory as HtmlizerFactory;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\NumberUtil;
use Espo\Core\Utils\TemplateFileManager;

use DateTime;

class Invitations
{
    private const TYPE_INVITATION = 'invitation';
    private const TYPE_CANCELLATION = 'cancellation';

    private $smtpParams;
    private $entityManager;
    private $emailSender;
    private $config;
    private $dateTime; /** @phpstan-ignore-line */
    private $language;
    private $number; /** @phpstan-ignore-line */
    private $templateFileManager;
    private $fileManager; /** @phpstan-ignore-line */
    private $htmlizerFactory;

    /**
     * Some dependencies are unused to keep backward compatibility.
     */
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

    /**
     * @throws SendingError
     * @throws Error
     */
    public function sendInvitation(Entity $entity, Entity $invitee, string $link): void
    {
        $this->sendInternal($entity, $invitee, $link);
    }

    /**
     * @throws SendingError
     * @throws Error
     */
    public function sendCancellation(Entity $entity, Entity $invitee, string $link): void
    {
        $this->sendInternal($entity, $invitee, $link, self::TYPE_CANCELLATION);
    }

    /**
     * @throws SendingError
     * @throws Error
     */
    private function sendInternal(
        Entity $entity,
        Entity $invitee,
        string $link,
        string $type = self::TYPE_INVITATION
    ): void {

        $uid = $type === self::TYPE_INVITATION ?
            $this->createUniqueId($entity, $invitee, $link) : null;

        $emailAddress = $invitee->get('emailAddress');

        if (empty($emailAddress)) {
            return;
        }

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $email->set('to', $emailAddress);

        $subjectTpl = $this->templateFileManager->getTemplate($type, 'subject', $entity->getEntityType(), 'Crm');
        $bodyTpl = $this->templateFileManager->getTemplate($type, 'body', $entity->getEntityType(), 'Crm');

        $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

        $data = [];

        $siteUrl = rtrim($this->config->get('siteUrl'), '/');
        $recordUrl = $siteUrl . '/#' . $entity->getEntityType() . '/view/' . $entity->getId();

        $data['recordUrl'] = $recordUrl;

        if ($uid) {
            $part = $siteUrl . '?entryPoint=eventConfirmation&action=';

            $data['acceptLink'] = $part . 'accept&uid=' . $uid->getIdValue();
            $data['declineLink'] = $part . 'decline&uid=' . $uid->getIdValue();
            $data['tentativeLink'] = $part . 'tentative&uid=' . $uid->getIdValue();
        }

        if ($invitee instanceof User) {
            $data['isUser'] = true;

            $htmlizer = $this->htmlizerFactory->createForUser($invitee);
        }
        else {
            $htmlizer = $this->htmlizerFactory->createNoAcl();
        }

        $data['inviteeName'] = $invitee->get('name');
        $data['entityType'] = $this->language->translateLabel($entity->getEntityType(), 'scopeNames');
        $data['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($data['entityType']);

        $subject = $htmlizer->render(
            $entity,
            $subjectTpl,
            $type . '-email-subject-' . $entity->getEntityType(),
            $data,
            true,
            true
        );

        $body = $htmlizer->render(
            $entity,
            $bodyTpl,
            $type . '-email-body-' . $entity->getEntityType(),
            $data,
            false,
            true
        );

        $email->set('subject', $subject);
        $email->set('body', $body);
        $email->set('isHtml', true);

        $attachmentName = ucwords($this->language->translateLabel($entity->getEntityType(), 'scopeNames')) . '.ics';

        /** @var Attachment $attachment */
        $attachment = $this->entityManager->getNewEntity(Attachment::ENTITY_TYPE);

        $attachment->set([
            'name' => $attachmentName,
            'type' => 'text/calendar',
            'contents' => $this->getIcsContents($entity, $type),
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
    }

    private function createUniqueId(Entity $entity, Entity $invitee, string $link): UniqueId
    {
        /** @var UniqueId $uid */
        $uid = $this->entityManager->getNewEntity(UniqueId::ENTITY_TYPE);

        $uid->set('data', [
            'eventType' => $entity->getEntityType(),
            'eventId' => $entity->getId(),
            'inviteeId' => $invitee->getId(),
            'inviteeType' => $invitee->getEntityType(),
            'link' => $link,
            'dateStart' => $entity->get('dateStart'),
        ]);

        if ($entity->get('dateEnd')) {
            $terminateAt = $entity->get('dateEnd');
        }
        else {
            $dt = new DateTime();
            $dt->modify('+1 month');

            $terminateAt = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
        }

        $uid->set([
            'targetId' => $entity->getId(),
            'targetType' => $entity->getEntityType(),
            'terminateAt' => $terminateAt,
        ]);

        $this->entityManager->saveEntity($uid);

        return $uid;
    }

    protected function getIcsContents(Entity $entity, string $type): string
    {
        /** @var ?User $user */
        $user = $this->entityManager
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, 'assignedUser')
            ->findOne();

        $who = '';
        $email = '';

        if ($user) {
            $who = $user->getName();
            $email = $user->getEmailAddress();
        }

        $status = $type === self::TYPE_CANCELLATION ?
            Ics::STATUS_CANCELLED :
            Ics::STATUS_CONFIRMED;

        $method = $type === self::TYPE_CANCELLATION ?
            Ics::METHOD_CANCEL :
            Ics::METHOD_REQUEST;

        $ics = new Ics('//EspoCRM//EspoCRM Calendar//EN', [
            'method' => $method,
            'startDate' => strtotime($entity->get('dateStart')),
            'endDate' => strtotime($entity->get('dateEnd')),
            'uid' => $entity->getId(),
            'summary' => $entity->get('name'),
            'who' => $who,
            'email' => $email,
            'description' => $entity->get('description'),
            'status' => $status,
        ]);

        return $ics->get();
    }
}
