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

use Espo\Core\Field\DateTime as DateTimeField;
use Espo\Core\Field\LinkParent;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
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
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\TemplateFileManager;

use DateTime;

class Invitations
{
    private const TYPE_INVITATION = 'invitation';
    private const TYPE_CANCELLATION = 'cancellation';

    /**
     * Some dependencies are unused to keep backward compatibility.
     */
    public function __construct(
        private EntityManager $entityManager,
        private ?SmtpParams $smtpParams,
        private EmailSender $emailSender,
        private Config $config,
        private Language $language,
        private TemplateFileManager $templateFileManager,
        private HtmlizerFactory $htmlizerFactory
    ) {}

    /**
     * @throws SendingError
     */
    public function sendInvitation(Entity $entity, Entity $invitee, string $link): void
    {
        $this->sendInternal($entity, $invitee, $link);
    }

    /**
     * @throws SendingError
     */
    public function sendCancellation(Entity $entity, Entity $invitee, string $link): void
    {
        $this->sendInternal($entity, $invitee, $link, self::TYPE_CANCELLATION);
    }

    /**
     * @throws SendingError
     */
    private function sendInternal(
        Entity $entity,
        Entity $invitee,
        string $link,
        string $type = self::TYPE_INVITATION
    ): void {

        $uid = $type === self::TYPE_INVITATION ? $this->createUniqueId($entity, $invitee, $link) : null;

        /** @var ?string $emailAddress */
        $emailAddress = $invitee->get('emailAddress');

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
            ->getRelation($entity, 'assignedUser')
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

        if ($entity instanceof Meeting || $entity instanceof Call) {
            $attendees = $this->getAttendees($entity, $addressList);
        }

        $ics = new Ics('//EspoCRM//EspoCRM Calendar//EN', [
            'method' => $method,
            'status' => $status,
            'startDate' => strtotime($entity->get('dateStart')),
            'endDate' => strtotime($entity->get('dateEnd')),
            'uid' => $entity->getId(),
            'summary' => $entity->get('name'),
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

        $siteUrl = rtrim($this->config->get('siteUrl'), '/');

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

        $data['inviteeName'] = $invitee->get('name');
        $data['entityType'] = $this->language->translateLabel($entity->getEntityType(), 'scopeNames');
        $data['entityTypeLowerFirst'] = Util::mbLowerCaseFirst($data['entityType']);

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
        $users = $this->entityManager->getRelation($entity, 'users')->find();

        foreach ($users as $it) {
            $address = $it->getEmailAddress();

            if ($address && !in_array($address, $addressList)) {
                $addressList[] = $address;
                $attendees[] = [$address, $it->getName()];
            }
        }

        /** @var iterable<Contact> $contacts */
        $contacts = $this->entityManager->getRelation($entity, 'contacts')->find();

        foreach ($contacts as $it) {
            $address = $it->getEmailAddress();

            if ($address && !in_array($address, $addressList)) {
                $addressList[] = $address;
                $attendees[] = [$address, $it->getName()];
            }
        }

        /** @var iterable<Lead> $leads */
        $leads = $this->entityManager->getRelation($entity, 'leads')->find();

        foreach ($leads as $it) {
            $address = $it->getEmailAddress();

            if ($address && !in_array($address, $addressList)) {
                $addressList[] = $address;
                $attendees[] = [$address, $it->getName()];
            }
        }

        return $attendees;
    }
}
