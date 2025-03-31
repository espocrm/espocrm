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

namespace Espo\Core\Mail\Account\GroupAccount\Hooks;

use Espo\Core\Name\Field;
use Laminas\Mail\Message;

use Espo\Core\Mail\Account\GroupAccount\AccountFactory as GroupAccountFactory;
use Espo\Core\Mail\SenderParams;
use Espo\Core\Templates\Entities\Person;
use Espo\Core\Utils\SystemUser;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Tools\Email\Util;
use Espo\Tools\EmailTemplate\Data as EmailTemplateData;
use Espo\Tools\EmailTemplate\Params as EmailTemplateParams;
use Espo\Tools\EmailTemplate\Service as EmailTemplateService;
use Espo\Core\Mail\Account\Account;
use Espo\Core\Mail\Account\Hook\BeforeFetchResult;
use Espo\Core\Mail\Account\Hook\AfterFetch as AfterFetchInterface;
use Espo\Core\Mail\Account\GroupAccount\Account as GroupAccount;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Log;
use Espo\Tools\Stream\Service as StreamService;
use Espo\Entities\InboundEmail;
use Espo\Entities\Email;
use Espo\Entities\User;
use Espo\Entities\Team;
use Espo\Entities\EmailAddress;
use Espo\Entities\Attachment;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\ORM\EntityManager;
use Espo\Modules\Crm\Tools\Case\Distribution\RoundRobin;
use Espo\Modules\Crm\Tools\Case\Distribution\LeastBusy;

use Throwable;
use DateTime;

class AfterFetch implements AfterFetchInterface
{
    private const DEFAULT_AUTOREPLY_LIMIT = 5;
    private const DEFAULT_AUTOREPLY_SUPPRESS_PERIOD = '2 hours';

    public function __construct(
        private EntityManager $entityManager,
        private StreamService $streamService,
        private Config $config,
        private EmailSender $emailSender,
        private Log $log,
        private RoundRobin $roundRobin,
        private LeastBusy $leastBusy,
        private GroupAccountFactory $groupAccountFactory,
        private EmailTemplateService $emailTemplateService,
        private SystemUser $systemUser
    ) {}

