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

namespace Espo\Core\Mail\Importer;

use Espo\Core\Field\DateTime as DateTimeField;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Field\LinkParent;
use Espo\Core\Job\Job\Data as JobData;
use Espo\Core\Job\JobSchedulerFactory;
use Espo\Core\Mail\FiltersMatcher;
use Espo\Core\Mail\Importer;
use Espo\Core\Mail\Message;
use Espo\Core\Mail\MessageWrapper;
use Espo\Core\Mail\ParserFactory;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Notification\AssignmentNotificator;
use Espo\Core\Notification\AssignmentNotificatorFactory;
use Espo\Core\Notification\AssignmentNotificator\Params as AssignmentNotificatorParams;
use Espo\Core\Utils\Config;
use Espo\Core\FieldProcessing\Relation\LinkMultipleSaver;
use Espo\Core\FieldProcessing\Saver\Params as SaverParams;
use Espo\Core\Job\QueueName;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Entities\Email;
use Espo\Entities\EmailFilter;
use Espo\Repositories\Email as EmailRepository;
use Espo\ORM\EntityManager;
use Espo\Tools\Stream\Jobs\ProcessNoteAcl;

use DateTime;
use DateTimeZone;
use Exception;

class DefaultImporter implements Importer
{
    private const SUBJECT_MAX_LENGTH = 255;
    private const PROCESS_ACL_DELAY_PERIOD = '5 seconds';

