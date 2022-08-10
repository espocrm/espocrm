<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Mail;

use Espo\Core\Mail\Importer\DuplicateFinder;
use Espo\Entities\Email;
use Espo\Entities\Job;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Repositories\Email as EmailRepository;

use Espo\ORM\EntityManager;

use Espo\Core\Utils\DateTime as DateTimeUtil;

use Espo\Core\Notification\AssignmentNotificator;
use Espo\Core\Notification\AssignmentNotificatorFactory;
use Espo\Core\Notification\AssignmentNotificator\Params as AssignmentNotificatorParams;
use Espo\Core\Mail\Importer\Data;

use Espo\Core\Utils\Config;
use Espo\Core\FieldProcessing\Relation\LinkMultipleSaver;
use Espo\Core\FieldProcessing\Saver\Params as SaverParams;
use Espo\Core\Job\QueueName;
use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Modules\Crm\Entities\Lead;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Imports email messages. Handles duplicate checking, parent look-up.
 */
class Importer
{
    private const SUBJECT_MAX_LENGTH = 255;

    private EntityManager $entityManager;
    private Config $config;
    private AssignmentNotificator $notificator;
    private FiltersMatcher $filtersMatcher;
    private ParserFactory $parserFactory;
    private LinkMultipleSaver $linkMultipleSaver;
    private DuplicateFinder $duplicateFinder;

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        AssignmentNotificatorFactory $notificatorFactory,
        ParserFactory $parserFactory,
        LinkMultipleSaver $linkMultipleSaver,
        DuplicateFinder $duplicateFinder
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->parserFactory = $parserFactory;
        $this->linkMultipleSaver = $linkMultipleSaver;
        $this->duplicateFinder = $duplicateFinder;

