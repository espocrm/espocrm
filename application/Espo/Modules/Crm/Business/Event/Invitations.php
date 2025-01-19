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

namespace Espo\Modules\Crm\Business\Event;

use Espo\Core\Field\DateTime as DateTimeField;
use Espo\Core\Field\LinkParent;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Espo\Entities\Preferences;
use Espo\Entities\UniqueId;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\ORM\Entity;
use Espo\Entities\User;
use Espo\Core\Utils\Util;
use Espo\Core\Htmlizer\HtmlizerFactory as HtmlizerFactory;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\TemplateFileManager;

use DateTime;

/**
 * Do not use. Use `Espo\Modules\Crm\Tools\Meeting\Invitation\Sender`.
 * @internal
 */
class Invitations
{
    private const TYPE_INVITATION = 'invitation';
    private const TYPE_CANCELLATION = 'cancellation';

    /**
     * Some dependencies are unused to keep backward compatibility.
     * @todo Revise.
     */
    public function __construct(
        private EntityManager $entityManager,
        private ?SmtpParams $smtpParams,
        private EmailSender $emailSender,
        private Language $language,
        private TemplateFileManager $templateFileManager,
        private HtmlizerFactory $htmlizerFactory,
        private ApplicationConfig $applicationConfig,
        private DateTimeUtil $dateTime,
    ) {}

    /**
     * @throws SendingError
     */
    public function sendInvitation(Entity $entity, Entity $invitee, string $link, ?string $emailAddress = null): void
    {
        $this->sendInternal($entity, $invitee, $link, self::TYPE_INVITATION, $emailAddress);
    }

    /**
     * @throws SendingError
     */
    public function sendCancellation(Entity $entity, Entity $invitee, string $link, ?string $emailAddress = null): void
    {
        $this->sendInternal($entity, $invitee, $link, self::TYPE_CANCELLATION, $emailAddress);
    }

    /**
     * @throws SendingError
     */
    private function sendInternal(
        Entity $entity,
        Entity $invitee,
        string $link,
        string $type,
        ?string $emailAddress,
    ): void {

        $uid = $type === self::TYPE_INVITATION ? $this->createUniqueId($entity, $invitee, $link) : null;

        /** @var ?string $emailAddress */
        $emailAddress ??= $invitee->get(Field::EMAIL_ADDRESS);

        if (!$emailAddress) {
            return;
        }

        $htmlizer = $invitee instanceof User ?
            $this->htmlizerFactory->createForUser($invitee) :
            $this->htmlizerFactory->createNoAcl();

        $data = $this->prepareData($entity, $uid, $invitee);

        $subjectTpl = $this->templateFileManager->getTemplate($type, 'subject', $entity->getEntityType(), 'Crm');
        $subjectTpl = str_replace(["\n", "\r"], '', $subjectTpl);

        $bodyTpl = $this->templateFileManager->getTemplate($type, 'body', $entity->getEntityType(), 'Crm');

        $subject = $htmlizer->render(
            $entity,
            $subjectTpl,
            "$type-email-subject-{$entity->getEntityType()}",
            $data,
            true,
            true
        );

        $body = $htmlizer->render(
            $entity,
            $bodyTpl,
            "$type-email-body-{$entity->getEntityType()}",
            $data,
            false,
            true
        );

        $email = $this->entityManager->getRDBRepositoryByClass(Email::class)->getNew();

        $email
            ->addToAddress($emailAddress)
            ->setSubject($subject)
            ->setBody($body)
            ->setIsHtml()
            ->setParent(LinkParent::createFromEntity($entity));

        $attachmentName = ucwords($this->language->translateLabel($entity->getEntityType(), 'scopeNames')) . '.ics';

        $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getNew();

        $attachment
            ->setName($attachmentName)
            ->setType('text/calendar')
            ->setContents($this->getIcsContents($entity, $type));

        $sender = $this->emailSender->create();

        if ($this->smtpParams) {
            $sender->withSmtpParams($this->smtpParams);
        }

        $sender
            ->withAttachments([$attachment])
            ->send($email);
    }

    private function createUniqueId(Entity $entity, Entity $invitee, string $link): UniqueId
    {
        $uid = $this->entityManager->getRDBRepositoryByClass(UniqueId::class)->getNew();

        $uid->setData([
            'eventType' => $entity->getEntityType(),
            'eventId' => $entity->getId(),
            'inviteeId' => $invitee->getId(),
            'inviteeType' => $invitee->getEntityType(),
            'link' => $link,
            'dateStart' => $entity->get('dateStart'),
        ]);

        if ($entity->get('dateEnd')) {
            $terminateAt = $entity->get('dateEnd');
        } else {
            $dt = new DateTime();
            $dt->modify('+1 month');

            $terminateAt = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
        }

        $uid->setTarget(LinkParent::createFromEntity($entity));
        $uid->setTerminateAt(DateTimeField::fromString($terminateAt));

        $this->entityManager->saveEntity($uid);

        return $uid;
    }

