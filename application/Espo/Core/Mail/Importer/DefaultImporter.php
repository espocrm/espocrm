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
use Espo\Core\Mail\Parser;
use Espo\Core\Mail\ParserFactory;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Notification\AssignmentNotificator;
use Espo\Core\Notification\AssignmentNotificatorFactory;
use Espo\Core\Notification\AssignmentNotificator\Params as AssignmentNotificatorParams;
use Espo\Core\Utils\Config;
use Espo\Core\FieldProcessing\Relation\LinkMultipleSaver;
use Espo\Core\FieldProcessing\Saver\Params as SaverParams;
use Espo\Core\Job\QueueName;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Espo\Entities\EmailFilter;
use Espo\Entities\GroupEmailFolder;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\SelectBuilder;
use Espo\Repositories\Email as EmailRepository;
use Espo\ORM\EntityManager;
use Espo\Tools\Stream\Jobs\ProcessNoteAcl;

use DateTime;
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
        $parser = $this->getParser($message);

        $email = $this->entityManager->getRDBRepositoryByClass(Email::class)->getNew();
        $email->set('isBeingImported', true);

        $subject = $this->getSubject($parser, $message);

        $email
            ->setSubject($subject)
            ->setStatus(Email::STATUS_ARCHIVED)
            ->setIsHtml(false)
            ->setGroupFolderId($data->getGroupEmailFolderId())
            ->setTeams(LinkMultiple::create()->withAddedIdList($data->getTeamIdList()));

        if ($data->getAssignedUserId()) {
            $email->setAssignedUserId($data->getAssignedUserId());
            $email->addAssignedUserId($data->getAssignedUserId());
        }

        foreach ($data->getUserIdList() as $uId) {
            $email->addUserId($uId);
        }

        $this->setFromStrings($parser, $message, $email);
        $this->setAddresses($parser, $message, $email);

        foreach ($data->getFolderData() as $uId => $folderId) {
            $email->setUserColumnFolderId($uId, $folderId);
        }

        $toSkip = $this->processFilters($email, $data->getFilterList(), true);

        if ($toSkip) {
            return null;
        }

        $isSystemEmail = $this->processMessageId($parser, $message, $email);

        if ($isSystemEmail) {
            return null;
        }

        $this->processDate($parser, $message, $email);

        $duplicate = $this->findDuplicate($email, $message);

        if ($duplicate && $duplicate->getStatus() !== Email::STATUS_BEING_IMPORTED) {
            $this->entityManager->refreshEntity($duplicate);

            $this->processDuplicate($duplicate, $data, $email->getGroupFolder()?->getId());

            return $duplicate;
        }

        $this->processDeliveryDate($parser, $message, $email);

        if (!$email->getDateSent()) {
            $email->setDateSent(DateTimeField::createNow());
        }

        $inlineAttachmentList = [];

        if (!$data->fetchOnlyHeader()) {
            $inlineAttachmentList = $parser->getInlineAttachmentList($message, $email);

            $toSkip = $this->processFilters($email, $data->getFilterList());

            if ($toSkip) {
                return null;
            }
        } else {
            $email->setBody('Not fetched. The email size exceeds the limit.');
            $email->setIsHtml(false);
        }

        $this->processInReplyTo($parser, $message, $email);

        $parentFound = $this->parentFinder->find($email, $message);

        if ($parentFound) {
            $email->setParent($parentFound);
        }

        if (!$duplicate) {
            $this->entityManager->getLocker()->lockExclusive(Email::ENTITY_TYPE);

            $duplicate = $this->findDuplicate($email, $message);

            if ($duplicate) {
                $this->entityManager->getLocker()->rollback();

                if ($duplicate->getStatus() !== Email::STATUS_BEING_IMPORTED) {
                    $this->entityManager->refreshEntity($duplicate);

                    $this->processDuplicate($duplicate, $data, $email->getGroupFolder()?->getId());

                    return $duplicate;
                }
            }
        }

        if ($duplicate) {
            $this->copyAttributesToDuplicate($email, $duplicate);
            $this->getEmailRepository()->fillAccount($duplicate);

            $this->processDuplicate($duplicate, $data, $email->getGroupFolder()?->getId());

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

        $this->processFinalTransactionalSave($email);
        $this->processAttachmentSave($inlineAttachmentList, $email);

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
            'name' => $email->get(Field::NAME),
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
        $parentType = $email->get(Field::PARENT . 'Type');
        $parentId = $email->get(Field::PARENT . 'Id');

        if (!$parentId || !$parentType) {
            return;
        }

        $emailKeepParentTeamsEntityList = $this->config->get('emailKeepParentTeamsEntityList') ?? [];

        if (
            !in_array($parentType, $emailKeepParentTeamsEntityList) ||
            !$this->entityManager->hasRepository($parentType)
        ) {
            return;
        }

        $parent = $email->getParent();

        if (!$parent) {
            return;
        }

        if (!$parent instanceof CoreEntity) {
            return;
        }

        foreach ($parent->getLinkMultipleIdList(Field::TEAMS) as $parentTeamId) {
            $email->addTeamId($parentTeamId);
        }
    }

    private function findDuplicate(Email $email, Message $message): ?Email
    {
        return $this->duplicateFinder->find($email, $message);
    }

    private function processDuplicate(Email $email, Data $data, ?string $groupFolderId): void
    {
        $assignedUserId = $data->getAssignedUserId();

        if ($email->getStatus() === Email::STATUS_ARCHIVED) {
            $this->getEmailRepository()->loadFromField($email);
            $this->getEmailRepository()->loadToField($email);
        }

        $fetchedTeamIds = $email->getTeams()->getIdList();
        $fetchedUserIds = $email->getUsers()->getIdList();
        $fetchedAssignedUserIds = $email->getAssignedUsers()->getIdList();

        $email->setLinkMultipleIdList('users', []);
        $email->setLinkMultipleIdList(Field::TEAMS, []);
        $email->setLinkMultipleIdList(Field::ASSIGNED_USERS, []);

        $processNoteAcl = false;

        if ($assignedUserId) {
            if (!in_array($assignedUserId, $fetchedUserIds)) {
                $processNoteAcl = true;

                $email->addUserId($assignedUserId);
            }

            if (!in_array($assignedUserId, $fetchedAssignedUserIds)) {
                $email->addAssignedUserId($assignedUserId);
            }
        }

        foreach ($data->getUserIdList() as $uId) {
            if (!in_array($uId, $fetchedUserIds)) {
                $processNoteAcl = true;

                $email->addUserId($uId);
            }
        }

        foreach ($data->getFolderData() as $uId => $folderId) {
            if (!in_array($uId, $fetchedUserIds)) {
                $email->setUserColumnFolderId($uId, $folderId);

                continue;
            }

            // Can cause skip-notification bypass. @todo Revise.
            $this->entityManager
                ->getRelation($email, 'users')
                ->updateColumnsById($uId, [Email::USERS_COLUMN_FOLDER_ID => $folderId]);
        }

        $email->set('isBeingImported', true);

        $this->getEmailRepository()->applyUsersFilters($email);

        if ($groupFolderId && !$email->getGroupFolder()) {
            $this->relateWithGroupFolder($email, $groupFolderId);

            $addedFromFolder = $this->applyGroupFolder(
                $email,
                $groupFolderId,
                $fetchedUserIds,
                $fetchedTeamIds
            );

            if ($addedFromFolder) {
                $processNoteAcl = true;
            }
        }

        foreach ($data->getTeamIdList() as $teamId) {
            if (!in_array($teamId, $fetchedTeamIds)) {
                $processNoteAcl = true;

                $email->addTeamId($teamId);
            }
        }

        $saverParams = SaverParams::create()->withRawOptions([
            'skipLinkMultipleRemove' => true,
            'skipLinkMultipleUpdate' => true,
        ]);

        $this->linkMultipleSaver->process($email, 'users', $saverParams);
        $this->linkMultipleSaver->process($email, Field::ASSIGNED_USERS, $saverParams);
        $this->linkMultipleSaver->process($email, Field::TEAMS, $saverParams);

        if ($this->notificationsEnabled()) {
            $notificatorParams = AssignmentNotificatorParams::create()
                ->withRawOptions([Email::SAVE_OPTION_IS_BEING_IMPORTED => true]);

            $this->notificator->process($email, $notificatorParams);
        }

        $email->set('isBeingImported', false);
        $email->clear('teamsIds');
        $email->clear('usersIds');
        $email->clear('assignedUsersIds');

        $email->setAsFetched();

        if ($email->getParentType() && $processNoteAcl) {
            $this->scheduleAclJob($email);
        }
    }

    private function notificationsEnabled(): bool
    {
        return in_array(
            Email::ENTITY_TYPE,
            $this->config->get('assignmentNotificationsEntityList') ?? []
        );
    }

    private function getSubject(Parser $parser, Message $message): string
    {
        $subject = '';

        if ($parser->hasHeader($message, 'subject')) {
            $subject = $parser->getHeader($message, 'subject');
        }

        if (!empty($subject)) {
            $subject = trim($subject);
        }

        if ($subject !== '0' && empty($subject)) {
            $subject = '(No Subject)';
        }

        if (strlen($subject) > self::SUBJECT_MAX_LENGTH) {
            $subject = substr($subject, 0, self::SUBJECT_MAX_LENGTH);
        }

        return $subject;
    }

    private function setFromStrings(Parser $parser, Message $message, Email $email): void
    {
        $fromAddressData = $parser->getAddressData($message, 'from');

        if ($fromAddressData) {
            $namePart = ($fromAddressData->name ? ($fromAddressData->name . ' ') : '');

            $email->set('fromString', "$namePart<$fromAddressData->address>");
        }

        $replyToData = $parser->getAddressData($message, 'reply-To');

        if ($replyToData) {
            $namePart = ($replyToData->name ? ($replyToData->name . ' ') : '');

            $email->set('replyToString', "$namePart<$replyToData->address>");
        }
    }

    private function setAddresses(Parser $parser, Message $message, Email $email): void
    {
        $from = $parser->getAddressList($message, 'from');
        $to = $parser->getAddressList($message, 'to');
        $cc = $parser->getAddressList($message, 'cc');
        $replyTo = $parser->getAddressList($message, 'reply-To');

        $email->setFromAddress($from[0] ?? null);
        $email->setToAddressList($to);
        $email->setCcAddressList($cc);
        $email->setReplyToAddressList($replyTo);

        $email->set('addressNameMap', $parser->getAddressNameMap($message));
    }

    /**
     * @return bool True if an email is system.
     */
    private function processMessageId(Parser $parser, Message $message, Email $email): bool
    {
        if (!$parser->hasHeader($message, 'message-Id')) {
            return false;
        }

        $messageId = $parser->getMessageId($message);

        if (!$messageId) {
            return false;
        }

        $email->setMessageId($messageId);

        if ($parser->hasHeader($message, 'delivered-To')) {
            $deliveredTo = $parser->getHeader($message, 'delivered-To') ?? '';

            $email->set('messageIdInternal', "$messageId-$deliveredTo");
        }

        if (stripos($messageId, '@espo-system') !== false) {
            return true;
        }

        return false;
    }

    private function processDate(Parser $parser, Message $message, Email $email): void
    {
        if (!$parser->hasHeader($message, 'date')) {
            return;
        }

        $dateString = $parser->getHeader($message, 'date') ?? 'now';

        try {
            $dateSent = DateTimeField::fromDateTime(new DateTime($dateString));
        } catch (Exception) {
            return;
        }

        $email->setDateSent($dateSent);
    }

    private function processDeliveryDate(Parser $parser, Message $message, Email $email): void
    {
        if (!$parser->hasHeader($message, 'delivery-Date')) {
            return;
        }

        $dateString = $parser->getHeader($message, 'delivery-Date') ?? 'now';

        try {
            $deliveryDate = DateTimeField::fromDateTime(new DateTime($dateString));
        } catch (Exception) {
            return;
        }

        $email->setDeliveryDate($deliveryDate);
    }

    private function processInReplyTo(Parser $parser, Message $message, Email $email): void
    {
        if (!$parser->hasHeader($message, 'in-Reply-To')) {
            return;
        }

        $stringValue = $parser->getHeader($message, 'in-Reply-To');

        if (!$stringValue) {
            return;
        }

        $values = explode(' ', $stringValue);

        $inReplyTo = $values[0] ?? null;

        if (!$inReplyTo) {
            return;
        }

        if ($inReplyTo[0] !== '<') {
            $inReplyTo = "<$inReplyTo>";
        }

        $replied = $this->entityManager
            ->getRDBRepositoryByClass(Email::class)
            ->where(['messageId' => $inReplyTo])
            ->findOne();

        if (!$replied) {
            return;
        }

        $email->setReplied($replied);

        foreach ($replied->getTeams()->getIdList() as $teamId) {
            $email->addTeamId($teamId);
        }
    }

    /**
     * @param iterable<EmailFilter> $filterList
     * @return bool True if to skip.
     */
    private function processFilters(Email $email, iterable $filterList, bool $skipBody = false): bool
    {
        $matchedFilter = $this->filtersMatcher->findMatch($email, $filterList, $skipBody);

        if (!$matchedFilter) {
            return false;
        }

        if ($matchedFilter->getAction() === EmailFilter::ACTION_SKIP) {
            return true;
        }

        if (
            $matchedFilter->getAction() === EmailFilter::ACTION_MOVE_TO_GROUP_FOLDER &&
            $matchedFilter->getGroupEmailFolderId()
        ) {
            $this->applyGroupFolder($email, $matchedFilter->getGroupEmailFolderId());
        }

        return false;
    }

    private function processFinalTransactionalSave(Email $email): void
    {
        $this->entityManager->getTransactionManager()->start();

        $this->entityManager
            ->getRDBRepositoryByClass(Email::class)
            ->forUpdate()
            ->where([Attribute::ID => $email->getId()])
            ->findOne();

        $this->entityManager->saveEntity($email, [Email::SAVE_OPTION_IS_BEING_IMPORTED => true]);

        $this->entityManager->getTransactionManager()->commit();
    }

    /**
     * @param Attachment[] $inlineAttachmentList
     */
    private function processAttachmentSave(array $inlineAttachmentList, Email $email): void
    {
        foreach ($inlineAttachmentList as $attachment) {
            $attachment->setTargetField('body');
            $attachment->setRelated(LinkParent::createFromEntity($email));

            $this->entityManager->saveEntity($attachment);
        }
    }

    private function getParser(Message $message): Parser
    {
        return $message instanceof MessageWrapper ?
            ($message->getParser() ?? $this->parserFactory->create()) :
            $this->parserFactory->create();
    }

    private function getEmailRepository(): EmailRepository
    {
        /** @var EmailRepository */
        return $this->entityManager->getRDBRepositoryByClass(Email::class);
    }

    private function relateWithGroupFolder(Email $email, string $groupFolderId): void
    {
        $this->entityManager
            ->getRelation($email, 'groupFolder')
            ->relateById($groupFolderId);
    }

    /**
     * @param string[] $fetchedUserIds
     * @param string[] $fetchedTeamIds
     */
    private function applyGroupFolder(
        Email $email,
        string $groupFolderId,
        array $fetchedUserIds = [],
        array $fetchedTeamIds = [],
    ): bool {

        $email->setGroupFolderId($groupFolderId);

        $groupFolder = $this->entityManager
            ->getRDBRepositoryByClass(GroupEmailFolder::class)
            ->getById($groupFolderId);

        if (!$groupFolder || !$groupFolder->getTeams()->getCount()) {
            return false;
        }

        $added = false;

        foreach ($groupFolder->getTeams()->getIdList() as $teamId) {
            if (!in_array($teamId, $fetchedTeamIds)) {
                $added = true;

                $email->addTeamId($teamId);
            }
        }

        $users = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->select([Attribute::ID])
            ->where([
                'type' => [User::TYPE_REGULAR, User::TYPE_ADMIN],
                'isActive' => true,
                Attribute::ID . '!=' => $fetchedUserIds,
            ])
            ->where(
                Condition::in(
                    Expression::column(Attribute::ID),
                    SelectBuilder::create()
                        ->from(Team::RELATIONSHIP_TEAM_USER)
                        ->select('userId')
                        ->where(['teamId' => $groupFolder->getTeams()->getIdList()])
                        ->build()
                )
            )
            ->find();

        foreach ($users as $user) {
            $added = true;

            $email->addUserId($user->getId());
        }

        return $added;
    }

    private function scheduleAclJob(Email $email): void
    {
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
                    ->withTargetId($email->getId())
                    ->withTargetType(Email::ENTITY_TYPE)
            )
            ->setQueue(QueueName::Q1)
            ->setTime($dt)
            ->schedule();
    }
}