        $this->notificator = $notificatorFactory->create(Email::ENTITY_TYPE);
        $this->filtersMatcher = new FiltersMatcher();
    }

    public function import(Message $message, Data $data): ?Email
    {
        $assignedUserId = $data->getAssignedUserId();
        $teamIdList = $data->getTeamIdList();
        $userIdList = $data->getUserIdList();
        $filterList = $data->getFilterList();
        $folderData = $data->getFolderData();

        $parser = $message instanceof MessageWrapper ?
            ($message->getParser() ?? $this->parserFactory->create()) :
            $this->parserFactory->create();

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $email->set('isBeingImported', true);

        $subject = '';

        if ($parser->hasHeader($message, 'subject')) {
            $subject = $parser->getHeader($message, 'subject');
        }

        if (!empty($subject) && is_string($subject)) {
            $subject = trim($subject);
        }

        if ($subject !== '0' && empty($subject)) {
            $subject = '(No Subject)';
        }

        if (strlen($subject) > self::SUBJECT_MAX_LENGTH) {
            $subject = substr($subject, 0, self::SUBJECT_MAX_LENGTH);
        }

        $email->set('isHtml', false);
        $email->set('name', $subject);
        $email->set('status', Email::STATUS_ARCHIVED);
        $email->set('attachmentsIds', []);
        $email->set('teamsIds', $teamIdList);

        if ($assignedUserId) {
            $email->set('assignedUserId', $assignedUserId);
            $email->addLinkMultipleId('assignedUsers', $assignedUserId);
        }

        foreach ($userIdList as $uId) {
            $email->addLinkMultipleId('users', $uId);
        }

        $fromAddressData = $parser->getAddressData($message, 'from');

        if ($fromAddressData) {
            $fromString = ($fromAddressData->name ? ($fromAddressData->name . ' ') : '') . '<' .
                $fromAddressData->address . '>';

            $email->set('fromString', $fromString);
        }

        $replyToData = $parser->getAddressData($message, 'reply-To');

        if ($replyToData) {
            $replyToString = ($replyToData->name ? ($replyToData->name . ' ') : '') .
                '<' . $replyToData->address . '>';

            $email->set('replyToString', $replyToString);
        }

        $fromArr = $parser->getAddressList($message, 'from');
        $toArr = $parser->getAddressList($message, 'to');
        $ccArr = $parser->getAddressList($message, 'cc');
        $replyToArr = $parser->getAddressList($message, 'reply-To');

        if (count($fromArr)) {
            $email->set('from', $fromArr[0]);
        }

        $email->set('to', implode(';', $toArr));
        $email->set('cc', implode(';', $ccArr));
        $email->set('replyTo', implode(';', $replyToArr));

        $addressNameMap = $parser->getAddressNameMap($message);

        $email->set('addressNameMap', $addressNameMap);

        foreach ($folderData as $uId => $folderId) {
            $email->setLinkMultipleColumn('users', 'folderId', $uId, $folderId);
        }

        if ($this->filtersMatcher->match($email, $filterList, true)) {
            return null;
        }

        if (
            $parser->hasHeader($message, 'message-Id') &&
            $parser->getHeader($message, 'message-Id')
        ) {
            /** @var string $messageId */
            $messageId = $parser->getMessageId($message);

            $email->set('messageId', $messageId);

            if ($parser->hasHeader($message, 'delivered-To')) {
                $email->set(
                    'messageIdInternal',
                    $messageId . '-' . $parser->getHeader($message, 'delivered-To')
                );
            }

            if (stripos($messageId, '@espo-system') !== false) {
                return null;
            }
        }

        if ($parser->hasHeader($message, 'date')) {
            try {
                /** @var string $dateHeaderValue */
                $dateHeaderValue = $parser->getHeader($message, 'date');

                $dt = new DateTime($dateHeaderValue);

                $dateSent = $dt
                    ->setTimezone(new DateTimeZone('UTC'))
                    ->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

                $email->set('dateSent', $dateSent);
            }
            catch (Exception $e) {}
        }

        $duplicate = $this->findDuplicate($email);

        if ($duplicate && $duplicate->getStatus() !== Email::STATUS_BEING_IMPORTED) {
            /** @var Email $duplicate */
            $duplicate = $this->entityManager->getEntityById(Email::ENTITY_TYPE, $duplicate->getId());

            $this->processDuplicate(
                $duplicate,
                $assignedUserId,
                $userIdList,
                $folderData,
                $teamIdList
            );

            return $duplicate;
        }

        if (!$email->getDateSent()) {
            $email->set('dateSent', date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT));
        }

        if ($parser->hasHeader($message, 'delivery-Date')) {
            try {
                /** @var string $deliveryDateHeaderValue */
                $deliveryDateHeaderValue = $parser->getHeader($message, 'delivery-Date');

                $dt = new DateTime($deliveryDateHeaderValue);

                $deliveryDate = $dt
                    ->setTimezone(new DateTimeZone('UTC'))
                    ->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

                $email->set('deliveryDate', $deliveryDate);
            }
            catch (Exception $e) {}
        }

        $inlineAttachmentList = [];

        if (!$data->fetchOnlyHeader()) {
            $inlineAttachmentList = $parser->getInlineAttachmentList($message, $email);

            if ($this->filtersMatcher->match($email, $filterList)) {
                return null;
            }
        }
        else {
            $email->set('body', 'Not fetched. The email size exceeds the limit.');
            $email->set('isHtml', false);
        }

        $replied = null;

        if (
            $parser->hasHeader($message, 'in-Reply-To') &&
            $parser->getHeader($message, 'in-Reply-To')
        ) {
            $arr = explode(' ', $parser->getHeader($message, 'in-Reply-To'));

            $inReplyTo = $arr[0];

            if ($inReplyTo) {
                if ($inReplyTo[0] !== '<') {
                    $inReplyTo = '<' . $inReplyTo . '>';
                }

                /** @var Email|null $replied */
                $replied = $this->entityManager
                    ->getRDBRepository(Email::ENTITY_TYPE)
                    ->where([
                        'messageId' => $inReplyTo
                    ])
                    ->findOne();

                if ($replied) {
                    $email->set('repliedId', $replied->getId());

                    /** @var string[] $repliedTeamIdList */
                    $repliedTeamIdList = $replied->getLinkMultipleIdList('teams');

                    foreach ($repliedTeamIdList as $repliedTeamId) {
                        $email->addLinkMultipleId('teams', $repliedTeamId);
                    }
                }
            }
        }

        $parentFound = $this->processReferences($parser, $message, $email);

        if (
            !$parentFound &&
            $replied &&
            $replied->getParentId() &&
            $replied->getParentType()
        ) {
            /** @var string $parentId */
            $parentId = $replied->getParentId();
            /** @var string $parentType */
            $parentType = $replied->getParentType();

            $parentEntity = $this->entityManager->getEntityById($parentType, $parentId);

            if ($parentEntity) {
                $parentFound = true;

                $email->set('parentType', $parentType);
                $email->set('parentId', $parentId);
            }
        }

        if (!$parentFound) {
            $from = $email->get('from');

            if ($from) {
                $parentFound = $this->findParentByAddress($email, $from);
            }
        }

        if (!$parentFound) {
            if (!empty($replyToArr)) {
                $parentFound = $this->findParentByAddress($email, $replyToArr[0]);
            }
        }

        if (!$parentFound) {
            if (!empty($toArr)) {
                $parentFound = $this->findParentByAddress($email, $toArr[0]);
            }
        }

        if (!$duplicate) {
            $this->entityManager->getLocker()->lockExclusive(Email::ENTITY_TYPE);

            $duplicate = $this->findDuplicate($email);

            if ($duplicate) {
                $this->entityManager->getLocker()->rollback();

                if ($duplicate->getStatus() !== Email::STATUS_BEING_IMPORTED) {
                    /** @var Email $duplicate */
                    $duplicate = $this->entityManager->getEntityById(Email::ENTITY_TYPE, $duplicate->getId());

                    $this->processDuplicate(
                        $duplicate,
                        $assignedUserId,
                        $userIdList,
                        $folderData,
                        $teamIdList
                    );

                    return $duplicate;
                }
            }
        }

        if ($duplicate) {
            $duplicate->set([
                'from' => $email->get('from'),
                'to' => $email->get('to'),
                'cc' => $email->get('cc'),
                'bcc' => $email->get('bcc'),
                'replyTo' => $email->get('replyTo'),
                'name' => $email->get('name'),
                'dateSent' => $email->get('dateSent'),
                'body' => $email->get('body'),
                'bodyPlain' => $email->get('bodyPlain'),
                'parentType' => $email->get('parentType'),
                'parentId' => $email->get('parentId'),
                'isHtml' => $email->get('isHtml'),
                'messageId' => $email->get('messageId'),
                'fromString' => $email->get('fromString'),
                'replyToString' => $email->get('replyToString'),
            ]);

            /** @var EmailRepository $emailRepository */
            $emailRepository = $this->entityManager->getRDBRepository(Email::ENTITY_TYPE);

            $emailRepository->fillAccount($duplicate);

            $this->processDuplicate(
                $duplicate,
                $assignedUserId,
                $userIdList,
                $folderData,
                $teamIdList
            );

            return $duplicate;
        }

        if (!$email->getMessageId()) {
            $email->setDummyMessageId();
        }

        $email->set('status', Email::STATUS_BEING_IMPORTED);

        $this->entityManager->saveEntity($email, [
            'skipAll' => true,
            'keepNew' => true,
        ]);

        $this->entityManager->getLocker()->commit();

        if ($parentFound) {
            $this->processEmailWithParent($email);
        }

        $email->set('status', Email::STATUS_ARCHIVED);

        $this->entityManager->saveEntity($email, ['isBeingImported' => true]);

        foreach ($inlineAttachmentList as $attachment) {
            $attachment->set([
                'relatedId' => $email->getId(),
                'relatedType' => Email::ENTITY_TYPE,
                'field' => 'body',
            ]);

            $this->entityManager->saveEntity($attachment);
        }

        return $email;
    }

    private function processEmailWithParent(Email $email): void
    {
        $parentLink = $email->getParent();

        if (!$parentLink) {
            return;
        }

        $parentType = $parentLink->getEntityType();
        $parentId = $parentLink->getId();

        $emailKeepParentTeamsEntityList = $this->config->get('emailKeepParentTeamsEntityList') ?? [];

        if (
            !in_array($parentType, $emailKeepParentTeamsEntityList) ||
            !$this->entityManager->hasRepository($parentType)
        ) {
            return;
        }

        $parent = $this->entityManager->getEntity($parentType, $parentId);

        if (!$parent) {
            return;
        }

        if (!$parent instanceof CoreEntity) {
            return;
        }

        /** @var string[] $parentTeamIdList */
        $parentTeamIdList = $parent->getLinkMultipleIdList('teams');

        foreach ($parentTeamIdList as $parentTeamId) {
            $email->addLinkMultipleId('teams', $parentTeamId);
        }
    }

    private function processReferences(Parser $parser, Message $message, Email $email): bool
    {
        if (
            !$parser->hasHeader($message, 'references') ||
            !$parser->getHeader($message, 'references')
        ) {
            return false;
        }

        $references = $parser->getHeader($message, 'references');

        $delimiter = strpos($references, '>,') ? ',' : ' ';

        foreach (explode($delimiter, $references) as $reference) {
            $reference = str_replace(['/', '@'], ' ', trim(trim($reference), '<>'));

            $parentFound = $this->processReferencesItem($email, $reference);

            if ($parentFound) {
                return true;
            }
        }

        return false;
    }

    private function processReferencesItem(Email $email, string $reference): bool
    {
        $parentType = null;
        $parentId = null;
        $number = null;
        $emailSent = PHP_INT_MAX;

        $n = sscanf($reference, '%s %s %d %d espo', $parentType, $parentId, $emailSent, $number);

        if ($n !== 4) {
            $n = sscanf($reference, '%s %s %d %d espo-system', $parentType, $parentId, $emailSent, $number);
        }

        if ($n !== 4 || $emailSent >= time()) {
            return false;
        }

        if (!$parentType || !$parentId) {
            return false;
        }

        $email->set('parentType', $parentType);
        $email->set('parentId', $parentId);

        if ($parentType === Lead::ENTITY_TYPE) {
            /** @var ?Lead $parent */
            $parent = $this->entityManager->getEntityById(Lead::ENTITY_TYPE, $parentId);

            if (!$parent) {
                $email->set('parentType', null);
                $email->set('parentId', null);

                return false;
            }

            $this->processReferenceLead($email, $parent);
        }

        return true;
    }

    private function processReferenceLead(Email $email, Lead $lead): void
    {
        if ($lead->getStatus() !== Lead::STATUS_CONVERTED) {
            return;
        }

        $createdAccountId = $lead->get('createdAccountId');

        if ($createdAccountId) {
            $account = $this->entityManager->getEntityById(Account::ENTITY_TYPE, $createdAccountId);

            if (!$account) {
                return;
            }

            $email->set('parentType', Account::ENTITY_TYPE);
            $email->set('parentId', $account->getId());

            return;
        }

        $createdContactId = $lead->get('createdContactId');

        if (
            $this->config->get('b2cMode') &&
            $createdContactId
        ) {
            $contact = $this->entityManager->getEntityById(Contact::ENTITY_TYPE, $createdContactId);

            if (!$contact) {
                return;
            }

            $email->set('parentType', Contact::ENTITY_TYPE);
            $email->set('parentId', $contact->getId());
        }
    }

    private function findParentByAddress(Email $email, string $emailAddress): bool
    {
        $contact = $this->entityManager
            ->getRDBRepository(Contact::ENTITY_TYPE)
            ->where([
                'emailAddress' => $emailAddress
            ])
            ->findOne();

        if ($contact) {
            if (!$this->config->get('b2cMode') && $contact->get('accountId')) {
                $email->set('parentType', Account::ENTITY_TYPE);
                $email->set('parentId', $contact->get('accountId'));

                return true;
            }

            $email->set('parentType', Contact::ENTITY_TYPE);
            $email->set('parentId', $contact->getId());

            return true;
        }

        $account = $this->entityManager
            ->getRDBRepository(Account::ENTITY_TYPE)
            ->where([
                'emailAddress' => $emailAddress
            ])
            ->findOne();

        if ($account) {
            $email->set('parentType', Account::ENTITY_TYPE);
            $email->set('parentId', $account->getId());

            return true;
        }

        $lead = $this->entityManager
            ->getRDBRepository(Lead::ENTITY_TYPE)
            ->where([
                'emailAddress' => $emailAddress
            ])
            ->findOne();

        if ($lead) {
            $email->set('parentType', Lead::ENTITY_TYPE);
            $email->set('parentId', $lead->getId());

            return true;
        }

        return false;
    }

    private function findDuplicate(Email $email): ?Email
    {
        return $this->duplicateFinder->find($email);
    }

    /**
     * @param string[] $userIdList
     * @param array<string,string> $folderData
     * @param string[] $teamIdList
     */
    private function processDuplicate(
        Email $duplicate,
        ?string $assignedUserId,
        array $userIdList,
        array $folderData,
        array $teamIdList
    ): void {

        /** @var EmailRepository $emailRepository */
        $emailRepository = $this->entityManager->getRDBRepository(Email::ENTITY_TYPE);

        if ($duplicate->getStatus() == Email::STATUS_ARCHIVED) {
            $emailRepository->loadFromField($duplicate);
            $emailRepository->loadToField($duplicate);
        }

        $duplicate->loadLinkMultipleField('users');

        /** @var string[] $fetchedUserIdList */
        $fetchedUserIdList = $duplicate->getLinkMultipleIdList('users');

        $duplicate->setLinkMultipleIdList('users', []);

        $processNoteAcl = false;

        if ($assignedUserId) {
            if (!in_array($assignedUserId, $fetchedUserIdList)) {
                $processNoteAcl = true;

                $duplicate->addLinkMultipleId('users', $assignedUserId);
            }

            $duplicate->addLinkMultipleId('assignedUsers', $assignedUserId);
        }

        foreach ($userIdList as $uId) {
            if (!in_array($uId, $fetchedUserIdList)) {
                $processNoteAcl = true;

                $duplicate->addLinkMultipleId('users', $uId);
            }
        }

        foreach ($folderData as $uId => $folderId) {
            if (!in_array($uId, $fetchedUserIdList)) {
                $duplicate->setLinkMultipleColumn('users', 'folderId', $uId, $folderId);

                continue;
            }

            $this->entityManager
                ->getRDBRepository(Email::ENTITY_TYPE)
                ->getRelation($duplicate, 'users')
                ->updateColumnsById($uId, ['folderId' => $folderId]);
        }

        $duplicate->set('isBeingImported', true);

        $emailRepository->applyUsersFilters($duplicate);

        $saverParams = SaverParams::create()->withRawOptions([
            'skipLinkMultipleRemove' => true,
            'skipLinkMultipleUpdate' => true,
        ]);

        $this->linkMultipleSaver->process($duplicate, 'users', $saverParams);
        $this->linkMultipleSaver->process($duplicate, 'assignedUsers', $saverParams);

        if ($this->emailNotificationsEnabled()) {
            $this->notificator->process(
                $duplicate,
                AssignmentNotificatorParams::create()->withRawOptions(['isBeingImported' => true])
            );
        }

        /** @var string[] $fetchedTeamIdList */
        $fetchedTeamIdList = $duplicate->getLinkMultipleIdList('teams');

        foreach ($teamIdList as $teamId) {
            if (!in_array($teamId, $fetchedTeamIdList)) {
                $processNoteAcl = true;

                $this->entityManager
                    ->getRDBRepository(Email::ENTITY_TYPE)
                    ->getRelation($duplicate, 'teams')
                    ->relateById($teamId);
            }
        }

        if ($duplicate->getParentType() && $processNoteAcl) {
            // Need to update acl fields (users and teams)
            // of notes related to the duplicate email.
            // To grant access to the user who received the email.

            $dt = new DateTime();

            $dt->modify('+5 seconds');

            $executeAt = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

            $job = $this->entityManager->getNewEntity(Job::ENTITY_TYPE);

            $job->set([
                'serviceName' => 'Stream',
                'methodName' => 'processNoteAclJob',
                'data' => [
                    'targetType' => Email::ENTITY_TYPE,
                    'targetId' => $duplicate->getId(),
                ],
                'executeAt' => $executeAt,
                'queue' => QueueName::Q1,
            ]);

            $this->entityManager->saveEntity($job);
        }
    }

    private function emailNotificationsEnabled(): bool
    {
        return in_array(
            Email::ENTITY_TYPE,
            $this->config->get('assignmentNotificationsEntityList') ?? []
        );
    }
}
