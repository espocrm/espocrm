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

namespace Espo\Modules\Crm\Tools\MassEmail;

use Laminas\Mail\Message;

use Espo\Entities\EmailTemplate;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\Entities\EmailAddress;

use Espo\Core\{
    Exceptions\Error,
    ORM\EntityManager,
    ServiceFactory,
    Utils\Config,
    Utils\DateTime as DateTimeUtil,
    Utils\Language,
    Mail\EmailSender,
    Mail\Sender,
    Mail\Mail\Header\XQueueItemId,
    Utils\Log};

use Espo\{
    Entities\InboundEmail,
    Modules\Crm\Entities\EmailQueueItem,
    Modules\Crm\Entities\Campaign,
    Modules\Crm\Entities\MassEmail,
    Modules\Crm\Entities\CampaignTrackingUrl,
    Modules\Crm\Services\Campaign as CampaignService,
    Services\EmailTemplate as EmailTemplateService,
    ORM\Entity,
    Entities\Email
};

use Exception;
use DateTime;

class Processor
{
    private $config;

    private $serviceFactory;

    private $entityManager;

    private $defaultLanguage;

    private $emailSender;

    protected const MAX_ATTEMPT_COUNT = 3;

    protected const MAX_PER_HOUR_COUNT = 10000;

    private ?CampaignService $campaignService = null;

    private ?EmailTemplateService $emailTemplateService = null;

    protected Log $log;

    public function __construct(
        Config $config,
        ServiceFactory $serviceFactory,
        EntityManager $entityManager,
        Language $defaultLanguage,
        EmailSender $emailSender,
        Log $log
    ) {
        $this->config = $config;
        $this->serviceFactory = $serviceFactory;
        $this->entityManager = $entityManager;
        $this->defaultLanguage = $defaultLanguage;
        $this->emailSender = $emailSender;
        $this->log = $log;
    }

    /**
     * @throws Error
     */
    public function process(MassEmail $massEmail, bool $isTest = false): void
    {
        $maxBatchSize = $this->config->get('massEmailMaxPerHourCount', self::MAX_PER_HOUR_COUNT);

        if (!$isTest) {
            $threshold = new DateTime();
            $threshold->modify('-1 hour');

            $sentLastHourCount = $this->entityManager
                ->getRDBRepository(EmailQueueItem::ENTITY_TYPE)
                ->where([
                    'status' => EmailQueueItem::STATUS_SENT,
                    'sentAt>' => $threshold->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
                ])
                ->count();

            if ($sentLastHourCount >= $maxBatchSize) {
                return;
            }

            $maxBatchSize = $maxBatchSize - $sentLastHourCount;
        }

        $queueItemList = $this->entityManager
            ->getRDBRepository(EmailQueueItem::ENTITY_TYPE)
            ->where([
                'status' => EmailQueueItem::STATUS_PENDING,
                'massEmailId' => $massEmail->getId(),
                'isTest' => $isTest,
            ])
            ->limit(0, $maxBatchSize)
            ->find();

        $templateId = $massEmail->getEmailTemplateId();

        if (!$templateId) {
            $this->setFailed($massEmail);

            return;
        }

        $campaign = null;

        $campaignId = $massEmail->getCampaignId();

        if ($campaignId) {
            $campaign = $this->entityManager->getEntityById(Campaign::ENTITY_TYPE, $campaignId);
        }

        $emailTemplate = $this->entityManager
            ->getRDBRepositoryByClass(EmailTemplate::class)
            ->getById($templateId);

        if (!$emailTemplate) {
            $this->setFailed($massEmail);

            return;
        }

        /** @var iterable<\Espo\Entities\Attachment> $attachmentList */
        $attachmentList = $this->entityManager
            ->getRDBRepository(EmailTemplate::ENTITY_TYPE)
            ->getRelation($emailTemplate, 'attachments')
            ->find();

        $smtpParams = null;

        $inboundEmailId = $massEmail->getInboundEmailId();

        if ($inboundEmailId) {
            /** @var ?InboundEmail $inboundEmail */
            $inboundEmail = $this->entityManager->getEntityById(InboundEmail::ENTITY_TYPE, $inboundEmailId);

            if (!$inboundEmail) {
                throw new Error("Group Email Account '{$inboundEmailId}' is not available.");
            }

            if (
                !$inboundEmail->isAvailableForSending() ||
                !$inboundEmail->smtpIsForMassEmail()
            ) {
                throw new Error("Group Email Account '{$inboundEmailId}' can't be used for Mass Email.");
            }

            /** @var \Espo\Services\InboundEmail $inboundEmailService */
            $inboundEmailService = $this->serviceFactory->create(InboundEmail::ENTITY_TYPE);

            $smtpParams = $inboundEmailService->getSmtpParamsFromAccount($inboundEmail);

            if (!$smtpParams) {
                throw new Error("Group Email Account '{$inboundEmailId}' has no SMTP params.");
            }

            if ($inboundEmail->getReplyToAddress()) {
                $smtpParams['replyToAddress'] = $inboundEmail->getReplyToAddress();
            }
        }

        foreach ($queueItemList as $queueItem) {
            $this->sendQueueItem(
                $queueItem,
                $massEmail,
                $emailTemplate,
                $attachmentList,
                $campaign,
                $isTest,
                $smtpParams
            );
        }

        if (!$isTest) {
            $countLeft = $this->entityManager
                ->getRDBRepository(EmailQueueItem::ENTITY_TYPE)
                ->where([
                    'status' => EmailQueueItem::STATUS_PENDING,
                    'massEmailId' => $massEmail->getId(),
                    'isTest' => false,
                ])
                ->count();

            if ($countLeft == 0) {
                $massEmail->set('status', MassEmail::STATUS_COMPLETE);

                $this->entityManager->saveEntity($massEmail);
            }
        }
    }

