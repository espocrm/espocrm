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

namespace Espo\Core\Mail\Account\GroupAccount\Hooks;

use Laminas\Mail\Message;

use Espo\Core\Mail\Account\Account;
use Espo\Core\Mail\Account\Hook\BeforeFetchResult;
use Espo\Core\Mail\Account\Hook\AfterFetch as AfterFetchInterface;
use Espo\Core\Mail\Account\GroupAccount\Account as GroupAccount;
use Espo\Core\Mail\EmailSender;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Crypt;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Log;

use Espo\Services\Stream as StreamService;
use Espo\Services\Email as EmailService;
use Espo\Services\EmailTemplate as EmailTemplateService;

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

use Espo\Modules\Crm\Business\Distribution\CaseObj\RoundRobin;
use Espo\Modules\Crm\Business\Distribution\CaseObj\LeastBusy;

use Throwable;
use DateTime;

class AfterFetch implements AfterFetchInterface
{
    private EntityManager $entityManager;

    private StreamService $streamService;

    private Config $config;

    private EmailSender $emailSender;

    private InjectableFactory $injectableFactory;

    private Crypt $crypt;

    private Log $log;

    private RoundRobin $roundRobin;

    private LeastBusy $leastBusy;

    private const DEFAULT_AUTOREPLY_LIMIT = 5;

    private const DEFAULT_AUTOREPLY_SUPPRESS_PERIOD = '2 hours';

