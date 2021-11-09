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

namespace Espo\Modules\Crm\Tools\MassEmail;

use Laminas\Mail\Message;

use Espo\Entities\EmailTemplate;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Repositories\EmailAddress as EmailAddressRepository;

use Espo\Core\{
    Exceptions\Error,
    ORM\EntityManager,
    ServiceFactory,
    Utils\Config,
    Utils\Language,
    Mail\EmailSender,
    Mail\Sender,
    Mail\Mail\Header\XQueueItemId,
    Utils\Log,
};

use Espo\{
    Modules\Crm\Entities\EmailQueueItem,
    Modules\Crm\Entities\Campaign,
    Modules\Crm\Services\Campaign as CampaignService,
    Services\EmailTemplate as EmailTemplateService,
    ORM\Entity,
    Entities\Email,
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

    private $campaignService = null;

    private $emailTemplateService = null;

    protected $log;

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

    public function process(MassEmail $massEmail, bool $isTest = false): void
    {
        $maxBatchSize = $this->config->get('massEmailMaxPerHourCount', self::MAX_PER_HOUR_COUNT);

        if (!$isTest) {
            $threshold = new DateTime();

            $threshold->modify('-1 hour');

            $sentLastHourCount = $this->entityManager
                ->getRDBRepository('EmailQueueItem')
                ->where([
                    'status' => 'Sent',
                    'sentAt>' => $threshold->format('Y-m-d H:i:s'),
                ])
                ->count();

            if ($sentLastHourCount >= $maxBatchSize) {
                return;
            }

            $maxBatchSize = $maxBatchSize - $sentLastHourCount;
        }

        $queueItemList = $this->entityManager
            ->getRDBRepository('EmailQueueItem')
            ->where([
                'status' => 'Pending',
                'massEmailId' => $massEmail->getId(),
                'isTest' => $isTest,
            ])
            ->limit(0, $maxBatchSize)
            ->find();

        $templateId = $massEmail->get('emailTemplateId');

        if (!$templateId) {
            $this->setFailed($massEmail);

            return;
        }

        $campaign = null;

        $campaignId = $massEmail->get('campaignId');

        if ($campaignId) {
            $campaign = $this->entityManager->getEntity('Campaign', $campaignId);
        }

        $emailTemplate = $this->entityManager->getEntity('EmailTemplate', $templateId);

        if (!$emailTemplate) {
            $this->setFailed($massEmail);

            return;
        }

        /** @var iterable<\Espo\Entities\Attachment> */
        $attachmentList = $this->entityManager
            ->getRDBRepository('EmailTemplate')
            ->getRelation($emailTemplate, 'attachments')
            ->find();

        $smtpParams = null;

        if ($massEmail->get('inboundEmailId')) {

            $inboundEmail = $this->entityManager->getEntity('InboundEmail', $massEmail->get('inboundEmailId'));

            if (!$inboundEmail) {
                throw new Error(
                    "Group Email Account '" . $massEmail->get('inboundEmailId') . "' is not available."
                );
            }

            if (
                $inboundEmail->get('status') !== 'Active' ||
                !$inboundEmail->get('useSmtp') ||
                !$inboundEmail->get('smtpIsForMassEmail')
            ) {
                throw new Error(
                    "Group Email Account '" . $massEmail->get('inboundEmailId') . "' can't be used for Mass Email."
                );
            }

            $inboundEmailService = $this->serviceFactory->create('InboundEmail');

            $smtpParams = $inboundEmailService->getSmtpParamsFromAccount($inboundEmail);

            if (!$smtpParams) {
                throw new Error(
                    "Group Email Account '" . $massEmail->get('inboundEmailId') . "' has no SMTP params."
                );
            }

            if ($inboundEmail->get('replyToAddress')) {
                $smtpParams['replyToAddress'] = $inboundEmail->get('replyToAddress');
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
                ->getRDBRepository('EmailQueueItem')
                ->where([
                    'status' => 'Pending',
                    'massEmailId' => $massEmail->getId(),
                    'isTest' => false,
                ])
                ->count();

            if ($countLeft == 0) {
                $massEmail->set('status', 'Complete');

                $this->entityManager->saveEntity($massEmail);
            }
        }
    }

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
            $this->defaultLanguage->translate('Unsubscribe', 'labels', 'Campaign') .
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

        $trackImageAlt = $this->defaultLanguage->translate('Campaign', 'scopeNames');

        $trackOpenedUrl = $this->getSiteUrl() . '?entryPoint=campaignTrackOpened&id=' . $queueItem->getId();

        $trackOpenedHtml =
            '<img alt="' . $trackImageAlt . '" width="1" height="1" border="0" src="' . $trackOpenedUrl . '">';

        if ($massEmail->get('campaignId') && $this->config->get('massEmailOpenTracking')) {
            if ($emailData['isHtml']) {
                $body .= '<br>' . $trackOpenedHtml;
            }
        }

        $emailData['body'] = $body;

        $email = $this->entityManager->getEntity('Email');

        $email->set($emailData);

        $emailAddress = $target->get('emailAddress');

        if (empty($emailAddress)) {
            return null;
        }

        $email->set('to', $emailAddress);

        if ($massEmail->get('fromAddress')) {
            $email->set('from', $massEmail->get('fromAddress'));
        }

        if ($massEmail->get('replyToAddress')) {
            $email->set('replyTo', $massEmail->get('replyToAddress'));
        }

        return $email;
    }

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

    protected function setFailed(Entity $massEmail): void
    {
        $massEmail->set('status', 'Failed');

        $this->entityManager->saveEntity($massEmail);

        $queueItemList = $this->entityManager
            ->getRDBRepository('EmailQueueItem')
            ->where([
                'status' => 'Pending',
                'massEmailId' => $massEmail->getId(),
            ])
            ->find();

        foreach ($queueItemList as $queueItem) {
            $queueItem->set('status', 'Failed');

            $this->entityManager->saveEntity($queueItem);
        }
    }

    /**
     * @param iterable<\Espo\Entities\Attachment> $attachmentList
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

        $queueItemFetched = $this->entityManager->getEntity($queueItem->getEntityType(), $queueItem->getId());

        if ($queueItemFetched->get('status') !== 'Pending') {
            return false;
        }

        $queueItem->set('status', 'Sending');

        $this->entityManager->saveEntity($queueItem);

        $target = $this->entityManager->getEntity($queueItem->get('targetType'), $queueItem->get('targetId'));

        $emailAddress = $target->get('emailAddress');

        if (
            !$target ||
            !$target->getId() ||
            !$emailAddress
        ) {
            $queueItem->set('status', 'Failed');

            $this->entityManager->saveEntity($queueItem);

            return false;
        }

        /** @var EmailAddressRepository $emailAddressRepository */
        $emailAddressRepository = $this->entityManager->getRepository('EmailAddress');

        $emailAddressRecord = $emailAddressRepository->getByAddress($emailAddress);

        if ($emailAddressRecord) {
            if ($emailAddressRecord->get('invalid') || $emailAddressRecord->get('optOut')) {
                $queueItem->set('status', 'Failed');

                $this->entityManager->saveEntity($queueItem);

                return false;
            }
        }

        $trackingUrlList = [];

        if ($campaign) {
            $trackingUrlList = $this->entityManager
                ->getRDBRepository('Campaign')
                ->getRelation($campaign, 'trackingUrls')
                ->find();
        }

        $email = $this->getPreparedEmail($queueItem, $massEmail, $emailTemplate, $target, $trackingUrlList);

        if (!$email) {
            return false;
        }

        if ($email->get('replyToAddress')) {
            unset($smtpParams['replyToAddress']);
        }

        if ($campaign) {
            $email->setLinkMultipleIdList(
                'teams',
                $campaign->getLinkMultipleIdList('teams')
            );
        }

        $params = [];

        if ($massEmail->get('fromName')) {
            $params['fromName'] = $massEmail->get('fromName');
        }

        if ($massEmail->get('replyToName')) {
            $params['replyToName'] = $massEmail->get('replyToName');
        }

        try {
            $attemptCount = $queueItem->get('attemptCount');

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

            if ($queueItem->get('attemptCount') >= $maxAttemptCount) {
                $queueItem->set('status', 'Failed');
            }
            else {
                $queueItem->set('status', 'Pending');
            }

            $this->entityManager->saveEntity($queueItem);

            $this->log->error('MassEmail#sendQueueItem: [' . $e->getCode() . '] ' .$e->getMessage());

            return false;
        }

        $emailObject = $emailTemplate;

        if ($massEmail->get('storeSentEmails') && !$isTest) {
            $this->entityManager->saveEntity($email);

            $emailObject = $email;
        }

        $queueItem->set('emailAddress', $target->get('emailAddress'));

        $queueItem->set('status', 'Sent');
        $queueItem->set('sentAt', date('Y-m-d H:i:s'));

        $this->entityManager->saveEntity($queueItem);

        if ($campaign) {
            $this->getCampaignService()->logSent(
                $campaign->getId(),
                $queueItem->getId(),
                $target,
                $emailObject,
                $target->get('emailAddress'),
                null,
                $queueItem->get('isTest')
            );
        }

        return true;
    }

    protected function getEmailTemplateService(): EmailTemplateService
    {
        if (!$this->emailTemplateService) {
            $this->emailTemplateService = $this->serviceFactory->create('EmailTemplate');
        }

        return $this->emailTemplateService;
    }

    protected function getCampaignService(): CampaignService
    {
        if (!$this->campaignService) {
            $this->campaignService = $this->serviceFactory->create('Campaign');
        }

        return $this->campaignService;
    }

    protected function getSiteUrl(): string
    {
        return $this->config->get('massEmailSiteUrl') ?? $this->config->get('siteUrl');
    }
}