    /**
     * @param iterable<CampaignTrackingUrl> $trackingUrlList
     */
    protected function getPreparedEmail(
        EmailQueueItem $queueItem,
        MassEmail $massEmail,
        EmailTemplate $emailTemplate,
        Entity $target,
        iterable $trackingUrlList = []
    ): ?Email {

        $templateParams = [
            'parent' => $target,
        ];

        $emailData = $this->getEmailTemplateService()->parseTemplate($emailTemplate, $templateParams);

        $body = $emailData['body'];

        $optOutUrl = $this->getSiteUrl() . '?entryPoint=unsubscribe&id=' . $queueItem->getId();

        $optOutLink =
            '<a href="' . $optOutUrl . '">' .
            $this->defaultLanguage->translateLabel('Unsubscribe', 'labels', Campaign::ENTITY_TYPE) .
            '</a>';

        $body = str_replace('{optOutUrl}', $optOutUrl, $body);
        $body = str_replace('{optOutLink}', $optOutLink, $body);

        foreach ($trackingUrlList as $trackingUrl) {
            $url = $this->getSiteUrl() .
                '?entryPoint=campaignUrl&id=' . $trackingUrl->getId() . '&queueItemId=' . $queueItem->getId();

            $body = str_replace($trackingUrl->get('urlToUse'), $url, $body);
        }

        if (
            !$this->config->get('massEmailDisableMandatoryOptOutLink') &&
            stripos($body, '?entryPoint=unsubscribe&id') === false
        ) {
            if ($emailData['isHtml']) {
                $body .= "<br><br>" . $optOutLink;
            }
            else {
                $body .= "\n\n" . $optOutUrl;
            }
        }

        $trackImageAlt = $this->defaultLanguage->translateLabel('Campaign', 'scopeNames');

        $trackOpenedUrl = $this->getSiteUrl() . '?entryPoint=campaignTrackOpened&id=' . $queueItem->getId();

        $trackOpenedHtml =
            '<img alt="' . $trackImageAlt . '" width="1" height="1" border="0" src="' . $trackOpenedUrl . '">';

        if ($massEmail->get('campaignId') && $this->config->get('massEmailOpenTracking')) {
            if ($emailData['isHtml']) {
                $body .= '<br>' . $trackOpenedHtml;
            }
        }

        $emailData['body'] = $body;

        $email = $this->entityManager
            ->getRDBRepositoryByClass(Email::class)
            ->getNew();

        $email->set($emailData);

        $emailAddress = $target->get('emailAddress');

        if (empty($emailAddress)) {
            return null;
        }

        $email->set('to', $emailAddress);

        if ($massEmail->getFromAddress()) {
            $email->set('from', $massEmail->getFromAddress());
        }

        if ($massEmail->getReplyToAddress()) {
            $email->set('replyTo', $massEmail->getReplyToAddress());
        }

        return $email;
    }

