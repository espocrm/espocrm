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

namespace Espo\Modules\Crm\Tools\MassEmail;

use Laminas\Mail\Message;

use Espo\Core\Mail\Account\GroupAccount\AccountFactory;
use Espo\Core\Mail\Exceptions\NoSmtp;
use Espo\Core\Mail\SenderParams;
use Espo\Core\Mail\SmtpParams;
use Espo\Entities\Attachment;
use Espo\Modules\Crm\Tools\MassEmail\MessagePreparator\Data;
use Espo\ORM\Collection;
use Espo\Entities\EmailTemplate;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\Entities\EmailAddress;
use Espo\Core\Exceptions\Error;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Mail\Sender;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\Entities\Email;
use Espo\Modules\Crm\Entities\Campaign;
use Espo\Modules\Crm\Entities\CampaignTrackingUrl;
use Espo\Modules\Crm\Entities\EmailQueueItem;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\Crm\Tools\Campaign\LogService as CampaignService;
use Espo\ORM\Entity;
use Espo\Tools\EmailTemplate\Data as TemplateData;
use Espo\Tools\EmailTemplate\Params as TemplateParams;
use Espo\Tools\EmailTemplate\Processor as TemplateProcessor;

use Exception;
use DateTime;

class SendingProcessor
{
    private const MAX_ATTEMPT_COUNT = 3;
    private const MAX_PER_HOUR_COUNT = 10000;

    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
        private Language $defaultLanguage,
        private EmailSender $emailSender,
        private Log $log,
        private AccountFactory $accountFactory,
        private CampaignService $campaignService,
        private MessageHeadersPreparator $headersPreparator,
        private TemplateProcessor $templateProcessor
    ) {}

    /**
     * @throws Error
     * @throws NoSmtp
     */
    public function process(MassEmail $massEmail, bool $isTest = false): void
    {
        $hourMaxSize = $this->config->get('massEmailMaxPerHourCount', self::MAX_PER_HOUR_COUNT);
        $batchMaxSize = $this->config->get('massEmailMaxPerBatchCount');

        if (!$isTest) {
            $threshold = new DateTime();
            $threshold->modify('-1 hour');

            $sentLastHourCount = $this->entityManager
                ->getRDBRepositoryByClass(EmailQueueItem::class)
                ->where([
                    'status' => EmailQueueItem::STATUS_SENT,
                    'sentAt>' => $threshold->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
                ])
                ->count();

            if ($sentLastHourCount >= $hourMaxSize) {
                return;
            }

            $hourMaxSize = $hourMaxSize - $sentLastHourCount;
        }

        $maxSize = $hourMaxSize;

        if ($batchMaxSize) {
            $maxSize = min($batchMaxSize, $maxSize);
        }

        $queueItemList = $this->entityManager
            ->getRDBRepositoryByClass(EmailQueueItem::class)
            ->sth()
            ->where([
                'status' => EmailQueueItem::STATUS_PENDING,
                'massEmailId' => $massEmail->getId(),
                'isTest' => $isTest,
            ])
            ->limit(0, $maxSize)
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

        /** @var Collection<Attachment> $attachmentList */
        $attachmentList = $this->entityManager
            ->getRDBRepositoryByClass(EmailTemplate::class)
            ->getRelation($emailTemplate, 'attachments')
            ->find();

        $smtpParams = null;
        $senderParams = SenderParams::create();

        $inboundEmailId = $massEmail->getInboundEmailId();

        if ($inboundEmailId) {
            $account = $this->accountFactory->create($inboundEmailId);

            $smtpParams = $account->getSmtpParams();

            if (
                !$account->isAvailableForSending() ||
                !$account->getEntity()->smtpIsForMassEmail() ||
                !$smtpParams
            ) {
                throw new Error("Mass Email: Group email account $inboundEmailId can't be used for mass email.");
            }

            if ($account->getEntity()->getReplyToAddress()) {
                $senderParams = $senderParams
                    ->withReplyToAddress(
                        $account->getEntity()->getReplyToAddress()
                    );
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
                $smtpParams,
                $senderParams
            );
        }

        if ($isTest) {
            return;
        }

        $countLeft = $this->entityManager
            ->getRDBRepositoryByClass(EmailQueueItem::class)
            ->where([
                'status' => EmailQueueItem::STATUS_PENDING,
                'massEmailId' => $massEmail->getId(),
                'isTest' => false,
            ])
            ->count();

        if ($countLeft === 0) {
            $massEmail->set('status', MassEmail::STATUS_COMPLETE);

            $this->entityManager->saveEntity($massEmail);
        }
    }

    /**
     * @param iterable<CampaignTrackingUrl> $trackingUrlList
     */
    private function getPreparedEmail(
        EmailQueueItem $queueItem,
        MassEmail $massEmail,
        EmailTemplate $emailTemplate,
        Entity $target,
        iterable $trackingUrlList = []
    ): ?Email {

        $emailAddress = $target->get('emailAddress');

        if (!$emailAddress) {
            return null;
        }

        $emailData = $this->templateProcessor->process(
            $emailTemplate,
            TemplateParams::create()
                ->withApplyAcl(false) // @todo Revise.
                ->withCopyAttachments(false), // @todo Revise.
            TemplateData::create()
                ->withParent($target)
        );

        $body = $emailData->getBody();

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
            if ($emailData->isHtml()) {
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

        if (
            $massEmail->getCampaignId() &&
            $this->config->get('massEmailOpenTracking')
        ) {
            if ($emailData->isHtml()) {
                $body .= '<br>' . $trackOpenedHtml;
            }
        }

        /** @var Email $email */
        $email = $this->entityManager
            ->getRDBRepositoryByClass(Email::class)
            ->getNew();

        $email
            ->addToAddress($emailAddress)
            ->setSubject($emailData->getSubject())
            ->setBody($body)
            ->setIsHtml($emailData->isHtml())
            ->setAttachmentIdList($emailData->getAttachmentIdList());

        if ($massEmail->getFromAddress()) {
            $email->setFromAddress($massEmail->getFromAddress());
        }

        $replyToAddress = $massEmail->getReplyToAddress();

        if ($replyToAddress) {
            $email->addReplyToAddress($replyToAddress);
        }

        return $email;
    }

    private function prepareQueueItemMessage(
        EmailQueueItem $queueItem,
        Sender $sender,
        Message $message,
        SenderParams $senderParams
    ): void {

        $id = $queueItem->getId();

        $this->headersPreparator->prepare($message->getHeaders(), new Data($id, $senderParams));

        $fromAddress = $senderParams->getFromAddress();

        if (
            $this->config->get('massEmailVerp') &&
            $fromAddress &&
            strpos($fromAddress, '@')
        ) {
            $bounceAddress = explode('@', $fromAddress)[0] . '+bounce-qid-' . $id .
                '@' . explode('@', $fromAddress)[1];

            $sender->withEnvelopeOptions(['from' => $bounceAddress]);
        }
    }

    private function setFailed(MassEmail $massEmail): void
    {
        $massEmail->set('status', MassEmail::STATUS_FAILED);

        $this->entityManager->saveEntity($massEmail);

        $queueItemList = $this->entityManager
            ->getRDBRepositoryByClass(EmailQueueItem::class)
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
     * @param Collection<Attachment> $attachmentList
     */
    private function sendQueueItem(
        EmailQueueItem $queueItem,
        MassEmail $massEmail,
        EmailTemplate $emailTemplate,
        $attachmentList,
        ?Campaign $campaign,
        bool $isTest,
        ?SmtpParams $smtpParams,
        SenderParams $senderParams
    ): void {

        /** @var ?EmailQueueItem $queueItemFetched */
        $queueItemFetched = $this->entityManager->getEntityById($queueItem->getEntityType(), $queueItem->getId());

        if (
            !$queueItemFetched ||
            $queueItemFetched->getStatus() !== EmailQueueItem::STATUS_PENDING
        ) {
            return;
        }

        $queueItem->set('status', EmailQueueItem::STATUS_SENDING);

        $this->entityManager->saveEntity($queueItem);

        $target = $this->entityManager->getEntityById($queueItem->getTargetType(), $queueItem->getTargetId());

        $emailAddress = $target?->get('emailAddress');

        if (
            !$target ||
            !$target->hasId() ||
            !$emailAddress
        ) {
            $queueItem->set('status', EmailQueueItem::STATUS_FAILED);

            $this->entityManager->saveEntity($queueItem);

            return;
        }

        /** @var EmailAddressRepository $emailAddressRepository */
        $emailAddressRepository = $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);

        $emailAddressRecord = $emailAddressRepository->getByAddress($emailAddress);

        if (
            $emailAddressRecord && (
                $emailAddressRecord->isInvalid() ||
                $emailAddressRecord->isOptedOut()
            )
        ) {
            $queueItem->set('status', EmailQueueItem::STATUS_FAILED);

            $this->entityManager->saveEntity($queueItem);

            return;
        }

        /** @var CampaignTrackingUrl[] $trackingUrlList */
        $trackingUrlList = [];

        if ($campaign) {
            /** @var Collection<CampaignTrackingUrl> $trackingUrlList */
            $trackingUrlList = $this->entityManager
                ->getRDBRepositoryByClass(Campaign::class)
                ->getRelation($campaign, 'trackingUrls')
                ->find();
        }

        $email = $this->getPreparedEmail($queueItem, $massEmail, $emailTemplate, $target, $trackingUrlList);

        if (!$email) {
            return;
        }

        if ($email->get('replyToAddress')) { // @todo Revise.
            $senderParams = $senderParams->withReplyToAddress(null);
        }

        if ($campaign) {
            $email->setLinkMultipleIdList('teams', $campaign->getLinkMultipleIdList('teams'));
        }

        $senderParams = $senderParams->withFromAddress(
            $massEmail->getFromAddress() ??
            $this->config->get('outboundEmailFromAddress')
        );

        if ($massEmail->getFromName()) {
            $senderParams = $senderParams->withFromName($massEmail->getFromName());
        }

        if ($massEmail->getReplyToName()) {
            $senderParams = $senderParams->withReplyToName($massEmail->getReplyToName());
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

            $this->prepareQueueItemMessage($queueItem, $sender, $message, $senderParams);

            $sender
                ->withParams($senderParams)
                ->withMessage($message)
                ->withAttachments($attachmentList)
                ->send($email);
        }
        catch (Exception $e) {
            $maxAttemptCount = $this->config->get('massEmailMaxAttemptCount', self::MAX_ATTEMPT_COUNT);

            $queueItem->getAttemptCount() >= $maxAttemptCount ?
                $queueItem->set('status', EmailQueueItem::STATUS_FAILED) :
                $queueItem->set('status', EmailQueueItem::STATUS_PENDING);

            $this->entityManager->saveEntity($queueItem);

            $this->log->error("Mass Email, send item: {$e->getCode()}, " . $e->getMessage());

            return;
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
            $this->campaignService->logSent($campaign->getId(), $queueItem, $emailObject);
        }
    }

    private function getSiteUrl(): string
    {
        return
            $this->config->get('massEmailSiteUrl') ??
            $this->config->get('siteUrl');
    }
}