    /** @var AssignmentNotificator<Email>  */
    private AssignmentNotificator $notificator;
    private FiltersMatcher $filtersMatcher;

    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        AssignmentNotificatorFactory $notificatorFactory,
        private ParserFactory $parserFactory,
        private LinkMultipleSaver $linkMultipleSaver,
        private DuplicateFinder $duplicateFinder,
        private JobSchedulerFactory $jobSchedulerFactory,
        private ParentFinder $parentFinder
    ) {
        $this->notificator = $notificatorFactory->createByClass(Email::class);
        $this->filtersMatcher = new FiltersMatcher();
    }

    public function import(Message $message, Data $data): ?Email
    {
        $assignedUserId = $data->getAssignedUserId();
        $teamIdList = $data->getTeamIdList();
        $userIdList = $data->getUserIdList();
        $filterList = $data->getFilterList();
        $folderData = $data->getFolderData();
        $groupEmailFolderId = $data->getGroupEmailFolderId();

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

        $email->setSubject($subject);
        $email->setStatus(Email::STATUS_ARCHIVED);
        $email->setIsHtml(false);
        $email->setGroupFolderId($groupEmailFolderId);
        $email->setTeams(LinkMultiple::create()->withAddedIdList($teamIdList));
        //$email->set('attachmentsIds', []);

        if ($assignedUserId) {
            $email->setAssignedUserId($assignedUserId);
            $email->addAssignedUserId($assignedUserId);
        }

        foreach ($userIdList as $uId) {
            $email->addUserId($uId);
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

        $email->setFromAddress($fromArr[0] ?? null);
        $email->setToAddressList($toArr);
        $email->setCcAddressList($ccArr);
        $email->setReplyToAddressList($replyToArr);

        $addressNameMap = $parser->getAddressNameMap($message);

        $email->set('addressNameMap', $addressNameMap);

        foreach ($folderData as $uId => $folderId) {
            $email->setLinkMultipleColumn('users', Email::USERS_COLUMN_FOLDER_ID, $uId, $folderId);
        }

        $matchedFilter = $this->filtersMatcher->findMatch($email, $filterList, true);

        if ($matchedFilter && $matchedFilter->getAction() === EmailFilter::ACTION_SKIP) {
            return null;
        }

        if ($matchedFilter && $matchedFilter->getAction() === EmailFilter::ACTION_MOVE_TO_GROUP_FOLDER) {
            $groupEmailFolderId = $matchedFilter->getGroupEmailFolderId();

            $email->setGroupFolderId($groupEmailFolderId);
        }

        if (
            $parser->hasHeader($message, 'message-Id') &&
            $parser->getHeader($message, 'message-Id')
        ) {
            /** @var string $messageId */
            $messageId = $parser->getMessageId($message);

            $email->setMessageId($messageId);

            if ($parser->hasHeader($message, 'delivered-To')) {
                $messageIdInternal = $messageId . '-' . $parser->getHeader($message, 'delivered-To');

                $email->set('messageIdInternal', $messageIdInternal);
            }

            if (stripos($messageId, '@espo-system') !== false) {
                return null;
            }
        }

        if ($parser->hasHeader($message, 'date')) {
            try {
                $dateHeaderValue = $parser->getHeader($message, 'date') ?? 'now';

                $dateSent = new DateTime($dateHeaderValue);

                $email->setDateSent(DateTimeField::fromDateTime($dateSent));
            }
            catch (Exception) {}
        }

        $duplicate = $this->findDuplicate($email, $message);

        if ($duplicate && $duplicate->getStatus() !== Email::STATUS_BEING_IMPORTED) {
            $this->entityManager->refreshEntity($duplicate);

            $this->processDuplicate(
                $duplicate,
                $assignedUserId,
                $userIdList,
                $folderData,
                $teamIdList,
                $groupEmailFolderId
            );

            return $duplicate;
        }

        if (!$email->getDateSent()) {
            $email->setDateSent(DateTimeField::createNow());
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
            catch (Exception) {}
        }

        $inlineAttachmentList = [];

        if (!$data->fetchOnlyHeader()) {
            $inlineAttachmentList = $parser->getInlineAttachmentList($message, $email);

            $matchedFilter = $this->filtersMatcher->findMatch($email, $filterList);

            if ($matchedFilter && $matchedFilter->getAction() === EmailFilter::ACTION_SKIP) {
                return null;
            }

            if ($matchedFilter && $matchedFilter->getAction() === EmailFilter::ACTION_MOVE_TO_GROUP_FOLDER) {
                $groupEmailFolderId = $matchedFilter->getGroupEmailFolderId();

                $email->setGroupFolderId($groupEmailFolderId);
            }
        }
        else {
            $email->setBody('Not fetched. The email size exceeds the limit.');
            $email->setIsHtml(false);
        }

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

                /** @var ?Email $replied */
                $replied = $this->entityManager
                    ->getRDBRepository(Email::ENTITY_TYPE)
                    ->where(['messageId' => $inReplyTo])
                    ->findOne();

                if ($replied) {
                    $email->setRepliedId($replied->getId());

                    $repliedTeamIdList = $replied->getLinkMultipleIdList('teams');

                    foreach ($repliedTeamIdList as $repliedTeamId) {
                        $email->addTeamId($repliedTeamId);
                    }
                }
            }
        }

        $parentFound = $this->parentFinder->find($email, $message);

        if ($parentFound) {
            $email->setParent(LinkParent::create($parentFound->getEntityType(), $parentFound->getId()));
        }

        if (!$duplicate) {
            $this->entityManager->getLocker()->lockExclusive(Email::ENTITY_TYPE);

            $duplicate = $this->findDuplicate($email, $message);

            if ($duplicate) {
                $this->entityManager->getLocker()->rollback();

                if ($duplicate->getStatus() !== Email::STATUS_BEING_IMPORTED) {
                    $this->entityManager->refreshEntity($duplicate);

                    $this->processDuplicate(
                        $duplicate,
                        $assignedUserId,
                        $userIdList,
                        $folderData,
                        $teamIdList,
                        $groupEmailFolderId
                    );

                    return $duplicate;
                }
            }
        }

        if ($duplicate) {
            $this->copyAttributesToDuplicate($email, $duplicate);

            /** @var EmailRepository $emailRepository */
            $emailRepository = $this->entityManager->getRDBRepository(Email::ENTITY_TYPE);

            $emailRepository->fillAccount($duplicate);

            $this->processDuplicate(
                $duplicate,
                $assignedUserId,
                $userIdList,
                $folderData,
                $teamIdList,
                $groupEmailFolderId
            );

            return $duplicate;
        }

        if (!$email->getMessageId()) {
            $email->setDummyMessageId();
        }

        $email->setStatus(Email::STATUS_BEING_IMPORTED);

        $this->entityManager->saveEntity($email, [
            SaveOption::SKIP_ALL => true,
            SaveOption::KEEP_NEW => true,
        ]);

        $this->entityManager->getLocker()->commit();

        if ($parentFound) {
            $this->processEmailWithParent($email);
        }

        $email->setStatus(Email::STATUS_ARCHIVED);

        $this->entityManager->getTransactionManager()->start();
        $this->entityManager
            ->getRDBRepositoryByClass(Email::class)
            ->forUpdate()
            ->where(['id' => $email->getId()])
            ->findOne();

        $this->entityManager->saveEntity($email, ['isBeingImported' => true]);

        $this->entityManager->getTransactionManager()->commit();

        foreach ($inlineAttachmentList as $attachment) {
            $attachment->setTargetField('body');
            $attachment->setRelated(LinkParent::create(Email::ENTITY_TYPE, $email->getId()));

            $this->entityManager->saveEntity($attachment);
        }

        return $email;
    }

    private function copyAttributesToDuplicate(Email $email, Email $duplicate): void
    {
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

        $parent = $this->entityManager->getEntityById($parentType, $parentId);

        if (!$parent) {
            return;
        }

        if (!$parent instanceof CoreEntity) {
            return;
        }

        foreach ($parent->getLinkMultipleIdList('teams') as $parentTeamId) {
            $email->addTeamId($parentTeamId);
        }
    }

    private function findDuplicate(Email $email, Message $message): ?Email
    {
        return $this->duplicateFinder->find($email, $message);
    }

    /**
     * @param string[] $userIdList
     * @param array<string, string> $folderData
     * @param string[] $teamIdList
     */
    private function processDuplicate(
        Email $duplicate,
        ?string $assignedUserId,
        array $userIdList,
        array $folderData,
        array $teamIdList,
        ?string $groupEmailFolderId
    ): void {

        /** @var EmailRepository $emailRepository */
        $emailRepository = $this->entityManager->getRDBRepository(Email::ENTITY_TYPE);

        if ($duplicate->getStatus() === Email::STATUS_ARCHIVED) {
            $emailRepository->loadFromField($duplicate);
            $emailRepository->loadToField($duplicate);
        }

        $duplicate->loadLinkMultipleField('users');
        $fetchedUserIdList = $duplicate->getLinkMultipleIdList('users');

        $duplicate->setLinkMultipleIdList('users', []);

        $processNoteAcl = false;

        if ($assignedUserId) {
            if (!in_array($assignedUserId, $fetchedUserIdList)) {
                $processNoteAcl = true;

                $duplicate->addUserId($assignedUserId);
            }

            $duplicate->addAssignedUserId($assignedUserId);
        }

        foreach ($userIdList as $uId) {
            if (!in_array($uId, $fetchedUserIdList)) {
                $processNoteAcl = true;

                $duplicate->addUserId($uId);
            }
        }

        foreach ($folderData as $uId => $folderId) {
            if (!in_array($uId, $fetchedUserIdList)) {
                $duplicate->setLinkMultipleColumn('users', Email::USERS_COLUMN_FOLDER_ID, $uId, $folderId);

                continue;
            }

            $this->entityManager
                ->getRDBRepository(Email::ENTITY_TYPE)
                ->getRelation($duplicate, 'users')
                // Can cause skip-notification bypass.
                ->updateColumnsById($uId, [Email::USERS_COLUMN_FOLDER_ID => $folderId]);
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

        if ($groupEmailFolderId && !$duplicate->getGroupFolder()) {
            $this->entityManager
                ->getRDBRepository(Email::ENTITY_TYPE)
                ->getRelation($duplicate, 'groupFolder')
                ->relateById($groupEmailFolderId);
        }

        if ($duplicate->getParentType() && $processNoteAcl) {
            // Need to update acl fields (users and teams)
            // of notes related to the duplicate email.
            // To grant access to the user who received the email.

            $dt = new DateTime();
            $dt->modify('+' . self::PROCESS_ACL_DELAY_PERIOD);

            $this->jobSchedulerFactory
                ->create()
                ->setClassName(ProcessNoteAcl::class)
                ->setData(
                    JobData::create(['notify' => true])
                        ->withTargetId($duplicate->getId())
                        ->withTargetType(Email::ENTITY_TYPE)
                )
                ->setQueue(QueueName::Q1)
                ->setTime($dt)
                ->schedule();
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