    public function process(Account $account, Email $email, BeforeFetchResult $beforeFetchResult): void
    {
        if (!$account instanceof GroupAccount) {
            return;
        }

        if (!$account->createCase() && !$email->isFetched()) {
            $this->noteAboutEmail($email);
        }

        if ($account->createCase()) {
            if ($beforeFetchResult->get('isAutoReply')) {
                return;
            }

            $emailToProcess = $email;

            if ($email->isFetched()) {
                $emailToProcess = $this->entityManager->getEntityById(Email::ENTITY_TYPE, $email->getId());
            } else {
                $emailToProcess->updateFetchedValues();
            }

            if ($emailToProcess) {
                $this->createCase($account, $email);
            }

            return;
        }

        if ($account->autoReply()) {
            if ($beforeFetchResult->get('skipAutoReply')) {
                return;
            }

            $user = null;

            if ($account->getAssignedUser()) {
                $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $account->getAssignedUser()->getId());
            }

            $this->autoReply($account, $email, null, $user);
        }
    }

    private function noteAboutEmail(Email $email): void
    {
        if (!$email->getParent()) {
            return;
        }

        $this->streamService->noteEmailReceived($email->getParent(), $email);
    }

    private function autoReply(
        GroupAccount $account,
        Email $email,
        ?CaseObj $case = null,
        ?User $user = null
    ): void {

        $inboundEmail = $account->getEntity();

        $fromAddress = $email->getFromAddress();

        if (!$fromAddress) {
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

        $threshold = $d->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        /** @var EmailAddressRepository $emailAddressRepository */
        $emailAddressRepository = $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);

        $emailAddress = $emailAddressRepository->getByAddress($fromAddress);

        if ($emailAddress) {
            $sentCount = $this->entityManager
                ->getRDBRepository(Email::ENTITY_TYPE)
                ->where([
                    'toEmailAddresses.id' => $emailAddress->getId(),
                    'dateSent>' => $threshold,
                    'status' => Email::STATUS_SENT,
                    'createdById' => $this->systemUser->getId(),
                ])
                ->join('toEmailAddresses')
                ->count();

            if ($sentCount >= $limit) {
                return;
            }
        }

        $sender = $this->emailSender->create();

        if ($email->getMessageId()) {
            $sender->withAddedHeader('In-Reply-To', $email->getMessageId());
        }

        $sender
            ->withAddedHeader('Auto-Submitted', 'auto-replied')
            ->withAddedHeader('X-Auto-Response-Suppress', 'All')
            ->withAddedHeader('Precedence', 'auto_reply');

        try {
            $entityHash = [];

            $contact = null;

            if ($case) {
                $entityHash[CaseObj::ENTITY_TYPE] = $case;

                $contact = $case->getContact();
            }

            if (!$contact) {
                $contact = $this->entityManager->getNewEntity(Contact::ENTITY_TYPE);

                $fromName = Util::parseFromName($email->getFromString() ?? '');

                if (!empty($fromName)) {
                    $contact->set(Field::NAME, $fromName);
                }
            }

            $entityHash[Person::TEMPLATE_TYPE] = $contact;
            $entityHash[Contact::ENTITY_TYPE] = $contact;
            $entityHash[Email::ENTITY_TYPE] = $email;

            if ($user) {
                $entityHash[User::ENTITY_TYPE] = $user;
            }

            $replyData = $this->emailTemplateService->process(
                $replyEmailTemplateId,
                EmailTemplateData::create()
                    ->withEntityHash($entityHash),
                EmailTemplateParams::create()
                    ->withApplyAcl(false)
                    ->withCopyAttachments()
            );

            $subject = $replyData->getSubject();

            if ($case) {
                $subject = '[#' . $case->get('number'). '] ' . $subject;
            }

            /** @var Email $reply */
            $reply = $this->entityManager->getRDBRepositoryByClass(Email::class)->getNew();

            $reply
                ->addToAddress($fromAddress)
                ->setSubject($subject)
                ->setBody($replyData->getBody())
                ->setIsHtml($replyData->isHtml());

            if ($email->has('teamsIds')) {
                $reply->set('teamsIds', $email->get('teamsIds'));
            }

            if ($email->getParentId() && $email->getParentType()) {
                $reply->set('parentId', $email->getParentId());
                $reply->set('parentType', $email->getParentType());
            }

            $this->entityManager->saveEntity($reply);

            $senderParams = SenderParams::create();

            if ($inboundEmail->isAvailableForSending()) {
                $groupAccount = $this->groupAccountFactory->create($inboundEmail->getId());

                $smtpParams = $groupAccount->getSmtpParams();

                if ($smtpParams) {
                    $sender->withSmtpParams($smtpParams);
                }

                if ($groupAccount->getEmailAddress()) {
                    $senderParams = $senderParams->withFromAddress($groupAccount->getEmailAddress());
                }
            }

            if ($inboundEmail->getFromName()) {
                $senderParams = $senderParams->withFromName($inboundEmail->getFromName());
            }

            if ($inboundEmail->getReplyFromAddress()) {
                $senderParams = $senderParams->withFromAddress($inboundEmail->getReplyFromAddress());
            }

            if ($inboundEmail->getReplyFromName()) {
                $senderParams = $senderParams->withFromName($inboundEmail->getReplyFromName());
            }

            if ($inboundEmail->getReplyToAddress()) {
                $senderParams = $senderParams->withReplyToAddress($inboundEmail->getReplyToAddress());
            }

            $sender
                ->withParams($senderParams)
                ->withAttachments($replyData->getAttachmentList())
                ->send($reply);

            $this->entityManager->saveEntity($reply);
        } catch (Throwable $e) {
            $this->log->error("Inbound Email: Auto-reply error: " . $e->getMessage(), ['exception' => $e]);
        }
    }

    private function createCase(GroupAccount $account, Email $email): void
    {
        $inboundEmail = $account->getEntity();

        $parentId = $email->getParentId();

        if (
            $email->getParentType() === CaseObj::ENTITY_TYPE &&
            $parentId
        ) {
            $case = $this->entityManager
                ->getRDBRepositoryByClass(CaseObj::class)
                ->getById($parentId);

            if (!$case) {
                return;
            }

            $this->processCaseToEmailFields($case, $email);

            if (!$email->isFetched()) {
                $this->streamService->noteEmailReceived($case, $email);
            }

            return;
        }

        /** @noinspection RegExpRedundantEscape */
        if (preg_match('/\[#([0-9]+)[^0-9]*\]/', $email->get(Field::NAME), $m)) {
            $caseNumber = $m[1];

            $case = $this->entityManager
                ->getRDBRepository(CaseObj::ENTITY_TYPE)
                ->where([
                    'number' => $caseNumber,
                ])
                ->findOne();

            if (!$case) {
                return;
            }

            $email->set('parentType', CaseObj::ENTITY_TYPE);
            $email->set('parentId', $case->getId());

            $this->processCaseToEmailFields($case, $email);

            if (!$email->isFetched()) {
                $this->streamService->noteEmailReceived($case, $email);
            }

            return;
        }

        $params = [
            'caseDistribution' => $inboundEmail->getCaseDistribution(),
            'teamId' => $inboundEmail->get('teamId'),
            'userId' => $inboundEmail->get('assignToUserId'),
            'targetUserPosition' => $inboundEmail->getTargetUserPosition(),
            'inboundEmailId' => $inboundEmail->getId(),
        ];

        $case = $this->emailToCase($email, $params);

        $assignedUserLink = $case->getAssignedUser();

        $user = $assignedUserLink ?
            $this->entityManager->getEntityById(User::ENTITY_TYPE, $assignedUserLink->getId())
            : null;

        $this->streamService->noteEmailReceived($case, $email, true);

        if ($account->autoReply()) {
            $this->autoReply($account, $email, $case, $user);
        }
    }

    private function processCaseToEmailFields(CaseObj $case, Email $email): void
    {
        $userIdList = [];

        if ($case->hasLinkMultipleField(Field::ASSIGNED_USERS)) {
            $userIdList = $case->getLinkMultipleIdList(Field::ASSIGNED_USERS);
        } else {
            $assignedUserLink = $case->getAssignedUser();

            if ($assignedUserLink) {
                $userIdList[] = $assignedUserLink->getId();
            }
        }

        foreach ($userIdList as $userId) {
            $email->addLinkMultipleId('users', $userId);
        }

        $teamIdList = $case->getLinkMultipleIdList(Field::TEAMS);

        foreach ($teamIdList as $teamId) {
            $email->addLinkMultipleId(Field::TEAMS, $teamId);
        }

        $this->entityManager->saveEntity($email, [
            'skipLinkMultipleRemove' => true,
            'skipLinkMultipleUpdate' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function emailToCase(Email $email, array $params): CaseObj
    {
        /** @var CaseObj $case */
        $case = $this->entityManager->getNewEntity(CaseObj::ENTITY_TYPE);

        $case->populateDefaults();

        $case->set(Field::NAME, $email->get(Field::NAME));

        $bodyPlain = $email->getBodyPlain() ?? '';

        /** @var string $replacedBodyPlain */
        $replacedBodyPlain = preg_replace('/\s+/', '', $bodyPlain);

        if (trim($replacedBodyPlain) === '') {
            $bodyPlain = '';
        }

        if ($bodyPlain) {
            $case->set('description', $bodyPlain);
        }

        $attachmentIdList = $email->getLinkMultipleIdList('attachments');

        $copiedAttachmentIdList = [];

        /** @var AttachmentRepository $attachmentRepository*/
        $attachmentRepository = $this->entityManager->getRepository(Attachment::ENTITY_TYPE);

        foreach ($attachmentIdList as $attachmentId) {
            $attachment = $attachmentRepository->getById($attachmentId);

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
            case InboundEmail::CASE_DISTRIBUTION_DIRECT_ASSIGNMENT:
                if ($userId) {
                    $case->set('assignedUserId', $userId);
                    $case->set('status', CaseObj::STATUS_ASSIGNED);
                }

                break;

            case InboundEmail::CASE_DISTRIBUTION_ROUND_ROBIN:
                if ($teamId) {
                    /** @var ?Team $team */
                    $team = $this->entityManager->getEntityById(Team::ENTITY_TYPE, $teamId);

                    if ($team) {
                        $this->assignRoundRobin($case, $team, $targetUserPosition);
                    }
                }

                break;

            case InboundEmail::CASE_DISTRIBUTION_LEAST_BUSY:
                if ($teamId) {
                    /** @var ?Team $team */
                    $team = $this->entityManager->getEntityById(Team::ENTITY_TYPE, $teamId);

                    if ($team) {
                        $this->assignLeastBusy($case, $team, $targetUserPosition);
                    }
                }

                break;
        }

        $assignedUserLink = $case->getAssignedUser();

        if ($assignedUserLink) {
            $email->set('assignedUserId', $assignedUserLink->getId());
        }

        if ($email->get('accountId')) {
            $case->set('accountId', $email->get('accountId'));
        }

        $contact = $this->entityManager
            ->getRDBRepository(Contact::ENTITY_TYPE)
            ->join('emailAddresses', 'emailAddressesMultiple')
            ->where([
                'emailAddressesMultiple.id' => $email->get('fromEmailAddressId'),
            ])
            ->findOne();

        if ($contact) {
            $case->set('contactId', $contact->getId());
        } else {
            if (!$case->get('accountId')) {
                $lead = $this->entityManager
                    ->getRDBRepository(Lead::ENTITY_TYPE)
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

        $email->set('parentType', CaseObj::ENTITY_TYPE);
        $email->set('parentId', $case->getId());

        $this->entityManager->saveEntity($email, [
            'skipLinkMultipleRemove' => true,
            'skipLinkMultipleUpdate' => true,
        ]);

        // Unknown reason of doing this.
        $fetchedCase = $this->entityManager
            ->getRDBRepositoryByClass(CaseObj::class)
            ->getById($case->getId());

        if ($fetchedCase) {
            return $fetchedCase;
        }

        $this->entityManager->refreshEntity($case);

        return $case;
    }

    private function assignRoundRobin(CaseObj $case, Team $team, ?string $targetUserPosition): void
    {
        $user = $this->roundRobin->getUser($team, $targetUserPosition);

        if ($user) {
            $case->set('assignedUserId', $user->getId());
            $case->set('status', CaseObj::STATUS_ASSIGNED);
        }
    }

    private function assignLeastBusy(CaseObj $case, Team $team, ?string $targetUserPosition): void
    {
        $user = $this->leastBusy->getUser($team, $targetUserPosition);

        if ($user) {
            $case->set('assignedUserId', $user->getId());
            $case->set('status', CaseObj::STATUS_ASSIGNED);
        }
    }
}