    protected function getIcsContents(Entity $entity, string $type): string
    {
        /** @var ?User $user */
        $user = $this->entityManager
            ->getRelation($entity, Field::ASSIGNED_USER)
            ->findOne();

        $addressList = [];

        $organizerName = null;
        $organizerAddress = null;

        if ($user) {
            $organizerName = $user->getName();
            $organizerAddress = $user->getEmailAddress();

            if ($organizerAddress) {
                $addressList[] = $organizerAddress;
            }
        }

        $status = $type === self::TYPE_CANCELLATION ?
            Ics::STATUS_CANCELLED :
            Ics::STATUS_CONFIRMED;

        $method = $type === self::TYPE_CANCELLATION ?
            Ics::METHOD_CANCEL :
            Ics::METHOD_REQUEST;

        $attendees = [];

        $uid = $entity->getId();

        if ($entity instanceof Meeting || $entity instanceof Call) {
            $attendees = $this->getAttendees($entity, $addressList);

            $uid = $entity->getUid() ?? $uid;
        }

        $ics = new Ics('//EspoCRM//EspoCRM Calendar//EN', [
            'method' => $method,
            'status' => $status,
            'startDate' => strtotime($entity->get('dateStart')),
            'endDate' => strtotime($entity->get('dateEnd')),
            'uid' => $uid,
            'summary' => $entity->get(Field::NAME),
            'organizer' => $organizerAddress ? [$organizerAddress, $organizerName] : null,
            'attendees' => $attendees,
            'description' => $entity->get('description'),
        ]);

        return $ics->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareData(Entity $entity, ?UniqueId $uid, Entity $invitee): array
    {
        $data = [];

        $siteUrl = $this->applicationConfig->getSiteUrl();

        $data['recordUrl'] = "$siteUrl/#{$entity->getEntityType()}/view/{$entity->getId()}";

        if ($uid) {
            $part = "$siteUrl?entryPoint=eventConfirmation&action=";

            $data['acceptLink'] = $part . 'accept&uid=' . $uid->getIdValue();
            $data['declineLink'] = $part . 'decline&uid=' . $uid->getIdValue();
            $data['tentativeLink'] = $part . 'tentative&uid=' . $uid->getIdValue();
        }

        if ($invitee instanceof User) {
            $data['isUser'] = true;
        }

        $data['inviteeName'] = $invitee->get(Field::NAME);
        $data['entityType'] = $this->language->translateLabel($entity->getEntityType(), 'scopeNames');
        $data['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($data['entityType']);

        [$timeZone, $language] = $this->getTimeZoneAndLanguage($invitee);

        $data['timeZone'] = $timeZone;
        $data['dateStartFull'] = $this->prepareDateStartFull($entity, $timeZone, $language);

        return $data;
    }

    /**
     * @param string[] $addressList
     * @return array{string, ?string}[]
     */
    private function getAttendees(Meeting|Call $entity, array $addressList): array
    {
        $attendees = [];

        /** @var iterable<User> $users */
        $users = $this->entityManager
            ->getRelation($entity, Meeting::LINK_USERS)
            ->find();

        foreach ($users as $it) {
            $address = $it->getEmailAddress();

            if ($address && !in_array($address, $addressList)) {
                $addressList[] = $address;
                $attendees[] = [$address, $it->getName()];
            }
        }

        /** @var iterable<Contact> $contacts */
        $contacts = $this->entityManager
            ->getRelation($entity, Meeting::LINK_CONTACTS)
            ->find();

        foreach ($contacts as $it) {
            $address = $it->getEmailAddress();

            if ($address && !in_array($address, $addressList)) {
                $addressList[] = $address;
                $attendees[] = [$address, $it->getName()];
            }
        }

        /** @var iterable<Lead> $leads */
        $leads = $this->entityManager
            ->getRelation($entity, Meeting::LINK_LEADS)
            ->find();

        foreach ($leads as $it) {
            $address = $it->getEmailAddress();

            if ($address && !in_array($address, $addressList)) {
                $addressList[] = $address;
                $attendees[] = [$address, $it->getName()];
            }
        }

        return $attendees;
    }

    /**
     * @return array{string, string}
     */
    private function getTimeZoneAndLanguage(Entity $invitee): array
    {
        $timeZone = $this->applicationConfig->getTimeZone();
        $language = $this->applicationConfig->getLanguage();

        if ($invitee instanceof User) {
            $preferences = $this->entityManager
                ->getRepositoryByClass(Preferences::class)
                ->getById($invitee->getId());

            if ($preferences && $preferences->getTimeZone()) {
                $timeZone = $preferences->getTimeZone();
            }

            if ($preferences && $preferences->getLanguage()) {
                $language = $preferences->getLanguage();
            }
        }

        return [$timeZone, $language];
    }

    /**
     * @todo Take into account the invitees time format if a user.
     */
    private function prepareDateStartFull(Entity $entity, string $timeZone, string $language): ?string
    {
        $format = "dddd, MMMM Do, YYYY";

        if ($entity->get('dateStartDate')) {
            $value = $entity->get('dateStartDate');

            return $this->dateTime->convertSystemDate($value, $format, $language);
        }

        $value = $entity->get('dateStart');

        if (!$value) {
            return null;
        }

        $format = $this->applicationConfig->getTimeFormat() . ", " . $format;

        return $this->dateTime->convertSystemDateTime($value, $timeZone, $format, $language);
    }
}