    /**
     * @param array<string,mixed> $params
     */
    protected function prepareQueueItemMessage(
        EmailQueueItem $queueItem,
        Sender $sender,
        Message $message,
        array &$params
    ): void {

        $header = new XQueueItemId();

        $header->setId($queueItem->getId());

        $message->getHeaders()->addHeader($header);

        $message->getHeaders()->addHeaderLine('Precedence', 'bulk');

        if (!$this->config->get('massEmailDisableMandatoryOptOutLink')) {
            $optOutUrl = $this->getSiteUrl() . '?entryPoint=unsubscribe&id=' . $queueItem->getId();

            $message->getHeaders()->addHeaderLine('List-Unsubscribe', '<' . $optOutUrl . '>');
        }

        $fromAddress = $params['fromAddress'] ?? $this->config->get('outboundEmailFromAddress');

        if ($this->config->get('massEmailVerp')) {
            if ($fromAddress && strpos($fromAddress, '@')) {
                $bounceAddress = explode('@', $fromAddress)[0] . '+bounce-qid-' . $queueItem->getId() .
                    '@' . explode('@', $fromAddress)[1];

                $sender->withEnvelopeOptions([
                    'from' => $bounceAddress,
                ]);
            }
        }
    }

    protected function setFailed(MassEmail $massEmail): void
    {
        $massEmail->set('status', MassEmail::STATUS_FAILED);

        $this->entityManager->saveEntity($massEmail);

        $queueItemList = $this->entityManager
            ->getRDBRepository(EmailQueueItem::ENTITY_TYPE)
            ->where([
                'status' => EmailQueueItem::STATUS_PENDING,
                'massEmailId' => $massEmail->getId(),
            ])
            ->find();

        foreach ($queueItemList as $queueItem) {
            $queueItem->set('status', EmailQueueItem::STATUS_FAILED);

            $this->entityManager->saveEntity($queueItem);
        }
    }

    /**
     * @param iterable<\Espo\Entities\Attachment> $attachmentList
     * @param ?array<string,mixed> $smtpParams
     */
    protected function sendQueueItem(
        EmailQueueItem $queueItem,
        MassEmail $massEmail,
        EmailTemplate $emailTemplate,
        $attachmentList = [],
        ?Campaign $campaign = null,
        bool $isTest = false,
        $smtpParams = null
    ): bool {

        /** @var ?EmailQueueItem $queueItemFetched */
        $queueItemFetched = $this->entityManager->getEntityById($queueItem->getEntityType(), $queueItem->getId());

        if (
            !$queueItemFetched ||
            $queueItemFetched->getStatus() !== EmailQueueItem::STATUS_PENDING
        ) {
            return false;
        }

        $queueItem->set('status', EmailQueueItem::STATUS_SENDING);

        $this->entityManager->saveEntity($queueItem);

        $target = $this->entityManager->getEntityById($queueItem->getTargetType(), $queueItem->getTargetId());

        $emailAddress = null;

        if ($target) {
            $emailAddress = $target->get('emailAddress');
        }

        if (
            !$target ||
            !$target->hasId() ||
            !$emailAddress
        ) {
            $queueItem->set('status', EmailQueueItem::STATUS_FAILED);

            $this->entityManager->saveEntity($queueItem);

            return false;
        }

        /** @var EmailAddressRepository $emailAddressRepository */
        $emailAddressRepository = $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);

        $emailAddressRecord = $emailAddressRepository->getByAddress($emailAddress);