    public function __construct(
        EntityManager $entityManager,
        StreamService $streamService,
        Config $config,
        EmailSender $emailSender,
        InjectableFactory $injectableFactory,
        Crypt $crypt,
        Log $log,
        RoundRobin $roundRobin,
        LeastBusy $leastBusy
    ) {
        $this->entityManager = $entityManager;
        $this->streamService = $streamService;
        $this->config = $config;
        $this->emailSender = $emailSender;
        $this->injectableFactory = $injectableFactory;
        $this->crypt = $crypt;
        $this->log = $log;
        $this->roundRobin = $roundRobin;
        $this->leastBusy = $leastBusy;
    }

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
                $emailToProcess = $this->entityManager->getEntity(Email::ENTITY_TYPE, $email->getId());
            }
            else {
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
                $user = $this->entityManager->getEntity(User::ENTITY_TYPE, $account->getAssignedUser()->getId());
            }

            $this->autoReply($account, $email, null, $user);
        }
    }

    private function noteAboutEmail(Email $email): void
    {
        $parentLink = $email->getParent();

        if (!$parentLink) {
            return;
        }

        $parent = $this->entityManager->getEntity($parentLink->getEntityType(), $parentLink->getId());

        if (!$parent) {
            return;
        }

        $this->streamService->noteEmailReceived($parent, $email);
    }

    private function autoReply(
        GroupAccount $account,
        Email $email,
        ?CaseObj $case = null,
        ?User $user = null
    ): void {

        $inboundEmail = $account->getEntity();

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

        $threshold = $d->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        /** @var EmailAddressRepository $emailAddressRepository */
        $emailAddressRepository = $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);

        $emailAddress = $emailAddressRepository->getByAddress($email->get('from'));


        if ($emailAddress) {
            $sentCount = $this->entityManager
                ->getRDBRepository(Email::ENTITY_TYPE)
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
                $contact = $this->entityManager->getNewEntity('Contact');

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

            $reply = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

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
        catch (Throwable $e) {
            $this->log->error("Inbound Email: Auto-reply error: " . $e->getMessage());
        }
    }

    private function getEmailTemplateService(): EmailTemplateService
    {
        /** @var EmailTemplateService */
        return $this->injectableFactory->create(EmailTemplateService::class);
    }

    /**
     * @return array<string,mixed>
     */
    private function getSmtpParamsFromInboundEmail(InboundEmail $emailAccount): ?array
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
                $smtpParams['password'] = $this->crypt->decrypt($smtpParams['password']);
            }

            return $smtpParams;
        }

        return null;
    }

    private function createCase(GroupAccount $account, Email $email): void
    {
        $inboundEmail = $account->getEntity();

        if (
            $email->get('parentType') === CaseObj::ENTITY_TYPE &&
            $email->get('parentId')
        ) {
            $case = $this->entityManager->getEntity(CaseObj::ENTITY_TYPE, $email->get('parentId'));

            if (!$case) {
                return;
            }

            $this->processCaseToEmailFields($case, $email);

            if (!$email->isFetched()) {
                $this->streamService->noteEmailReceived($case, $email);
            }

            return;
        }

        if (preg_match('/\[#([0-9]+)[^0-9]*\]/', $email->get('name'), $m)) {
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
            'caseDistribution' => $inboundEmail->get('caseDistribution'),
            'teamId' => $inboundEmail->get('teamId'),
            'userId' => $inboundEmail->get('assignToUserId'),
            'targetUserPosition' => $inboundEmail->get('targetUserPosition'),
            'inboundEmailId' => $inboundEmail->getId(),
        ];

        $case = $this->emailToCase($email, $params);

        $user = $this->entityManager->getEntity('User', $case->get('assignedUserId'));

        $this->streamService->noteEmailReceived($case, $email, true);

        if ($account->autoReply()) {
            $this->autoReply($account, $email, $case, $user);
        }
    }

    private function processCaseToEmailFields(CaseObj $case, Email $email): void
    {
        $userIdList = [];

        if ($case->hasLinkMultipleField('assignedUsers')) {
            /** @var string[] $userIdList */
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

        /** @var string[] $teamIdList */
        $teamIdList = $case->getLinkMultipleIdList('teams');

        foreach ($teamIdList as $teamId) {
            $email->addLinkMultipleId('teams', $teamId);
        }

        $this->entityManager->saveEntity($email, [
            'skipLinkMultipleRemove' => true,
            'skipLinkMultipleUpdate' => true,
        ]);
    }

    /**
     * @param array<string,mixed> $params
     */
    private function emailToCase(Email $email, array $params): CaseObj
    {
        /** @var CaseObj $case */
        $case = $this->entityManager->getEntity(CaseObj::ENTITY_TYPE);

        $case->populateDefaults();

        $case->set('name', $email->get('name'));

        $bodyPlain = $email->getBodyPlain() ?? '';

        /** @var string $replacedBodyPlain */
        $replacedBodyPlain = preg_replace('/\s+/', '', $bodyPlain);

        if (trim($replacedBodyPlain) === '') {
            $bodyPlain = '';
        }

        if ($bodyPlain) {
            $case->set('description', $bodyPlain);
        }

        /** @var string[] $attachmentIdList */
        $attachmentIdList = $email->getLinkMultipleIdList('attachments');

        $copiedAttachmentIdList = [];

        /** @var AttachmentRepository $attachmentRepository*/
        $attachmentRepository = $this->entityManager->getRepository(Attachment::ENTITY_TYPE);

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
                    $team = $this->entityManager->getEntity(Team::ENTITY_TYPE, $teamId);

                    if ($team) {
                        $this->assignRoundRobin($case, $team, $targetUserPosition);
                    }
                }

                break;

            case 'Least-Busy':
                if ($teamId) {
                    $team = $this->entityManager->getEntity(Team::ENTITY_TYPE, $teamId);

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

        // Unknown reason to do this.
        $fetchedCase = $this->entityManager->getEntity('Case', $case->getId());

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
            $case->set('status', 'Assigned');
        }
    }

    private function assignLeastBusy(CaseObj $case, Team $team, ?string $targetUserPosition): void
    {
        $user = $this->leastBusy->getUser($team, $targetUserPosition);

        if ($user) {
            $case->set('assignedUserId', $user->getId());
            $case->set('status', 'Assigned');
        }
    }
}
