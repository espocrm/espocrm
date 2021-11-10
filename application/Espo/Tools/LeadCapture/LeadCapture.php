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

namespace Espo\Tools\LeadCapture;

use Espo\Modules\Crm\Services\Campaign as CampaignService;
use Espo\Services\EmailTemplate as EmailTemplateService;
use Espo\Services\InboundEmail as InboundEmailService;

use Espo\Entities\UniqueId;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;

use Espo\Core\{
    Exceptions\Error,
    Exceptions\NotFound,
    Exceptions\BadRequest,
    ORM\EntityManager,
    Utils\FieldUtil,
    Utils\Language,
    HookManager,
    Mail\EmailSender,
    Utils\Config,
    Utils\DateTime as DateTimeUtil,
    Utils\Log,
    Job\QueueName,
};

use Espo\{
    ORM\Entity,
    Entities\LeadCapture as LeadCaptureEntity,
    Modules\Crm\Entities\Lead as LeadEntity,
};

use stdClass;
use DateTime;

class LeadCapture
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var FieldUtil */
    protected $fieldUtil;

    /** @var Language */
    protected $defaultLanguage;

    /** @var HookManager */
    protected $hookManager;

    /** @var EmailSender */
    protected $emailSender;

    /** @var Config */
    protected $config;

    /** @var DateTimeUtil */
    protected $dateTime;

    /** @var Log */
    protected $log;

    /** @var CampaignService */
    private $campaignService;

    /** @var EmailTemplateService */
    private $emailTemplateService;

    /** @var InboundEmailService */
    private $inboundEmailService;

    public function __construct(
        EntityManager $entityManager,
        FieldUtil $fieldUtil,
        Language $defaultLanguage,
        HookManager $hookManager,
        EmailSender $emailSender,
        Config $config,
        DateTimeUtil $dateTime,
        Log $log,
        CampaignService $campaignService,
        EmailTemplateService $emailTemplateService,
        InboundEmailService $inboundEmailService
    ) {
        $this->entityManager = $entityManager;
        $this->fieldUtil = $fieldUtil;
        $this->defaultLanguage = $defaultLanguage;
        $this->hookManager = $hookManager;
        $this->emailSender = $emailSender;
        $this->config = $config;
        $this->dateTime = $dateTime;
        $this->log = $log;
        $this->campaignService = $campaignService;
        $this->emailTemplateService = $emailTemplateService;
        $this->inboundEmailService = $inboundEmailService;
    }

    public function capture(string $apiKey, stdClass $data)
    {
        $leadCapture = $this->entityManager
            ->getRDBRepository('LeadCapture')
            ->where([
                'apiKey' => $apiKey,
                'isActive' => true,
            ])
            ->findOne();

        if (!$leadCapture) {
            throw new NotFound('Api key is not valid.');
        }

        if (!$leadCapture->get('optInConfirmation')) {
            $this->proceed($leadCapture, $data);

            return;
        }

        if (empty($data->emailAddress)) {
            throw new Error('LeadCapture: No emailAddress passed in the payload.');
        }

        if (!$leadCapture->get('optInConfirmationEmailTemplateId')) {
            throw new Error('LeadCapture: No optInConfirmationEmailTemplate specified.');
        }

        $lead = $this->getLeadWithPopulatedData($leadCapture, $data);

        $target = $lead;

        $duplicateData = $this->findLeadDuplicates($leadCapture, $lead);

        if ($duplicateData['lead']) {
            $target = $duplicateData['lead'];
        }

        if ($duplicateData['contact']) {
            $target = $duplicateData['contact'];
        }

        $hasDuplicate = $duplicateData['lead'] || $duplicateData['contact'];

        $isLogged = false;

        if ($hasDuplicate) {
            $this->log($leadCapture, $target, $data, false);

            $isLogged = true;

            if ($leadCapture->get('skipOptInConfirmationIfSubscribed') && $leadCapture->get('targetListId')) {
                $isAlreadyOptedIn = $this->isTargetOptedIn($target, $leadCapture->get('targetListId'));

                if ($isAlreadyOptedIn) {
                    $this->log->debug("LeadCapture: Already opted in. Skipped.");

                    return;
                }
            }
        }

        if ($leadCapture->get('createLeadBeforeOptInConfirmation')) {
            if (!$hasDuplicate) {
                $this->entityManager->saveEntity($lead);

                $this->log($leadCapture, $target, $data, true);

                $isLogged = true;
            }
        }

        $lifetime = $leadCapture->get('optInConfirmationLifetime');

        if (!$lifetime) {
            $lifetime = 100;
        }

        $dt = new DateTime();

        $dt->modify('+' . $lifetime . ' hours');

        $terminateAt = $dt->format('Y-m-d H:i:s');

        $uniqueId = $this->entityManager->getEntity('UniqueId');

        $uniqueId->set([
            'terminateAt' => $terminateAt,
            'data' => (object) [
                'leadCaptureId' => $leadCapture->getId(),
                'data' => $data,
                'leadId' => $lead->getId(),
                'isLogged' => $isLogged,
            ],
        ]);

        $this->entityManager->saveEntity($uniqueId);

        $job = $this->entityManager->getEntity('Job');

        $job->set([
            'serviceName' => 'LeadCapture',
            'methodName' => 'jobOptInConfirmation',
            'data' => (object) [
                'id' => $uniqueId->get('name'),
            ],
            'queue' => QueueName::E0,
        ]);

        $this->entityManager->saveEntity($job);
    }

    protected function proceed(
        LeadCaptureEntity $leadCapture,
        stdClass $data,
        ?string $leadId = null,
        bool $isLogged = false
    ): void {

        if ($leadId) {
            $lead = $this->entityManager->getEntity('Lead', $leadId);
        } else {
            $lead = $this->getLeadWithPopulatedData($leadCapture, $data);
        }

        $campaign = null;

        if ($leadCapture->get('campaignId')) {
            $campaign = $this->entityManager->getEntity('Campaign', $leadCapture->get('campaignId'));
        }

        $toRelateLead = false;

        $target = $lead;

        $duplicateData = $this->findLeadDuplicates($leadCapture, $lead);

        $duplicate = $duplicateData['lead'];
        $contact = $duplicateData['contact'];

        $targetLead = $duplicateData['lead'] ?? $lead;

        if ($contact) {
            assert($contact instanceof Contact);

            $target = $contact;
        }

        if ($duplicate) {
            assert($duplicate instanceof Lead);

            $lead = $duplicate;

            if (!$contact) {
                $target = $lead;
            }
        }

        if ($targetLead) {
            assert($targetLead instanceof Lead);
        }

        $isContactOptedIn = false;

        if ($leadCapture->get('subscribeToTargetList') && $leadCapture->get('targetListId')) {
            $isAlreadyOptedIn = false;

            if ($contact && $leadCapture->get('subscribeContactToTargetList')) {
                $isAlreadyOptedIn = $this->isTargetOptedIn($contact, $leadCapture->get('targetListId'));

                $isContactOptedIn = $isAlreadyOptedIn;

                if (!$isAlreadyOptedIn) {
                    $this->entityManager
                        ->getRDBRepository('Contact')
                        ->relate($contact, 'targetLists', $leadCapture->get('targetListId'), [
                            'optedOut' => false,
                        ]);

                    $isAlreadyOptedIn = true;

                    if ($campaign) {
                        $this->campaignService->logOptedIn($campaign->getId(), null, $contact);
                    }

                    $targetList = $this->entityManager->getEntity('TargetList', $leadCapture->get('targetListId'));

                    if ($targetList) {
                        $this->hookManager->process('TargetList', 'afterOptIn', $targetList, [], [
                           'link' => 'contacts',
                           'targetId' => $contact->getId(),
                           'targetType' => 'Contact',
                           'leadCaptureId' => $leadCapture->getId(),
                        ]);
                    }
                }
            }

            if (!$isAlreadyOptedIn) {
                if ($targetLead->isNew()) {
                    $toRelateLead = true;
                }
                else {
                    $isAlreadyOptedIn = $this->isTargetOptedIn($targetLead, $leadCapture->get('targetListId'));

                    if (!$isAlreadyOptedIn) {
                        $toRelateLead = true;
                    }
                }
            }
        }

        if (
            $contact &&
            (!$isContactOptedIn || !$leadCapture->get('subscribeToTargetList')) &&
            $leadCapture->get('subscribeContactToTargetList')
        ) {
            $this->hookManager->process('LeadCapture', 'afterLeadCapture', $leadCapture, [], [
               'targetId' => $contact->getId(),
               'targetType' => 'Contact',
            ]);

            $this->hookManager->process('Contact', 'afterLeadCapture', $contact, [], [
               'leadCaptureId' => $leadCapture->getId(),
            ]);
        }

        $isNew = !$duplicate && !$contact;

        if (!$contact || !$leadCapture->get('subscribeContactToTargetList')) {
            if ($leadCapture->get('targetTeamId')) {
                $lead->addLinkMultipleId('teams', $leadCapture->get('targetTeamId'));
            }

            $this->entityManager->saveEntity($lead);

            if (!$duplicate && $campaign) {
                $this->campaignService->logLeadCreated($campaign->getId(), $lead);
            }
        }

        if ($toRelateLead && $targetLead->getId()) {
            $this->entityManager
                ->getRDBRepository('Lead')
                ->relate($targetLead, 'targetLists', $leadCapture->get('targetListId'), [
                    'optedOut' => false,
                ]);

            if ($campaign) {
                $this->campaignService->logOptedIn($campaign->getId(), null, $targetLead);
            }

            $targetList = $this->entityManager->getEntity('TargetList', $leadCapture->get('targetListId'));

            if ($targetList) {
                $this->hookManager->process('TargetList', 'afterOptIn', $targetList, [], [
                   'link' => 'leads',
                   'targetId' => $targetLead->getId(),
                   'targetType' => 'Lead',
                   'leadCaptureId' => $leadCapture->getId(),
                ]);
            }
        }

        if ($toRelateLead  || !$leadCapture->get('subscribeToTargetList')) {
            $this->hookManager->process('LeadCapture', 'afterLeadCapture', $leadCapture, [], [
               'targetId' => $targetLead->getId(),
               'targetType' => 'Lead',
            ]);

            $this->hookManager->process('Lead', 'afterLeadCapture', $targetLead, [], [
               'leadCaptureId' => $leadCapture->getId(),
            ]);
        }

        if (!$isLogged) {
            $this->log($leadCapture, $target, $data, $isNew);
        }
    }

    public function confirmOptIn(string $id): stdClass
    {
        /** @var UniqueId|null $uniqueId */
        $uniqueId = $this->entityManager
            ->getRDBRepository('UniqueId')
            ->where([
                'name' => $id
            ])
            ->findOne();

        if (!$uniqueId) {
            throw new NotFound("LeadCapture Confirm: UniqueId not found.");
        }

        $uniqueIdData = $uniqueId->getData();

        if (empty($uniqueIdData->data)) {
            throw new Error("LeadCapture Confirm: data not found.");
        }

        if (empty($uniqueIdData->leadCaptureId)) {
            throw new Error("LeadCapture Confirm: leadCaptureId not found.");
        }

        $data = $uniqueIdData->data;
        $leadCaptureId = $uniqueIdData->leadCaptureId;
        $leadId = $uniqueIdData->leadId ?? null;
        $isLogged = $uniqueIdData->isLogged ?? false;

        $terminateAt = $uniqueId->get('terminateAt');

        if (is_string($terminateAt) && time() > strtotime($terminateAt)) {
            return (object) [
                'status' => 'expired',
                'message' => $this->defaultLanguage->translate('optInConfirmationExpired', 'messages', 'LeadCapture'),
            ];
        }

        $leadCapture = $this->entityManager->getEntity('LeadCapture', $leadCaptureId);

        if (!$leadCapture) {
            throw new Error("LeadCapture Confirm: LeadCapture not found.");
        }

        if (empty($uniqueIdData->isProcessed)) {
            $this->proceed($leadCapture, $data, $leadId, $isLogged);

            $uniqueIdData->isProcessed = true;

            $uniqueId->set('data', $uniqueIdData);

            $this->entityManager->saveEntity($uniqueId);
        }

        return (object) [
            'status' => 'success',
            'message' => $leadCapture->get('optInConfirmationSuccessMessage'),
            'leadCaptureName' => $leadCapture->get('name'),
            'leadCaptureId' => $leadCapture->getId(),
        ];
    }

    public function sendOptInConfirmation(string $id)
    {
        /** @var ?UniqueId $uniqueId */
        $uniqueId = $this->entityManager
            ->getRDBRepository('UniqueId')
            ->where([
                'name' => $id,
            ])
            ->findOne();

        if (!$uniqueId) {
            throw new Error("LeadCapture: UniqueId not found.");
        }

        $uniqueIdData = $uniqueId->getData();

        if (empty($uniqueIdData->data)) {
            throw new Error("LeadCapture: data not found.");
        }

        if (empty($uniqueIdData->leadCaptureId)) {
            throw new Error("LeadCapture: leadCaptureId not found.");
        }

        $data = $uniqueIdData->data;
        $leadCaptureId = $uniqueIdData->leadCaptureId;
        $leadId = $uniqueIdData->leadId ?? null;

        $terminateAt = $uniqueId->get('terminateAt');

        if (is_string($terminateAt) && time() > strtotime($terminateAt)) {
            throw new Error("LeadCapture: Opt-in cofrmation expired.");
        }

        $leadCapture = $this->entityManager->getEntity('LeadCapture', $leadCaptureId);

        if (!$leadCapture) {
            throw new Error("LeadCapture: LeadCapture not found.");
        }

        $optInConfirmationEmailTemplateId = $leadCapture->get('optInConfirmationEmailTemplateId');

        if (!$optInConfirmationEmailTemplateId) {
            throw new Error("LeadCapture: No optInConfirmationEmailTemplateId.");
        }

        $emailTemplate = $this->entityManager->getEntity('EmailTemplate', $optInConfirmationEmailTemplateId);

        if (!$emailTemplate) {
            throw new Error("LeadCapture: EmailTemplate not found.");
        }

        if ($leadId) {
            $lead = $this->entityManager->getEntity('Lead', $leadId);
        } else {
            $lead = $this->entityManager->getEntity('Lead');

            $lead->set($data);
        }

        $emailData = $this->emailTemplateService
            ->parseTemplate($emailTemplate, [
                'Person' => $lead,
                'Lead' => $lead,
            ]);

        if (!$lead) {
            throw new Error("Lead Capture: Could not find lead.");
        }

        $emailAddress = $lead->get('emailAddress');

        if (!$emailAddress) {
            throw new Error();
        }

        $subject = $emailData['subject'];
        $body = $emailData['body'];
        $isHtml = $emailData['isHtml'];

        if (mb_strpos($body, '{optInUrl}') === false && mb_strpos($body, '{optInLink}') === false) {
            if ($isHtml) {
                $body .= "<p>{optInLink}</p>";
            } else {
                $body .= "\n\n{optInUrl}";
            }
        }

        $url = $this->config->getSiteUrl() . '/?entryPoint=confirmOptIn&id=' . $uniqueId->get('name');

        $linkHtml =
            '<a href='.$url.'>' .
            $this->defaultLanguage->translate('Confirm Opt-In', 'labels', 'LeadCapture') .
            '</a>';

        $body = str_replace('{optInUrl}', $url, $body);
        $body = str_replace('{optInLink}', $linkHtml, $body);

        $createdAt = $uniqueId->get('createdAt');

        if ($createdAt) {
            $dateString = $this->dateTime->convertSystemDateTime($createdAt, null, $this->config->get('dateFormat'));
            $timeString = $this->dateTime->convertSystemDateTime($createdAt, null, $this->config->get('timeFormat'));
            $dateTimeString = $this->dateTime->convertSystemDateTime($createdAt);

            $body = str_replace('{optInDate}', $dateString, $body);
            $body = str_replace('{optInTime}', $timeString, $body);
            $body = str_replace('{optInDateTime}', $dateTimeString, $body);
        }

        $email = $this->entityManager->getEntity('Email');

        $email->set([
            'to' => $emailAddress,
            'subject' => $subject,
            'body' => $body,
            'isHtml' => $isHtml,
        ]);

        $smtpParams = null;

        $inboundEmailId = $leadCapture->get('inboundEmailId');

        if ($inboundEmailId) {
            $inboundEmail = $this->entityManager->getEntity('InboundEmail', $inboundEmailId);

            if (!$inboundEmail) {
                throw new Error("Lead Capture: Group Email Account {$inboundEmailId} is not available.");
            }

            if (
                $inboundEmail->get('status') !== 'Active' ||
                !$inboundEmail->get('useSmtp')
            ) {
                throw new Error("Lead Capture:  Group Email Account {$inboundEmailId} can't be used for Lead Capture.");
            }

            $smtpParams = $this->inboundEmailService->getSmtpParamsFromAccount($inboundEmail);

            if (!$smtpParams) {
                throw new Error("Lead Capture: Group Email Account {$inboundEmailId} has no SMTP params.");
            }
        }

        $sender = $this->emailSender->create();

        if ($smtpParams) {
            $sender->withSmtpParams($smtpParams);
        }

        $sender->send($email);
    }

    protected function getLeadWithPopulatedData(LeadCaptureEntity $leadCapture, stdClass $data): LeadEntity
    {
        $lead = $this->entityManager->getEntity('Lead');

        $fieldList = $leadCapture->get('fieldList');

        if (empty($fieldList)) {
            throw new Error('No field list specified.');
        }

        $isEmpty = true;

        foreach ($fieldList as $field) {
            if ($field === 'name') {
                if (property_exists($data, 'name') && $data->name) {
                    $value = trim($data->name);

                    $parts = explode(' ', $value);

                    $lastName = array_pop($parts);
                    $firstName = implode(' ', $parts);

                    $lead->set('firstName', $firstName);
                    $lead->set('lastName', $lastName);

                    $isEmpty = false;
                }

                continue;
            }

            $attributeList = $this->fieldUtil->getActualAttributeList('Lead', $field);

            if (empty($attributeList)) {
                continue;
            }

            foreach ($attributeList as $attribute) {
                if (!property_exists($data, $attribute)) {
                    continue;
                }

                $lead->set($attribute, $data->$attribute);

                if (!empty($data->$attribute)) {
                    $isEmpty = false;
                }
            }
        }

        if ($isEmpty) {
            throw new BadRequest('No appropriate data in payload.');
        }

        if ($leadCapture->get('leadSource')) {
            $lead->set('source', $leadCapture->get('leadSource'));
        }

        if ($leadCapture->get('campaignId')) {
            $lead->set('campaignId', $leadCapture->get('campaignId'));
        }

        return $lead;
    }

    protected function findLeadDuplicates(LeadCaptureEntity $leadCapture, LeadEntity $lead): array
    {
        $duplicate = null;
        $contact = null;

        if ($lead->get('emailAddress') || $lead->get('phoneNumber')) {
            $groupOr = [];

            if ($lead->get('emailAddress')) {
                $groupOr['emailAddress'] = $lead->get('emailAddress');
            }

            if ($lead->get('phoneNumber')) {
                $groupOr['phoneNumber'] = $lead->get('phoneNumber');
            }

            if ($lead->isNew() && $leadCapture->get('duplicateCheck')) {
                $duplicate = $this->entityManager
                    ->getRDBRepository('Lead')
                    ->where(['OR' => $groupOr])
                    ->findOne();
            }

            if ($leadCapture->isToSubscribeContactIfExists()) {
                $contact = $this->entityManager
                    ->getRDBRepository('Contact')
                    ->where(['OR' => $groupOr])
                    ->findOne();
            }
        }

        return [
            'contact' => $contact,
            'lead' => $duplicate,
        ];
    }

    protected function isTargetOptedIn(Entity $target, string $targetListId): bool
    {
        $isAlreadyOptedIn = $this->entityManager
            ->getRDBRepository($target->getEntityType())
            ->isRelated($target, 'targetLists', $targetListId);

        if (!$isAlreadyOptedIn) {
            return false;
        }

        $targetList = $this->entityManager->getEntity('TargetList', $targetListId);

        if (!$targetList) {
            return false;
        }

        $link = null;

        if ($target->getEntityType() === 'Contact') {
            $link = 'contacts';
        }

        if ($target->getEntityType() === 'Lead') {
            $link = 'leads';
        }

        if (!$link) {
            return false;
        }

        $targetFound = $this->entityManager
            ->getRDBRepository('TargetList')
            ->getRelation($targetList, $link)
            ->where([
                'id' => $target->getId(),
            ])
            ->findOne();

        if ($targetFound && $targetFound->get('targetListIsOptedOut')) {
            return false;
        }

        return true;
    }

    protected function log(LeadCaptureEntity $leadCapture, Entity $target, stdClass $data, bool $isNew = true)
    {
        $logRecord = $this->entityManager->getEntity('LeadCaptureLogRecord');

        $logRecord->set([
            'targetId' => $target->getId(),
            'targetType' => $target->getEntityType(),
            'leadCaptureId' => $leadCapture->getId(),
            'isCreated' => $isNew,
            'data' => $data,
        ]);

        if (!empty($data->description)) {
            $logRecord->set('description', $data->description);
        }

        $this->entityManager->saveEntity($logRecord);
    }
}