        if ($emailAddressRecord) {
            if ($emailAddressRecord->isInvalid() || $emailAddressRecord->isOptedOut()) {
                $queueItem->set('status', EmailQueueItem::STATUS_FAILED);

                $this->entityManager->saveEntity($queueItem);

                return false;
            }
        }

        /** @var CampaignTrackingUrl[] $trackingUrlList */
        $trackingUrlList = [];

        if ($campaign) {
            /** @var \Espo\ORM\Collection<CampaignTrackingUrl> $trackingUrlList */
            $trackingUrlList = $this->entityManager
                ->getRDBRepository(Campaign::ENTITY_TYPE)
                ->getRelation($campaign, 'trackingUrls')
                ->find();
        }

        $email = $this->getPreparedEmail($queueItem, $massEmail, $emailTemplate, $target, $trackingUrlList);

        if (!$email) {
            return false;
        }

        if ($email->get('replyToAddress')) { // @todo Revise.
            unset($smtpParams['replyToAddress']);
        }

        if ($campaign) {
            $email->setLinkMultipleIdList(
                'teams',
                $campaign->getLinkMultipleIdList('teams') ?? []
            );
        }

        $params = [];

        if ($massEmail->getFromName()) {
            $params['fromName'] = $massEmail->getFromName();
        }

        if ($massEmail->getReplyToName()) {
            $params['replyToName'] = $massEmail->getReplyToName();
        }

        try {
            $attemptCount = $queueItem->getAttemptCount();
            $attemptCount++;

            $queueItem->set('attemptCount', $attemptCount);

            $sender = $this->emailSender->create();

            if ($smtpParams) {
                $sender->withSmtpParams($smtpParams);
            }

            $message = new Message();

            $this->prepareQueueItemMessage($queueItem, $sender, $message, $params);

            $sender
                ->withParams($params)
                ->withMessage($message)
                ->withAttachments($attachmentList)
                ->send($email);
        }
        catch (Exception $e) {
            $maxAttemptCount = $this->config->get('massEmailMaxAttemptCount', self::MAX_ATTEMPT_COUNT);

            if ($queueItem->getAttemptCount() >= $maxAttemptCount) {
                $queueItem->set('status', EmailQueueItem::STATUS_FAILED);
            }
            else {
                $queueItem->set('status', EmailQueueItem::STATUS_PENDING);
            }

            $this->entityManager->saveEntity($queueItem);

            $this->log->error('MassEmail#sendQueueItem: [' . $e->getCode() . '] ' .$e->getMessage());

            return false;
        }

        $emailObject = $emailTemplate;

        if ($massEmail->storeSentEmails() && !$isTest) {
            $this->entityManager->saveEntity($email);

            $emailObject = $email;
        }

        $queueItem->set('emailAddress', $target->get('emailAddress'));
        $queueItem->set('status', EmailQueueItem::STATUS_SENT);
        $queueItem->set('sentAt', date(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT));

        $this->entityManager->saveEntity($queueItem);

        if ($campaign) {
            $this->getCampaignService()->logSent(
                $campaign->getId(),
                $queueItem->getId(),
                $target,
                $emailObject,
                $target->get('emailAddress'),
                null,
                $queueItem->isTest()
            );
        }

        return true;
    }

    protected function getEmailTemplateService(): EmailTemplateService
    {
        if (!$this->emailTemplateService) {
            /** @var EmailTemplateService $service */
            $service = $this->serviceFactory->create(EmailTemplate::ENTITY_TYPE);

            $this->emailTemplateService = $service;
        }

        return $this->emailTemplateService;
    }

    protected function getCampaignService(): CampaignService
    {
        if (!$this->campaignService) {
            /** @var CampaignService $service */
            $service = $this->serviceFactory->create(Campaign::ENTITY_TYPE);

            $this->campaignService = $service;
        }

        return $this->campaignService;
    }

    protected function getSiteUrl(): string
    {
        return $this->config->get('massEmailSiteUrl') ?? $this->config->get('siteUrl');
    }
}
