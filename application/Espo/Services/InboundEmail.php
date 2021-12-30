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

namespace Espo\Services;

use Laminas\Mail\Storage;
use Laminas\Mail\Message;

use Espo\ORM\Entity;

use Espo\Modules\Crm\Business\Distribution\CaseObj\RoundRobin;
use Espo\Modules\Crm\Business\Distribution\CaseObj\LeastBusy;

use Espo\Services\EmailTemplate as EmailTemplateService;
use Espo\Modules\Crm\Services\Campaign as CampaignService;

use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Repositories\EmailAddress as EmailAddressRepository;

use Espo\Core\{
    Exceptions\Error,
    Exceptions\BadRequest,
    Mail\Importer,
    Mail\ImporterData,
    Mail\MessageWrapper,
    Mail\Mail\Storage\Imap,
    Mail\Parser,
    Mail\ParserFactory,
};

use Espo\Services\{
    Email as EmailService,
    Record as RecordService,
};

use Espo\Entities\{
    Team,
    InboundEmail as InboundEmailEntity,
    User,
    Email as EmailEntity,
};

use Espo\Modules\Crm\Entities\CaseObj as CaseEntity;

use Espo\Core\Di;

use RecursiveIteratorIterator;
use Exception;
use Throwable;
use DateTime;
use DateTimeZone;

