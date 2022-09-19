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
    FieldValidation\FieldValidationManager,
    FieldValidation\FieldValidationParams,
    ORM\EntityManager,
    Utils\FieldUtil,
    Utils\Language,
    HookManager,
    Mail\EmailSender,
    Utils\Config,
    Utils\DateTime as DateTimeUtil,
    Utils\Log,
    Job\QueueName};

use Espo\{
    Entities\Email,
    Entities\EmailTemplate,
    Entities\InboundEmail,
    Entities\Job,
    Entities\LeadCaptureLogRecord,
    Modules\Crm\Entities\Campaign,
    Modules\Crm\Entities\TargetList,
    ORM\Entity,
    Entities\LeadCapture as LeadCaptureEntity,
};

use stdClass;
use DateTime;

class LeadCapture
{
    protected EntityManager $entityManager;
    protected FieldUtil $fieldUtil;
    protected Language $defaultLanguage;
    protected HookManager $hookManager;
    protected EmailSender $emailSender;
    protected Config $config;
    protected DateTimeUtil $dateTime;
    protected Log $log;

    private CampaignService $campaignService;
    private EmailTemplateService $emailTemplateService;
    private InboundEmailService $inboundEmailService;
    private FieldValidationManager $fieldValidationManager;

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
        InboundEmailService $inboundEmailService,
        FieldValidationManager $fieldValidationManager
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
        $this->fieldValidationManager = $fieldValidationManager;
    }

    /**
     * Capture a lead. A main entry method.
     *
     * @param string $apiKey An API key.
     * @param stdClass $data A payload.
     * @throws BadRequest
     * @throws Error
     * @throws NotFound
     */
    public function capture(string $apiKey, stdClass $data): void
    {
        /** @var ?LeadCaptureEntity $leadCapture */
        $leadCapture = $this->entityManager
            ->getRDBRepository(LeadCaptureEntity::ENTITY_TYPE)
            ->where([
                'apiKey' => $apiKey,
                'isActive' => true,
            ])
            ->findOne();

        if (!$leadCapture) {
            throw new NotFound('Api key is not valid.');
        }

        if (!$leadCapture->optInConfirmation()) {
            $this->proceed($leadCapture, $data);

            return;
        }

        if (empty($data->emailAddress)) {
            throw new Error('LeadCapture: No emailAddress passed in the payload.');
        }

        if (!$leadCapture->getOptInConfirmationEmailTemplateId()) {
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

            $targetListId = $leadCapture->getTargetListId();

            if ($leadCapture->skipOptInConfirmationIfSubscribed() && $targetListId) {
                $isAlreadyOptedIn = $this->isTargetOptedIn($target, $targetListId);

                if ($isAlreadyOptedIn) {
                    $this->log->debug("LeadCapture: Already opted in. Skipped.");

                    return;
                }
            }
        }

        if ($leadCapture->createLeadBeforeOptInConfirmation() && !$hasDuplicate) {
            $this->entityManager->saveEntity($lead);

            $this->log($leadCapture, $target, $data, true);

            $isLogged = true;
        }

        $lifetime = $leadCapture->getOptInConfirmationLifetime();

        if (!$lifetime) {
            $lifetime = 100;
        }

        $dt = new DateTime();

        $dt->modify('+' . $lifetime . ' hours');

        $terminateAt = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        /** @var UniqueId $uniqueId */
        $uniqueId = $this->entityManager->getNewEntity(UniqueId::ENTITY_TYPE);

        $uniqueId->set([
            'terminateAt' => $terminateAt,
            'data' => (object) [
                'leadCaptureId' => $leadCapture->getId(),
                'data' => $data,
                'leadId' => $lead->hasId() ? $lead->getId() : null,
                'isLogged' => $isLogged,
            ],
        ]);

        $this->entityManager->saveEntity($uniqueId);

        $this->entityManager->createEntity(Job::ENTITY_TYPE, [
            'serviceName' => 'LeadCapture',
            'methodName' => 'jobOptInConfirmation',
            'data' => (object) [
                'id' => $uniqueId->getIdValue(),
            ],
            'queue' => QueueName::E0,
        ]);
    }

    /**
     * @throws BadRequest
     * @throws NotFound
     * @throws Error
     */
    protected function proceed(
        LeadCaptureEntity $leadCapture,
        stdClass $data,
        ?string $leadId = null,
        bool $isLogged = false
    ): void {

        if ($leadId) {
            /** @var ?Lead $lead */
            $lead = $this->entityManager->getEntityById(Lead::ENTITY_TYPE, $leadId);

            if (!$lead) {
                throw new NotFound("Lead '{$leadId}' not found.");
            }
        }
        else {
            $lead = $this->getLeadWithPopulatedData($leadCapture, $data);
        }

        $campaign = null;

        /** @var ?string $campaignId */
        $campaignId = $leadCapture->getCampaignId();

        if ($campaignId) {
            $campaign = $this->entityManager->getEntityById(Campaign::ENTITY_TYPE, $campaignId);
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

        $isContactOptedIn = false;

        $targetListId = $leadCapture->getTargetListId();

        if ($leadCapture->subscribeToTargetList() && $targetListId) {
            $isAlreadyOptedIn = false;

            if ($contact && $leadCapture->subscribeContactToTargetList()) {
                $isAlreadyOptedIn = $this->isTargetOptedIn($contact, $targetListId);

                $isContactOptedIn = $isAlreadyOptedIn;

                if (!$isAlreadyOptedIn) {
                    $this->entityManager
                        ->getRDBRepository(Contact::ENTITY_TYPE)
                        ->getRelation($contact, 'targetLists')
                        ->relateById($targetListId, ['optedOut' => false]);

                    $isAlreadyOptedIn = true;

                    if ($campaign) {
                        $this->campaignService->logOptedIn($campaign->getId(), null, $contact);
                    }

                    $targetList = $this->entityManager->getEntityById(TargetList::ENTITY_TYPE, $targetListId);

                    if ($targetList) {
                        $this->hookManager->process(TargetList::ENTITY_TYPE, 'afterOptIn', $targetList, [], [
                           'link' => 'contacts',
                           'targetId' => $contact->getId(),
                           'targetType' => Contact::ENTITY_TYPE,
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
                    $isAlreadyOptedIn = $this->isTargetOptedIn($targetLead, $targetListId);

                    if (!$isAlreadyOptedIn) {
                        $toRelateLead = true;
                    }
                }
            }
        }

        if (
            $contact &&
            (!$isContactOptedIn || !$leadCapture->subscribeToTargetList()) &&
            $leadCapture->subscribeContactToTargetList()
        ) {
            $this->hookManager->process(LeadCaptureEntity::ENTITY_TYPE, 'afterLeadCapture', $leadCapture, [], [
               'targetId' => $contact->getId(),
               'targetType' => Contact::ENTITY_TYPE,
            ]);

            $this->hookManager->process(Contact::ENTITY_TYPE, 'afterLeadCapture', $contact, [], [
               'leadCaptureId' => $leadCapture->getId(),
            ]);
        }

        $isNew = !$duplicate && !$contact;

        if (!$contact || !$leadCapture->subscribeContactToTargetList()) {
            $targetTeamId = $leadCapture->getTargetTeamId();

            if ($targetTeamId) {
                $lead->addLinkMultipleId('teams', $targetTeamId);
            }

            $this->entityManager->saveEntity($lead);

            if (!$duplicate && $campaign) {
                $this->campaignService->logLeadCreated($campaign->getId(), $lead);
            }
        }

        if ($toRelateLead && $targetLead->hasId() && $targetListId) {
            $this->entityManager
                ->getRDBRepository(Lead::ENTITY_TYPE)
                ->getRelation($targetLead, 'targetLists')
                ->relateById($targetListId, ['optedOut' => false]);

            if ($campaign) {
                $this->campaignService->logOptedIn($campaign->getId(), null, $targetLead);
            }

            $targetList = $this->entityManager->getEntityById(TargetList::ENTITY_TYPE, $targetListId);

            if ($targetList) {
                $this->hookManager->process(TargetList::ENTITY_TYPE, 'afterOptIn', $targetList, [], [
                   'link' => 'leads',
                   'targetId' => $targetLead->getId(),
                   'targetType' => Lead::ENTITY_TYPE,
                   'leadCaptureId' => $leadCapture->getId(),
                ]);
            }
        }

        if ($toRelateLead  || !$leadCapture->subscribeToTargetList()) {
            $this->hookManager->process(
                LeadCaptureEntity::ENTITY_TYPE,
                'afterLeadCapture',
                $leadCapture,
                [],
                [
                   'targetId' => $targetLead->getId(),
                   'targetType' => Lead::ENTITY_TYPE,
                ]
            );

            $this->hookManager->process(
                Lead::ENTITY_TYPE,
                'afterLeadCapture',
                $targetLead,
                [],
                [
                   'leadCaptureId' => $leadCapture->getId(),
                ]
            );
        }

        if (!$isLogged) {
            $this->log($leadCapture, $target, $data, $isNew);
        }
    }

    /**
     * Confirm opt-in.
     *
     * @throws BadRequest
     * @throws Error
     * @throws NotFound
     *
     * @param string $id A unique ID.
     * @return array{
     *   status: 'success'|'expired',
     *   message: ?string,
     *   leadCaptureName?: ?string,
     *   leadCaptureId?: string,
     * }
     */
    public function confirmOptIn(string $id): array
    {
        /** @var ?UniqueId $uniqueId */
        $uniqueId = $this->entityManager
            ->getRDBRepository(UniqueId::ENTITY_TYPE)
            ->where(['name' => $id])
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

        $terminateAt = $uniqueId->getTerminateAt();

        if ($terminateAt && time() > strtotime($terminateAt->getString())) {
            return [
                'status' => 'expired',
                'message' => $this->defaultLanguage
                    ->translateLabel('optInConfirmationExpired', 'messages', 'LeadCapture'),
            ];
        }

        /** @var ?LeadCaptureEntity $leadCapture */
        $leadCapture = $this->entityManager->getEntityById(LeadCaptureEntity::ENTITY_TYPE, $leadCaptureId);

        if (!$leadCapture) {
            throw new Error("LeadCapture Confirm: LeadCapture not found.");
        }

        if (empty($uniqueIdData->isProcessed)) {
            $this->proceed($leadCapture, $data, $leadId, $isLogged);

            $uniqueIdData->isProcessed = true;

            $uniqueId->set('data', $uniqueIdData);

            $this->entityManager->saveEntity($uniqueId);
        }

        return [
            'status' => 'success',
            'message' => $leadCapture->getOptInConfirmationSuccessMessage(),
            'leadCaptureName' => $leadCapture->getName(),
            'leadCaptureId' => $leadCapture->getId(),
        ];
    }

    /**
     * Send opt-in confirmation email.
     *
     * @param string $id A unique ID.
     * @throws Error
     */
    public function sendOptInConfirmation(string $id): void
    {
        /** @var ?UniqueId $uniqueId */
        $uniqueId = $this->entityManager
            ->getRDBRepository(UniqueId::ENTITY_TYPE)
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

        $terminateAt = $uniqueId->getTerminateAt();

        if ($terminateAt && time() > strtotime($terminateAt->getString())) {
            throw new Error("LeadCapture: Opt-in confirmation expired.");
        }

        /** @var ?LeadCaptureEntity $leadCapture */
        $leadCapture = $this->entityManager->getEntity(LeadCaptureEntity::ENTITY_TYPE, $leadCaptureId);

        if (!$leadCapture) {
            throw new Error("LeadCapture: LeadCapture not found.");
        }

        $optInConfirmationEmailTemplateId = $leadCapture->getOptInConfirmationEmailTemplateId();

        if (!$optInConfirmationEmailTemplateId) {
            throw new Error("LeadCapture: No optInConfirmationEmailTemplateId.");
        }

        /** @var ?EmailTemplate $emailTemplate */
        $emailTemplate = $this->entityManager
            ->getEntityById(EmailTemplate::ENTITY_TYPE, $optInConfirmationEmailTemplateId);

        if (!$emailTemplate) {
            throw new Error("LeadCapture: EmailTemplate not found.");
        }

        if ($leadId) {
            /** @var ?Lead $lead */
            $lead = $this->entityManager->getEntityById(Lead::ENTITY_TYPE, $leadId);
        }
        else {
            $lead = $this->entityManager->getNewEntity(Lead::ENTITY_TYPE);

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

        $emailAddress = $lead->getEmailAddress();

        if (!$emailAddress) {
            throw new Error("Lead Capture: No lead email address.");
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

        $url = $this->config->getSiteUrl() . '/?entryPoint=confirmOptIn&id=' . $uniqueId->getIdValue();

        $linkHtml =
            '<a href='.$url.'>' .
            $this->defaultLanguage->translateLabel('Confirm Opt-In', 'labels', 'LeadCapture') .
            '</a>';

        $body = str_replace('{optInUrl}', $url, $body);
        $body = str_replace('{optInLink}', $linkHtml, $body);

        $createdAt = $uniqueId->getCreatedAt()->getString();

        if ($createdAt) {
            $dateString = $this->dateTime->convertSystemDateTime($createdAt, null, $this->config->get('dateFormat'));
            $timeString = $this->dateTime->convertSystemDateTime($createdAt, null, $this->config->get('timeFormat'));
            $dateTimeString = $this->dateTime->convertSystemDateTime($createdAt);

            $body = str_replace('{optInDate}', $dateString, $body);
            $body = str_replace('{optInTime}', $timeString, $body);
            $body = str_replace('{optInDateTime}', $dateTimeString, $body);
        }

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $email->set([
            'to' => $emailAddress,
            'subject' => $subject,
            'body' => $body,
            'isHtml' => $isHtml,
        ]);

        $smtpParams = null;

        $inboundEmailId = $leadCapture->getInboundEmailId();

        if ($inboundEmailId) {
            /** @var ?InboundEmail $inboundEmail */
            $inboundEmail = $this->entityManager->getEntityById(InboundEmail::ENTITY_TYPE, $inboundEmailId);

            if (!$inboundEmail) {
                throw new Error("Lead Capture: Group Email Account {$inboundEmailId} is not available.");
            }

            if (!$inboundEmail->isAvailableForSending()) {
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

    /**
     * @throws BadRequest
     * @throws Error
     */
    protected function getLeadWithPopulatedData(LeadCaptureEntity $leadCapture, stdClass $data): Lead
    {
        /** @var Lead $lead */
        $lead = $this->entityManager->getNewEntity(Lead::ENTITY_TYPE);

        $fieldList = $leadCapture->getFieldList();

        if ($fieldList === []) {
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

            $attributeList = $this->fieldUtil->getActualAttributeList(Lead::ENTITY_TYPE, $field);

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
            throw new BadRequest('noRequiredFields');
        }

        if ($leadCapture->getLeadSource()) {
            $lead->set('source', $leadCapture->getLeadSource());
        }

        if ($leadCapture->getCampaignId()) {
            $lead->set('campaignId', $leadCapture->getCampaignId());
        }

        $teamId = $leadCapture->getTargetTeamId();

        if ($teamId) {
            $lead->addLinkMultipleId('teams', $teamId);
        }

        // Skipping the 'required' validation.
        $validationParams = FieldValidationParams::create()->withTypeSkipFieldList('required', $fieldList);

        $this->fieldValidationManager->process($lead, $data, $validationParams);

        return $lead;
    }

    /**
     * @return array{
     *   contact: ?Contact,
     *   lead: ?Lead,
     * }
     */
    protected function findLeadDuplicates(LeadCaptureEntity $leadCapture, Lead $lead): array
    {
        $duplicate = null;
        $contact = null;

        $emailAddress = $lead->getEmailAddress();
        $phoneNumber = $lead->getPhoneNumber();

        if ($emailAddress || $phoneNumber) {
            $groupOr = [];

            if ($emailAddress) {
                $groupOr['emailAddress'] = $emailAddress;
            }

            if ($phoneNumber) {
                $groupOr['phoneNumber'] = $phoneNumber;
            }

            if ($lead->isNew() && $leadCapture->duplicateCheck()) {
                $duplicate = $this->entityManager
                    ->getRDBRepository(Lead::ENTITY_TYPE)
                    ->where(['OR' => $groupOr])
                    ->findOne();
            }

            if ($leadCapture->subscribeToTargetList() && $leadCapture->subscribeContactToTargetList()) {
                $contact = $this->entityManager
                    ->getRDBRepository(Contact::ENTITY_TYPE)
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
        $targetList = $this->entityManager->getEntityById(TargetList::ENTITY_TYPE, $targetListId);

        if (!$targetList) {
            return false;
        }

        $isAlreadyOptedIn = $this->entityManager
            ->getRDBRepository($target->getEntityType())
            ->getRelation($target, 'targetLists')
            ->isRelated($targetList);

        if (!$isAlreadyOptedIn) {
            return false;
        }

        $link = null;

        if ($target->getEntityType() === Contact::ENTITY_TYPE) {
            $link = 'contacts';
        }

        if ($target->getEntityType() === Lead::ENTITY_TYPE) {
            $link = 'leads';
        }

        if (!$link) {
            return false;
        }

        $targetFound = $this->entityManager
            ->getRDBRepository(TargetList::ENTITY_TYPE)
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

    protected function log(LeadCaptureEntity $leadCapture, Entity $target, stdClass $data, bool $isNew = true): void
    {
        $logRecord = $this->entityManager->getNewEntity(LeadCaptureLogRecord::ENTITY_TYPE);

        $logRecord->set([
            'targetId' => $target->hasId() ? $target->getId() : null,
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
