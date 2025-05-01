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

namespace Espo\Modules\Crm\Tools\MassEmail;

use Espo\Modules\Crm\Tools\MassEmail\MessagePreparator\Headers;
use Laminas\Mail\Message;

use Espo\Core\Field\DateTime;
use Espo\Core\Mail\ConfigDataProvider;
use Espo\ORM\EntityCollection;
use Espo\Core\Name\Field;
use Espo\Tools\EmailTemplate\Result;
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
        private TemplateProcessor $templateProcessor,
        private ConfigDataProvider $configDataProvider,
        private Config\ApplicationConfig $applicationConfig,
    ) {}

    /**
     * @throws Error
     * @throws NoSmtp
     */
    public function process(MassEmail $massEmail, bool $isTest = false): void
    {
        if ($this->toSkipAsInactive($massEmail, $isTest)) {
            $this->log->notice("Skipping mass email {id} queue for inactive campaign.", [
                'id' => $massEmail->getId(),
            ]);

            return;
        }

        $maxSize = 0;

        if ($this->handleMaxSize($isTest, $maxSize)) {
            return;
        }

        $emailTemplate = $massEmail->getEmailTemplate();

        if (!$emailTemplate) {
            $this->setFailed($massEmail);

            return;
        }

        $attachmentList = $emailTemplate->getAttachments();
        [$smtpParams, $senderParams] = $this->getSenderParams($massEmail);
        $queueItemList = $this->getQueueItems($massEmail, $isTest, $maxSize);

        foreach ($queueItemList as $queueItem) {
            $this->sendQueueItem(
                queueItem: $queueItem,
                massEmail: $massEmail,
                emailTemplate: $emailTemplate,
                attachmentList: $attachmentList,
                isTest: $isTest,
                smtpParams: $smtpParams,
                senderParams: $senderParams,
            );
        }

        if ($isTest) {
            return;
        }

        if ($this->getCountLeft($massEmail) !== 0) {
            return;
        }

        $this->setComplete($massEmail);
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

        $emailAddress = $target->get(Field::EMAIL_ADDRESS);

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

        $body = $this->prepareBody($massEmail, $queueItem, $emailData, $trackingUrlList);

        $email = $this->entityManager->getRDBRepositoryByClass(Email::class)->getNew();

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
        SenderParams $senderParams,
    ): void {

        $id = $queueItem->getId();

        $headers = new Headers($sender);

        $this->headersPreparator->prepare($headers, new Data($id, $senderParams, $queueItem));

        $fromAddress = $senderParams->getFromAddress();

        if (
            $this->config->get('massEmailVerp') &&
            $fromAddress &&
            strpos($fromAddress, '@')
        ) {
            $bounceAddress = explode('@', $fromAddress)[0] . '+bounce-qid-' . $id .
                '@' . explode('@', $fromAddress)[1];

            $sender->withEnvelopeFromAddress($bounceAddress);
        }
    }

    private function setFailed(MassEmail $massEmail): void
    {
        $massEmail->setStatus(MassEmail::STATUS_FAILED);

        $this->entityManager->saveEntity($massEmail);

        $queueItemList = $this->entityManager
            ->getRDBRepositoryByClass(EmailQueueItem::class)
            ->where([
                'status' => EmailQueueItem::STATUS_PENDING,
                'massEmailId' => $massEmail->getId(),
            ])
            ->find();

        foreach ($queueItemList as $queueItem) {
            $this->setItemFailed($queueItem);
        }
    }

    /**
     * @param EntityCollection<Attachment> $attachmentList
     */
    private function sendQueueItem(
        EmailQueueItem $queueItem,
        MassEmail $massEmail,
        EmailTemplate $emailTemplate,
        EntityCollection $attachmentList,
        bool $isTest,
        ?SmtpParams $smtpParams,
        SenderParams $senderParams,
    ): void {

        if ($this->isNotPending($queueItem)) {
            return;
        }

        $this->setItemSending($queueItem);

        $target = $this->entityManager->getEntityById($queueItem->getTargetType(), $queueItem->getTargetId());

        $emailAddress = $target?->get(Field::EMAIL_ADDRESS);

        if (
            !$target ||
            !$target->hasId() ||
            !$emailAddress
        ) {
            $this->setItemFailed($queueItem);

            return;
        }

        $emailAddressRecord = $this->getEmailAddressRepository()->getByAddress($emailAddress);

        if ($emailAddressRecord) {
            if ($emailAddressRecord->isInvalid()) {
                $this->setItemFailed($queueItem);

                return;
            }

            if (
                $emailAddressRecord->isOptedOut() &&
                $massEmail->getCampaign()?->getType() !== Campaign::TYPE_INFORMATIONAL_EMAIL
            ) {
                $this->setItemFailed($queueItem);

                return;
            }
        }

        $email = $this->getPreparedEmail(
            queueItem: $queueItem,
            massEmail: $massEmail,
            emailTemplate: $emailTemplate,
            target: $target,
            trackingUrlList: $this->getTrackingUrls($massEmail->getCampaign()),
        );

        if (!$email) {
            return;
        }

        $senderParams = $this->prepareItemSenderParams($email, $senderParams, $massEmail);

        $queueItem->incrementAttemptCount();

        $sender = $this->emailSender->create();

        if ($smtpParams) {
            $sender->withSmtpParams($smtpParams);
        }

        try {
            $this->prepareQueueItemMessage($queueItem, $sender, $senderParams);

            $sender
                ->withParams($senderParams)
                ->withAttachments($attachmentList)
                ->send($email);
        } catch (Exception $e) {
            $this->processException($queueItem, $e);

            return;
        }

        $emailObject = $emailTemplate;

        if ($massEmail->storeSentEmails() && !$isTest) {
            $this->entityManager->saveEntity($email);

            $emailObject = $email;
        }

        $this->setItemSent($queueItem, $emailAddress);

        if ($massEmail->getCampaign()) {
            $this->campaignService->logSent($massEmail->getCampaign()->getId(), $queueItem, $emailObject);
        }
    }

    private function getSiteUrl(): string
    {
        return $this->config->get('massEmailSiteUrl') ??
            $this->applicationConfig->getSiteUrl();
    }

    /**
     * @throws Error
     * @throws NoSmtp
     * @return array{?SmtpParams, SenderParams}
     */
    private function getSenderParams(MassEmail $massEmail): array
    {
        $smtpParams = null;
        $senderParams = SenderParams::create();

        $inboundEmailId = $massEmail->getInboundEmailId();

        if (!$inboundEmailId) {
            return [$smtpParams, $senderParams];
        }

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
                ->withReplyToAddress($account->getEntity()->getReplyToAddress());
        }

        return [$smtpParams, $senderParams];
    }

    private function getCountLeft(MassEmail $massEmail): int
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(EmailQueueItem::class)
            ->where([
                'status' => EmailQueueItem::STATUS_PENDING,
                'massEmailId' => $massEmail->getId(),
                'isTest' => false,
            ])
            ->count();
    }

    private function processException(EmailQueueItem $queueItem, Exception $e): void
    {
        $maxAttemptCount = $this->config->get('massEmailMaxAttemptCount', self::MAX_ATTEMPT_COUNT);

        $queueItem->getAttemptCount() >= $maxAttemptCount ?
            $queueItem->setStatus(EmailQueueItem::STATUS_FAILED) :
            $queueItem->setStatus(EmailQueueItem::STATUS_PENDING);

        $this->entityManager->saveEntity($queueItem);

        $this->log->error("Mass Email, send item: {$e->getCode()}, {$e->getMessage()}");
    }

    private function getEmailAddressRepository(): EmailAddressRepository
    {
        /** @var EmailAddressRepository */
        return $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);
    }

    private function isNotPending(EmailQueueItem $queueItem): bool
    {
        /** @var ?EmailQueueItem $queueItemFetched */
        $queueItemFetched = $this->entityManager->getEntityById(EmailQueueItem::ENTITY_TYPE, $queueItem->getId());

        if (!$queueItemFetched) {
            return true;
        }

        return $queueItemFetched->getStatus() !== EmailQueueItem::STATUS_PENDING;
    }

    /**
     * @return Collection<EmailQueueItem>
     */
    private function getQueueItems(MassEmail $massEmail, bool $isTest, int $maxSize): Collection
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(EmailQueueItem::class)
            ->sth()
            ->where([
                'status' => EmailQueueItem::STATUS_PENDING,
                'massEmailId' => $massEmail->getId(),
                'isTest' => $isTest,
            ])
            ->limit(0, $maxSize)
            ->find();
    }

    /**
     * @return bool Whether to skip.
     */
    private function handleMaxSize(bool $isTest, int &$maxSize): bool
    {
        $hourMaxSize = $this->config->get('massEmailMaxPerHourCount', self::MAX_PER_HOUR_COUNT);
        $batchMaxSize = $this->config->get('massEmailMaxPerBatchCount');

        if (!$isTest) {
            $threshold = DateTime::createNow()->addHours(-1);

            $sentLastHourCount = $this->entityManager
                ->getRDBRepositoryByClass(EmailQueueItem::class)
                ->where([
                    'status' => EmailQueueItem::STATUS_SENT,
                    'sentAt>' => $threshold->toString(),
                ])
                ->count();

            if ($sentLastHourCount >= $hourMaxSize) {
                return true;
            }

            $hourMaxSize = $hourMaxSize - $sentLastHourCount;
        }

        $maxSize = $hourMaxSize;

        if ($batchMaxSize) {
            $maxSize = min($batchMaxSize, $maxSize);
        }

        return false;
    }

    private function setComplete(MassEmail $massEmail): void
    {
        $massEmail->setStatus(MassEmail::STATUS_COMPLETE);

        $this->entityManager->saveEntity($massEmail);
    }

    private function setItemSending(EmailQueueItem $queueItem): void
    {
        $queueItem->setStatus(EmailQueueItem::STATUS_SENDING);

        $this->entityManager->saveEntity($queueItem);
    }

    private function setItemFailed(EmailQueueItem $queueItem): void
    {
        $queueItem->setStatus(EmailQueueItem::STATUS_FAILED);

        $this->entityManager->saveEntity($queueItem);
    }

    private function setItemSent(EmailQueueItem $queueItem, string $emailAddress): void
    {
        $queueItem->setEmailAddress($emailAddress);
        $queueItem->setStatus(EmailQueueItem::STATUS_SENT);
        $queueItem->setSentAtNow();

        $this->entityManager->saveEntity($queueItem);
    }

    /**
     * @return iterable<CampaignTrackingUrl>
     */
    private function getTrackingUrls(?Campaign $campaign): iterable
    {
        if (!$campaign || $campaign->getType() === Campaign::TYPE_INFORMATIONAL_EMAIL) {
            return [];
        }

        /** @var Collection<CampaignTrackingUrl> */
        return $this->entityManager
            ->getRelation($campaign, 'trackingUrls')
            ->find();
    }

    private function prepareItemSenderParams(
        Email $email,
        SenderParams $senderParams,
        MassEmail $massEmail
    ): SenderParams {

        $campaign = $massEmail->getCampaign();

        if ($email->get('replyToAddress')) { // @todo Revise.
            $senderParams = $senderParams->withReplyToAddress(null);
        }

        if ($campaign) {
            $email->setLinkMultipleIdList(Field::TEAMS, $campaign->getLinkMultipleIdList(Field::TEAMS));
        }

        $senderParams = $senderParams->withFromAddress(
            $massEmail->getFromAddress() ??
            $this->configDataProvider->getSystemOutboundAddress()
        );

        if ($massEmail->getFromName()) {
            $senderParams = $senderParams->withFromName($massEmail->getFromName());
        }

        if ($massEmail->getReplyToName()) {
            $senderParams = $senderParams->withReplyToName($massEmail->getReplyToName());
        }

        return $senderParams;
    }

    private function getOptOutUrl(EmailQueueItem $queueItem): string
    {
        return "{$this->getSiteUrl()}?entryPoint=unsubscribe&id={$queueItem->getId()}";
    }

    private function getOptOutLink(string $optOutUrl): string
    {
        $label = $this->defaultLanguage->translateLabel('Unsubscribe', 'labels', Campaign::ENTITY_TYPE);

        return "<a href=\"$optOutUrl\">$label</a>";
    }

    private function getTrackUrl(mixed $trackingUrl, EmailQueueItem $queueItem): string
    {
        $siteUrl = $this->getSiteUrl();

        $id1 = $trackingUrl->getId();
        $id2 = $queueItem->getId();

        return "$siteUrl?entryPoint=campaignUrl&id=$id1&queueItemId=$id2";
    }

    /**
     * @param iterable<CampaignTrackingUrl> $trackingUrlList
     */
    private function prepareBody(
        MassEmail $massEmail,
        EmailQueueItem $queueItem,
        Result $emailData,
        iterable $trackingUrlList,
    ): string {

        $body = $this->addBodyLinks(
            massEmail: $massEmail,
            queueItem: $queueItem,
            emailData: $emailData,
            body: $emailData->getBody(),
            trackingUrlList: $trackingUrlList,
        );

        return $this->addBodyTracking(
            massEmail: $massEmail,
            queueItem: $queueItem,
            emailData: $emailData,
            body: $body,
        );
    }

    private function toSkipAsInactive(MassEmail $massEmail, bool $isTest): bool
    {
        return !$isTest &&
            $massEmail->getCampaign() &&
            $massEmail->getCampaign()->getStatus() === Campaign::STATUS_INACTIVE;
    }

    /**
     * @param iterable<CampaignTrackingUrl> $trackingUrlList
     */
    private function addBodyLinks(
        MassEmail $massEmail,
        EmailQueueItem $queueItem,
        Result $emailData,
        string $body,
        iterable $trackingUrlList,
    ): string {

        $optOutUrl = $this->getOptOutUrl($queueItem);
        $optOutLink = $this->getOptOutLink($optOutUrl);

        if (!$this->isInformational($massEmail)) {
            $body = str_replace('{optOutUrl}', $optOutUrl, $body);
            $body = str_replace('{optOutLink}', $optOutLink, $body);
        }

        $body = str_replace('{queueItemId}', $queueItem->getId(), $body);

        foreach ($trackingUrlList as $trackingUrl) {
            $url = $this->getTrackUrl($trackingUrl, $queueItem);

            $body = str_replace($trackingUrl->getUrlToUse(), $url, $body);
        }

        return $this->addMandatoryBodyOptOutLink($massEmail, $queueItem, $emailData, $body);
    }

    private function addMandatoryBodyOptOutLink(
        MassEmail $massEmail,
        EmailQueueItem $queueItem,
        Result $emailData,
        string $body,
    ): string {

        if ($this->config->get('massEmailDisableMandatoryOptOutLink')) {
            return $body;
        }

        if ($this->isInformational($massEmail)) {
            return $body;
        }

        if (stripos($body, '?entryPoint=unsubscribe&id') !== false) {
            return $body;
        }

        $optOutUrl = $this->getOptOutUrl($queueItem);
        $optOutLink = $this->getOptOutLink($optOutUrl);

        if ($emailData->isHtml()) {
            $body .= "<br><br>" . $optOutLink;
        } else {
            $body .= "\n\n" . $optOutUrl;
        }

        return $body;
    }

    private function addBodyTracking(
        MassEmail $massEmail,
        EmailQueueItem $queueItem,
        Result $emailData,
        string $body
    ): string {

        if (!$massEmail->getCampaign()) {
            return $body;
        }

        if ($massEmail->getCampaign()->getType() === Campaign::TYPE_INFORMATIONAL_EMAIL) {
            return $body;
        }

        if (!$this->config->get('massEmailOpenTracking')) {
            return $body;
        }

        if (!$emailData->isHtml()) {
            return $body;
        }

        $alt = $this->defaultLanguage->translateLabel('Campaign', 'scopeNames');

        $url = "{$this->getSiteUrl()}?entryPoint=campaignTrackOpened&id={$queueItem->getId()}";

        /** @noinspection HtmlDeprecatedAttribute */
        $trackOpenedHtml = "<img alt=\"$alt\" width=\"1\" height=\"1\" border=\"0\" src=\"$url\">";

        $body .= '<br>' . $trackOpenedHtml;

        return $body;
    }

    private function isInformational(MassEmail $massEmail): bool
    {
        return $massEmail->getCampaign()?->getType() === Campaign::TYPE_INFORMATIONAL_EMAIL;
    }
}