class InboundEmail extends RecordService implements

    Di\CryptAware,
    Di\EmailSenderAware
{
    use Di\CryptSetter;
    use Di\EmailSenderSetter;

    private $campaignService = null;

    protected $storageClassName = Imap::class;

    private const DEFAULT_AUTOREPLY_SUPPRESS_PERIOD = '2 hours';

    private const DEFAULT_AUTOREPLY_LIMIT = 5;

    protected const PORTION_LIMIT = 20;

    protected function getCrypt()
    {
        return $this->crypt;
    }

    protected function filterInput($data)
    {
        parent::filterInput($data);

        if (property_exists($data, 'password')) {
            $data->password = $this->getCrypt()->encrypt($data->password);
        }

        if (property_exists($data, 'smtpPassword')) {
            $data->smtpPassword = $this->getCrypt()->encrypt($data->smtpPassword);
        }
    }

    public function processValidation(Entity $entity, $data)
    {
        parent::processValidation($entity, $data);

        if ($entity->get('useImap')) {
            if (!$entity->get('fetchSince')) {
                throw new BadRequest("EmailAccount validation: fetchSince is required.");
            }
        }
    }

    public function getFolders($params): array
    {
        if (!empty($params['id'])) {
            $account = $this->entityManager->getEntity('InboundEmail', $params['id']);

            if ($account) {
                $params['password'] = $this->getCrypt()->decrypt($account->get('password'));
                $params['imapHandler'] = $account->get('imapHandler');
            }
        }

        $foldersArr = [];

        $storage = $this->createStorage($params);

        $folders = new RecursiveIteratorIterator($storage->getFolders(), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($folders as $folder) {
            $foldersArr[] = mb_convert_encoding($folder->getGlobalName(), 'UTF-8', 'UTF7-IMAP');
        }

        return $foldersArr;
    }

    public function testConnection(array $params)
    {
        if (!empty($params['id'])) {
            $account = $this->entityManager->getEntity('InboundEmail', $params['id']);

            if ($account) {
                $params['imapHandler'] = $account->get('imapHandler');
            }
        }

        $storage = $this->createStorage($params);

        if ($storage->getFolders()) {
            return true;
        }

        throw new Error();
    }

    protected function createParser(): Parser
    {
        return $this->injectableFactory->create(ParserFactory::class)->create();
    }

    protected function createImporter(): Importer
    {
        return $this->injectableFactory->create(Importer::class);
    }

    public function fetchFromMailServer(InboundEmailEntity $emailAccount)
    {
        if ($emailAccount->get('status') != 'Active' || !$emailAccount->get('useImap')) {
            throw new Error("Group Email Account {$emailAccount->getId()} is not active.");
        }

        $importer = $this->createImporter();

        $teamId = $emailAccount->get('teamId');

        $userId = null;

        if ($emailAccount->get('assignToUserId')) {
            $userId = $emailAccount->get('assignToUserId');
        }

        $userIdList = [];

        $teamIdList = $emailAccount->getLinkMultipleIdList('teams');

        if (!empty($teamIdList)) {
            if ($emailAccount->get('addAllTeamUsers')) {
                $userList = $this->entityManager
                    ->getRDBRepository('User')
                    ->select(['id'])
                    ->distinct()
                    ->join('teams')
                    ->where([
                        'isActive' => true,
                        'teamsMiddle.teamId' => $teamIdList,
                    ])
                    ->find();

                foreach ($userList as $user) {
                    $userIdList[] = $user->getId();
                }
            }
        }

        if (!empty($teamId)) {
            $teamIdList[] = $teamId;
        }

        $filterCollection = $this->entityManager
            ->getRDBRepository('EmailFilter')
            ->where([
                'action' => 'Skip',
                'OR' => [
                    [
                        'parentType' => $emailAccount->getEntityType(),
                        'parentId' => $emailAccount->getId(),
                    ],
                    [
                        'parentId' => null,
                    ],
                ],
            ])
            ->find();

        $fetchData = $emailAccount->get('fetchData');

        if (empty($fetchData)) {
            $fetchData = (object) [];
        }

        $fetchData = clone $fetchData;

        if (!property_exists($fetchData, 'lastUID')) {
            $fetchData->lastUID = (object) [];
        }

        if (!property_exists($fetchData, 'lastDate')) {
            $fetchData->lastDate = (object) [];
        }

        if (!property_exists($fetchData, 'byDate')) {
            $fetchData->byDate = (object) [];
        }

        $fetchData->lastUID = clone $fetchData->lastUID;
        $fetchData->lastDate = clone $fetchData->lastDate;
        $fetchData->byDate = clone $fetchData->byDate;

        $storage = $this->getStorage($emailAccount);

        $monitoredFolders = $emailAccount->get('monitoredFolders');

        if (empty($monitoredFolders)) {
            $monitoredFolders = ['INBOX'];
        }

        foreach ($monitoredFolders as $folder) {
            $folder = mb_convert_encoding($folder, 'UTF7-IMAP', 'UTF-8');

            $portionLimit = $this->config->get('inboundEmailMaxPortionSize', self::PORTION_LIMIT);

            try {
                $storage->selectFolder($folder);
            }
            catch (Exception $e) {
                $this->log->error(
                    'InboundEmail '.$emailAccount->getId().' (Select Folder) [' . $e->getCode() . '] ' .
                    $e->getMessage()
                );

                continue;
            }

            $lastUID = 0;
            $lastDate = 0;

            if (!empty($fetchData->lastUID->$folder)) {
                $lastUID = $fetchData->lastUID->$folder;
            }

            if (!empty($fetchData->lastDate->$folder)) {
                $lastDate = $fetchData->lastDate->$folder;
            }

            $forceByDate = !empty($fetchData->byDate->$folder);

            if ($forceByDate) {
                $portionLimit = 0;
            }

            $previousLastUID = $lastUID;

            if (!empty($lastUID) && !$forceByDate) {
                $idList = $storage->getIdsFromUID($lastUID);
            }
            else {
                $fetchSince = $emailAccount->get('fetchSince');

                if ($lastDate) {
                    $fetchSince = $lastDate;
                }

                $dt = null;

                try {
                    $dt = new DateTime($fetchSince);
                }
                catch (Exception $e) {}

                if ($dt) {
                    $idList = $storage->getIdsFromDate($dt->format('d-M-Y'));
                }
                else {
                    return false;
                }
            }

            if ((count($idList) == 1) && !empty($lastUID)) {
                if ($storage->getUniqueId($idList[0]) == $lastUID) {
                    continue;
                }
            }

            $k = 0;

            foreach ($idList as $i => $id) {
                if ($k == count($idList) - 1) {
                    $lastUID = $storage->getUniqueId($id);
                }

                if ($forceByDate && $previousLastUID) {
                    $uid = $storage->getUniqueId($id);

                    if ($uid <= $previousLastUID) {
                        $k++;

                        continue;
                    }
                }

                $fetchOnlyHeader = $this->checkFetchOnlyHeader($storage, $id);

                $message = null;
                $email = null;

                $isAutoReply = null;
                $skipAutoReply = null;

                try {
                    $toSkip = false;

                    $parser = $this->createParser();

                    $message = new MessageWrapper($storage, $id, $parser);

                    if ($message->hasAttribute('from')) {
                        $fromString = $message->getAttribute('from');

                        if (preg_match('/MAILER-DAEMON|POSTMASTER/i', $fromString)) {
                            try {
                                $toSkip = $this->processBouncedMessage($message);
                            }
                            catch (Throwable $e) {
                                $this->log->error(
                                    'InboundEmail ' . $emailAccount->getId() .
                                    ' (Process Bounced Message: [' . $e->getCode() . '] ' .$e->getMessage()
                                );
                            }
                        }
                    }

                    $skipAutoReply = $this->checkMessageCannotBeAutoReplied($message);

                    $isAutoReply = $this->checkMessageIsAutoReply($message);

                    $flags = null;

                    if (!$toSkip) {
                        if ($message->isFetched() && $emailAccount->get('keepFetchedEmailsUnread')) {
                            $flags = $message->getFlags();
                        }

                        $importerData = ImporterData
                            ::create()
                            ->withAssignedUserId($userId)
                            ->withTeamIdList($teamIdList)
                            ->withUserIdList($userIdList)
                            ->withFilterList($filterCollection)
                            ->withFetchOnlyHeader($fetchOnlyHeader);

                        $email = $this->importMessage(
                            $importer,
                            $emailAccount,
                            $message,
                            $importerData
                        );

                        if ($emailAccount->get('keepFetchedEmailsUnread')) {
                            if (
                                is_array($flags) &&
                                empty($flags[Storage::FLAG_SEEN])
                            ) {
                                unset($flags[Storage::FLAG_RECENT]);

                                $storage->setFlags($id, $flags);
                            }
                        }
                    }
                }
                catch (Throwable $e) {
                    $this->log->error(
                        'InboundEmail '.$emailAccount->getId().
                        ' (Get Message): [' . $e->getCode() . '] ' .$e->getMessage()
                    );
                }

                try {
                    if (!empty($email)) {
                        if (!$emailAccount->get('createCase')) {
                            if (!$email->isFetched()) {
                                $this->noteAboutEmail($email);
                            }
                        }

                        $this->entityManager
                            ->getRDBRepository('InboundEmail')
                            ->getRelation($emailAccount, 'emails')
                            ->relate($email);

                        if ($emailAccount->get('createCase')) {
                            if ($email->isFetched()) {
                                $email = $this->entityManager->getEntity('Email', $email->getId());
                            }
                            else {
                                $email->updateFetchedValues();
                            }

                            if ($email && !$isAutoReply) {
                                $this->createCase($emailAccount, $email);
                            }
                        }
                        else {
                            if ($emailAccount->get('reply') && !$skipAutoReply) {
                                $user = $this->entityManager->getEntity('User', $userId);

                                $this->autoReply($emailAccount, $email, null, $user);
                            }
                        }
                    }
                }
                catch (Exception $e) {
                    $this->log->error(
                        'InboundEmail '.$emailAccount->getId().' (Post Import Logic): [' . $e->getCode() . '] ' .
                        $e->getMessage()
                    );
                }

                if ($k === count($idList) - 1 || $k === $portionLimit - 1) {
                    $lastUID = $storage->getUniqueId($id);

                    if ($email && $email->get('dateSent')) {
                        $dt = null;

                        try {
                            $dt = new DateTime($email->get('dateSent'));
                        } catch (Exception $e) {}

                        if ($dt) {
                            $nowDt = new DateTime();

                            if ($dt->getTimestamp() >= $nowDt->getTimestamp()) {
                                $dt = $nowDt;
                            }

                            $dateSent = $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                            $lastDate = $dateSent;
                        }
                    }

                    break;
                }

                $k++;
            }

            if ($forceByDate) {
                $nowDt = new DateTime();

                $lastDate = $nowDt->format('Y-m-d H:i:s');
            }

            $fetchData->lastDate->$folder = $lastDate;
            $fetchData->lastUID->$folder = $lastUID;

            if ($forceByDate) {
                if ($previousLastUID) {
                    $idList = $storage->getIdsFromUID($previousLastUID);

                    if (count($idList)) {
                        $uid1 = $storage->getUniqueId($idList[0]);

                        if ($uid1 && $uid1 > $previousLastUID) {
                            unset($fetchData->byDate->$folder);
                        }
                    }
                }
            } else {
                if ($previousLastUID && count($idList) && $previousLastUID >= $lastUID) {
                     $fetchData->byDate->$folder = true;
                }
            }

            $emailAccount->set('fetchData', $fetchData);

            $this->entityManager->saveEntity($emailAccount, ['silent' => true]);
        }

        $storage->close();

        return true;
    }

    protected function importMessage(
        Importer $importer,
        InboundEmailEntity $emailAccount,
        MessageWrapper $message,
        ImporterData $data
    ): ?EmailEntity {

        try {
            return $importer->import($message, $data);
        }
        catch (Throwable $e) {
            $this->log->error(
                'InboundEmail '.$emailAccount->getId().' (Import Message): [' . $e->getCode() . '] ' .
                $e->getMessage()
            );

            if ($this->entityManager->getLocker()->isLocked()) {
                $this->entityManager->getLocker()->rollback();
            }
        }

        return null;
    }

    protected function noteAboutEmail($email)
    {
        if ($email->get('parentType') && $email->get('parentId')) {
            $parent = $this->entityManager->getEntity($email->get('parentType'), $email->get('parentId'));

            if ($parent) {
                $this->getStreamService()->noteEmailReceived($parent, $email);

                return;
            }
        }
    }

    protected function processCaseToEmailFields($case, $email)
    {
        $userIdList = [];

        if ($case->hasLinkMultipleField('assignedUsers')) {
            $userIdList = $case->getLinkMultipleIdList('assignedUsers');
        }
        else {
            $assignedUserId = $case->get('assignedUserId');

            if ($assignedUserId) {
                $userIdList[] = $assignedUserId;
            }
        }

        foreach ($userIdList as $userId) {
            $email->addLinkMultipleId('users', $userId);
        }

        $teamIdList = $case->getLinkMultipleIdList('teams');

        foreach ($teamIdList as $teamId) {
            $email->addLinkMultipleId('teams', $teamId);
        }

        $this->entityManager->saveEntity($email, [
            'skipLinkMultipleRemove' => true,
            'skipLinkMultipleUpdate' => true,
        ]);
    }

    protected function createCase(InboundEmailEntity $inboundEmail, EmailEntity $email): void
    {
        if ($email->get('parentType') === 'Case' && $email->get('parentId')) {
            $case = $this->entityManager->getEntity('Case', $email->get('parentId'));

            if ($case) {
                $this->processCaseToEmailFields($case, $email);

                if (!$email->isFetched()) {
                    $this->getStreamService()->noteEmailReceived($case, $email);
                }
            }

            return;
        }

        if (preg_match('/\[#([0-9]+)[^0-9]*\]/', $email->get('name'), $m)) {
            $caseNumber = $m[1];

            $case = $this->entityManager
                ->getRDBRepository('Case')
                ->where([
                    'number' => $caseNumber,
                ])
                ->findOne();

            if ($case) {
                $email->set('parentType', 'Case');
                $email->set('parentId', $case->getId());

                $this->processCaseToEmailFields($case, $email);

                if (!$email->isFetched()) {
                    $this->getStreamService()->noteEmailReceived($case, $email);
                }
            }

            return;
        }

        $params = [
            'caseDistribution' => $inboundEmail->get('caseDistribution'),
            'teamId' => $inboundEmail->get('teamId'),
            'userId' => $inboundEmail->get('assignToUserId'),
            'targetUserPosition' => $inboundEmail->get('targetUserPosition'),
            'inboundEmailId' => $inboundEmail->getId(),
        ];

        $case = $this->emailToCase($email, $params);

        $user = $this->entityManager->getEntity('User', $case->get('assignedUserId'));

        $this->getStreamService()->noteEmailReceived($case, $email, true);

        if ($inboundEmail->get('reply')) {
            $this->autoReply($inboundEmail, $email, $case, $user);
        }
    }

    protected function assignRoundRobin(Entity $case, Team $team, $targetUserPosition)
    {
        $distribution = new RoundRobin($this->entityManager);

        $user = $distribution->getUser($team, $targetUserPosition);

        if ($user) {
            $case->set('assignedUserId', $user->getId());
            $case->set('status', 'Assigned');
        }
    }

    protected function assignLeastBusy(Entity $case, Team $team, $targetUserPosition)
    {
        $distribution = new LeastBusy($this->entityManager, $this->getMetadata());

        $user = $distribution->getUser($team, $targetUserPosition);

        if ($user) {
            $case->set('assignedUserId', $user->id);
            $case->set('status', 'Assigned');
        }
    }

    protected function emailToCase(EmailEntity $email, array $params = [])
    {
        /** @var CaseEntity $case */
        $case = $this->entityManager->getEntity('Case');

        $case->populateDefaults();

        $case->set('name', $email->get('name'));

        $bodyPlain = $email->getBodyPlain();

        if (trim(preg_replace('/\s+/', '', $bodyPlain)) === '') {
            $bodyPlain = '';
        }

        if ($bodyPlain) {
            $case->set('description', $bodyPlain);
        }

        $attachmentIdList = $email->getLinkMultipleIdList('attachments');
        $copiedAttachmentIdList = [];

        /** @var AttachmentRepository $attachmentRepository*/
        $attachmentRepository = $this->entityManager->getRepository('Attachment');

        foreach ($attachmentIdList as $attachmentId) {
            $attachment = $attachmentRepository->get($attachmentId);

            if (!$attachment) {
                continue;
            }

            $copiedAttachment = $attachmentRepository->getCopiedAttachment($attachment);

            $copiedAttachmentIdList[] = $copiedAttachment->getId();
        }

        if (count($copiedAttachmentIdList)) {
            $case->setLinkMultipleIdList('attachments', $copiedAttachmentIdList);
        }

        $userId = null;

        if (!empty($params['userId'])) {
            $userId = $params['userId'];
        }

        if (!empty($params['inboundEmailId'])) {
            $case->set('inboundEmailId', $params['inboundEmailId']);
        }

        $teamId = false;

        if (!empty($params['teamId'])) {
            $teamId = $params['teamId'];
        }

        if ($teamId) {
            $case->set('teamsIds', [$teamId]);
        }

        $caseDistribution = '';

        if (!empty($params['caseDistribution'])) {
            $caseDistribution = $params['caseDistribution'];
        }

        $targetUserPosition = null;

        if (!empty($params['targetUserPosition'])) {
            $targetUserPosition = $params['targetUserPosition'];
        }


        switch ($caseDistribution) {
            case 'Direct-Assignment':
                if ($userId) {
                    $case->set('assignedUserId', $userId);
                    $case->set('status', 'Assigned');
                }

                break;

            case 'Round-Robin':
                if ($teamId) {
                    $team = $this->entityManager->getEntity('Team', $teamId);

                    if ($team) {
                        $this->assignRoundRobin($case, $team, $targetUserPosition);
                    }
                }

                break;

            case 'Least-Busy':
                if ($teamId) {
                    $team = $this->entityManager->getEntity('Team', $teamId);

                    if ($team) {
                        $this->assignLeastBusy($case, $team, $targetUserPosition);
                    }
                }

                break;
        }

        if ($case->get('assignedUserId')) {
            $email->set('assignedUserId', $case->get('assignedUserId'));
        }

        if ($email->get('accountId')) {
            $case->set('accountId', $email->get('accountId'));
        }

        $contact = $this->entityManager
            ->getRDBRepository('Contact')
            ->join('emailAddresses', 'emailAddressesMultiple')
            ->where([
                'emailAddressesMultiple.id' => $email->get('fromEmailAddressId')
            ])
            ->findOne();

        if ($contact) {
            $case->set('contactId', $contact->getId());
        }
        else {
            if (!$case->get('accountId')) {
                $lead = $this->entityManager
                    ->getRDBRepository('Lead')
                    ->join('emailAddresses', 'emailAddressesMultiple')
                    ->where([
                        'emailAddressesMultiple.id' => $email->get('fromEmailAddressId')
                    ])
                    ->findOne();

                if ($lead) {
                    $case->set('leadId', $lead->getId());
                }
            }
        }

        $this->entityManager->saveEntity($case);

        $email->set('parentType', 'Case');
        $email->set('parentId', $case->getId());

        $this->entityManager->saveEntity($email, [
            'skipLinkMultipleRemove' => true,
            'skipLinkMultipleUpdate' => true,
        ]);

        return $this->entityManager->getEntity('Case', $case->getId());
    }

    protected function autoReply(
        InboundEmailEntity $inboundEmail,
        EmailEntity $email,
        ?CaseEntity $case = null,
        ?User $user = null
    ): void {

        if (!$email->get('from')) {
            return;
        }

        $replyEmailTemplateId = $inboundEmail->get('replyEmailTemplateId');

        if (!$replyEmailTemplateId) {
            return;
        }

        $limit = $this->config->get('emailAutoReplyLimit', self::DEFAULT_AUTOREPLY_LIMIT);

        $d = new DateTime();

        $period = $this->config->get('emailAutoReplySuppressPeriod', self::DEFAULT_AUTOREPLY_SUPPRESS_PERIOD);

        $d->modify('-' . $period);

        $threshold = $d->format('Y-m-d H:i:s');

        /** @var EmailAddressRepository $emailAddressRepository */
        $emailAddressRepository = $this->entityManager->getRepository('EmailAddress');

        $emailAddress = $emailAddressRepository->getByAddress($email->get('from'));

        $sentCount = $this->entityManager
            ->getRDBRepository('Email')
            ->where([
                'toEmailAddresses.id' => $emailAddress->getId(),
                'dateSent>' => $threshold,
                'status' => 'Sent',
                'createdById' => 'system',
            ])
            ->join('toEmailAddresses')
            ->count();

        if ($sentCount >= $limit) {
            return;
        }

        $message = new Message();

        $messageId = $email->get('messageId');

        if ($messageId) {
            $message->getHeaders()->addHeaderLine('In-Reply-To', $messageId);
        }

        $message->getHeaders()->addHeaderLine('Auto-Submitted', 'auto-replied');
        $message->getHeaders()->addHeaderLine('X-Auto-Response-Suppress', 'All');
        $message->getHeaders()->addHeaderLine('Precedence', 'auto_reply');

        try {
            $entityHash = [];

            if ($case) {
                $entityHash['Case'] = $case;

                if ($case->get('contactId')) {
                    $contact = $this->entityManager->getEntity('Contact', $case->get('contactId'));
                }
            }

            if (empty($contact)) {
                $contact = $this->entityManager->getEntity('Contact');

                $fromName = EmailService::parseFromName($email->get('fromString'));

                if (!empty($fromName)) {
                    $contact->set('name', $fromName);
                }
            }

            $entityHash['Person'] = $contact;
            $entityHash['Contact'] = $contact;
            $entityHash['Email'] = $email;

            if ($user) {
                $entityHash['User'] = $user;
            }

            $emailTemplateService = $this->getEmailTemplateService();

            $replyData = $emailTemplateService->parse(
                $replyEmailTemplateId,
                ['entityHash' => $entityHash],
                true
            );

            $subject = $replyData['subject'];

            if ($case) {
                $subject = '[#' . $case->get('number'). '] ' . $subject;
            }

            $reply = $this->entityManager->getEntity('Email');

            $reply->set('to', $email->get('from'));
            $reply->set('subject', $subject);
            $reply->set('body', $replyData['body']);
            $reply->set('isHtml', $replyData['isHtml']);
            $reply->set('attachmentsIds', $replyData['attachmentsIds']);

            if ($email->has('teamsIds')) {
                $reply->set('teamsIds', $email->get('teamsIds'));
            }

            if ($email->get('parentId') && $email->get('parentType')) {
                $reply->set('parentId', $email->get('parentId'));
                $reply->set('parentType', $email->get('parentType'));
            }

            $this->entityManager->saveEntity($reply);

            $sender = $this->emailSender->create();

            if ($inboundEmail->get('useSmtp')) {
                $smtpParams = $this->getSmtpParamsFromInboundEmail($inboundEmail);

                if ($smtpParams) {
                    $sender->withSmtpParams($smtpParams);
                }
            }

            $senderParams = [];

            if ($inboundEmail->get('fromName')) {
                $senderParams['fromName'] = $inboundEmail->get('fromName');
            }

            if ($inboundEmail->get('replyFromAddress')) {
                $senderParams['fromAddress'] = $inboundEmail->get('replyFromAddress');
            }

            if ($inboundEmail->get('replyFromName')) {
                $senderParams['fromName'] = $inboundEmail->get('replyFromName');
            }

            if ($inboundEmail->get('replyToAddress')) {
                $senderParams['replyToAddress'] = $inboundEmail->get('replyToAddress');
            }

            $sender
                ->withParams($senderParams)
                ->withMessage($message)
                ->send($reply);

            $this->entityManager->saveEntity($reply);
        }
        catch (Exception $e) {
            $this->log->error("Inbound Email: Auto-reply error: " . $e->getMessage());
        }
    }

    protected function getSmtpParamsFromInboundEmail(InboundEmailEntity $emailAccount)
    {
        $smtpParams = [];

        $smtpParams['server'] = $emailAccount->get('smtpHost');

        if ($smtpParams['server']) {
            $smtpParams['port'] = $emailAccount->get('smtpPort');
            $smtpParams['auth'] = $emailAccount->get('smtpAuth');
            $smtpParams['security'] = $emailAccount->get('smtpSecurity');
            $smtpParams['username'] = $emailAccount->get('smtpUsername');
            $smtpParams['password'] = $emailAccount->get('smtpPassword');

            if (array_key_exists('password', $smtpParams)) {
                $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
            }

            return $smtpParams;
        }

        return;
    }

    protected function processBouncedMessage($message): bool
    {
        $content = $message->getRawContent();

        $isHard = false;

        if (preg_match('/permanent[ ]*[error|failure]/', $content)) {
            $isHard = true;
        }

        $queueItemId = null;

        if (preg_match('/X-Queue-Item-Id: [a-z0-9\-]*/', $content, $m)) {
            $arr = preg_split('/X-Queue-Item-Id: /', $m[0], -1, \PREG_SPLIT_NO_EMPTY);
            $queueItemId = $arr[0];
        } else {
            $to = $message->getAttribute('to');

            if (preg_match('/\+bounce-qid-[a-z0-9\-]*/', $to, $m)) {
                $arr = preg_split('/\+bounce-qid-/', $m[0], -1, \PREG_SPLIT_NO_EMPTY);
                $queueItemId = $arr[0];
            }
        }

        if (!$queueItemId) {
            return false;
        }

        $queueItem = $this->entityManager->getEntity('EmailQueueItem', $queueItemId);

        if (!$queueItem) {
            return false;
        }

        $massEmailId = $queueItem->get('massEmailId');
        $massEmail = $this->entityManager->getEntity('MassEmail', $massEmailId);

        $campaignId = null;

        if ($massEmail) {
            $campaignId = $massEmail->get('campaignId');
        }

        $targetType = $queueItem->get('targetType');
        $targetId = $queueItem->get('targetId');
        $target = $this->entityManager->getEntity($targetType, $targetId);

        $emailAddress = $queueItem->get('emailAddress');

        /** @var EmailAddressRepository $emailAddressRepository */
        $emailAddressRepository = $this->entityManager->getRepository('EmailAddress');

        if ($isHard && $emailAddress) {
            $emailAddressEntity = $emailAddressRepository->getByAddress($emailAddress);

            if ($emailAddressEntity) {
                $emailAddressEntity->set('invalid', true);

                $this->entityManager->saveEntity($emailAddressEntity);
            }
        }

        if ($campaignId && $target && $target->getId()) {
            $this->getCampaignService()
                ->logBounced(
                    $campaignId, $queueItemId, $target, $emailAddress, $isHard, null, $queueItem->get('isTest')
                );
        }

        return true;
    }

    protected function getCampaignService(): CampaignService
    {
        if (!$this->campaignService) {
            $this->campaignService = $this->injectableFactory->create(CampaignService::class);
        }

        return $this->campaignService;
    }

    public function findAccountForSending(string $emailAddress): ?InboundEmailEntity
    {
        /** @var ?InboundEmailEntity */
        $inboundEmail = $this->entityManager
            ->getRDBRepository('InboundEmail')
            ->where([
                'status' => 'Active',
                'useSmtp' => true,
                'smtpHost!=' => null,
                'emailAddress' => $emailAddress,
            ])
            ->findOne();

        return $inboundEmail;
    }

    /**
     * @param string $emailAddress
     * @return InboundEmailEntity|null
     */
    public function findSharedAccountForUser(User $user, $emailAddress)
    {
        $groupEmailAccountPermission = $this->getAclManager()->get($user, 'groupEmailAccountPermission');

        if (!$groupEmailAccountPermission || $groupEmailAccountPermission === 'no') {
            return null;
        }

        if ($groupEmailAccountPermission === 'team') {
            $teamIdList = $user->getLinkMultipleIdList('teams');

            if (!count($teamIdList)) {
                return null;
            }

            // @todo Use the query builder.
            $selectParams = [
                'whereClause' => [
                    'status' => 'Active',
                    'useSmtp' => true,
                    'smtpIsShared' => true,
                    'teamsMiddle.teamId' => $teamIdList,
                    'emailAddress' => $emailAddress,
                ],
                'joins' => ['teams'],
                'distinct' => true,
            ];

            /** @var ?InboundEmailEntity */
            return $this->entityManager->getRDBRepository('InboundEmail')->findOne($selectParams);;
        }

        if ($groupEmailAccountPermission === 'all') {
            $selectParams = [
                'whereClause' => [
                    'status' => 'Active',
                    'useSmtp' => true,
                    'smtpIsShared' => true,
                    'emailAddress' => $emailAddress,
                ]
            ];

            /** @var ?InboundEmailEntity */
            return $this->entityManager->getRDBRepository('InboundEmail')->findOne($selectParams);
        }

        return null;
    }

    protected function getStorage(InboundEmailEntity $emailAccount)
    {
        $params = [
            'host' => $emailAccount->get('host'),
            'port' => $emailAccount->get('port'),
            'username' => $emailAccount->get('username'),
            'password' => $this->getCrypt()->decrypt($emailAccount->get('password')),
        ];

        if ($emailAccount->get('security')) {
            $params['security'] = $emailAccount->get('security');
        }

        $params['imapHandler'] = $emailAccount->get('imapHandler');
        $params['id'] = $emailAccount->id;

        $storage = $this->createStorage($params);

        return $storage;
    }

    protected function createStorage(array $params)
    {
        $imapParams = null;

        $handlerClassName = $params['imapHandler'] ?? null;

        $handler = null;

        if ($handlerClassName && !empty($params['id'])) {
            try {
                $handler = $this->injectableFactory->create($handlerClassName);
            }
            catch (Throwable $e) {
                $this->log->error(
                    "InboundEmail: Could not create Imap Handler. Error: " . $e->getMessage()
                );
            }

            if (method_exists($handler, 'prepareProtocol')) {
                // for backward compatibility
                $params['ssl'] = $params['security'];

                $imapParams = $handler->prepareProtocol($params['id'], $params);
            }
        }

        if (!$imapParams) {
            $imapParams = [
                'host' => $params['host'],
                'port' => $params['port'],
                'user' => $params['username'],
                'password' => $params['password'],
            ];

            if (!empty($params['security'])) {
                $imapParams['ssl'] = $params['security'];
            }
        }

        return new $this->storageClassName($imapParams);
    }

    public function storeSentMessage(InboundEmailEntity $emailAccount, $message)
    {
        $storage = $this->getStorage($emailAccount);

        $folder = $emailAccount->get('sentFolder');

        if (empty($folder)) {
            throw new Error("No sent folder for Email Account: " . $emailAccount->id . ".");
        }

        $storage->appendMessage($message->toString(), $folder);
    }

    public function getSmtpParamsFromAccount(InboundEmailEntity $emailAccount): ?array
    {
        $smtpParams = [];
        $smtpParams['server'] = $emailAccount->get('smtpHost');

        if ($smtpParams['server']) {
            $smtpParams['port'] = $emailAccount->get('smtpPort');
            $smtpParams['auth'] = $emailAccount->get('smtpAuth');
            $smtpParams['security'] = $emailAccount->get('smtpSecurity');
            $smtpParams['username'] = $emailAccount->get('smtpUsername');
            $smtpParams['password'] = $emailAccount->get('smtpPassword');

            if ($emailAccount->get('smtpAuth')) {
                $smtpParams['authMechanism'] = $emailAccount->get('smtpAuthMechanism');
            }

            if ($emailAccount->get('fromName')) {
                $smtpParams['fromName'] = $emailAccount->get('fromName');
            }

            if ($emailAccount->get('emailAddress')) {
                $smtpParams['fromAddress'] = $emailAccount->get('emailAddress');
            }

            if (array_key_exists('password', $smtpParams) && is_string($smtpParams['password'])) {
                $smtpParams['password'] = $this->getCrypt()->decrypt($smtpParams['password']);
            }

            $this->applySmtpHandler($emailAccount, $smtpParams);

            return $smtpParams;
        }

        return null;
    }

    public function applySmtpHandler(InboundEmailEntity $emailAccount, array &$params)
    {
        $handlerClassName = $emailAccount->get('smtpHandler');

        if (!$handlerClassName) {
            return;
        }

        $handler = null;

        try {
            $handler = $this->injectableFactory->create($handlerClassName);
        }
        catch (Throwable $e) {
            $this->log->error(
                "InboundEmail: Could not create Smtp Handler for account {$emailAccount->id}. Error: " .
                    $e->getMessage()
            );
        }

        if (method_exists($handler, 'applyParams')) {
            $handler->applyParams($emailAccount->getId(), $params);
        }
    }

    protected function checkFetchOnlyHeader(Imap $storage, string $id): bool
    {
        $maxSize = $this->config->get('emailMessageMaxSize');

        if (!$maxSize) {
            return false;
        }

        try {
            $size = $storage->getSize((int) $id);
        }
        catch (Throwable $e) {
            return false;
        }

        if (!is_int($size)) {
            return false;
        }

        if ($size > $maxSize * 1024 * 1024) {
            return true;
        }

        return false;
    }

    protected function checkMessageIsAutoReply(MessageWrapper $message): bool
    {
        if ($message->getAttribute('X-Autoreply')) {
            return true;
        }

        if ($message->getAttribute('X-Autorespond')) {
            return true;
        }

        if (
            $message->getAttribute('Auto-submitted') &&
            strtolower($message->getAttribute('Auto-submitted')) !== 'no'
        ) {
            return true;
        }

        return false;
    }

    protected function checkMessageCannotBeAutoReplied(MessageWrapper $message): bool
    {
        if ($message->getAttribute('X-Auto-Response-Suppress') === 'AutoReply') {
            return true;
        }

        if ($message->getAttribute('X-Auto-Response-Suppress') === 'All') {
            return true;
        }

        if ($this->checkMessageIsAutoReply($message)) {
            return true;
        }

        return false;
    }

    private function getEmailTemplateService(): EmailTemplateService
    {
        /** @var EmailTemplateService */
        return $this->injectableFactory->create(EmailTemplateService::class);
    }
}
