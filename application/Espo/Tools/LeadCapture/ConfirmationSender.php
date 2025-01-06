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

namespace Espo\Tools\LeadCapture;

use Espo\Core\Exceptions\Error;
use Espo\Core\Mail\Account\GroupAccount\AccountFactory;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Mail\Exceptions\NoSmtp;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Templates\Entities\Person;
use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Core\Utils\DateTime;
use Espo\Core\Utils\Language;
use Espo\Entities\Email;
use Espo\Entities\EmailTemplate;
use Espo\Entities\LeadCapture as LeadCaptureEntity;
use Espo\Entities\UniqueId;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\EntityManager;
use Espo\Tools\EmailTemplate\Data as EmailTemplateData;
use Espo\Tools\EmailTemplate\Params as EmailTemplateParams;
use Espo\Tools\EmailTemplate\Processor as EmailTemplateProcessor;

class ConfirmationSender
{

    public function __construct(
        private EntityManager $entityManager,
        private Language $defaultLanguage,
        private EmailSender $emailSender,
        private AccountFactory $accountFactory,
        private DateTime $dateTime,
        private EmailTemplateProcessor $emailTemplateProcessor,
        private ApplicationConfig $appConfig,
    ) {}

    /**
     * Send opt-in confirmation email.
     *
     * @param string $id A unique ID.
     * @throws Error
     * @throws NoSmtp
     * @throws SendingError
     */
    public function send(string $id): void
    {
        /** @var ?UniqueId $uniqueId */
        $uniqueId = $this->entityManager
            ->getRDBRepositoryByClass(UniqueId::class)
            ->where(['name' => $id])
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

        if ($terminateAt && time() > strtotime($terminateAt->toString())) {
            throw new Error("LeadCapture: Opt-in confirmation expired.");
        }

        /** @var ?LeadCaptureEntity $leadCapture */
        $leadCapture = $this->entityManager->getEntityById(LeadCaptureEntity::ENTITY_TYPE, $leadCaptureId);

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
        } else {
            $lead = $this->entityManager->getNewEntity(Lead::ENTITY_TYPE);

            $lead->set($data);
        }

        if (!$lead) {
            throw new Error("Lead Capture: Could not find lead.");
        }

        $emailAddress = $lead->getEmailAddress();

        if (!$emailAddress) {
            throw new Error("Lead Capture: No lead email address.");
        }

        $emailData = $this->emailTemplateProcessor->process(
            $emailTemplate,
            EmailTemplateParams::create(),
            EmailTemplateData::create()
                ->withEntityHash([
                    Person::TEMPLATE_TYPE => $lead,
                    Lead::ENTITY_TYPE => $lead,
                ])
        );

        $subject = $emailData->getSubject();
        $body = $emailData->getBody();
        $isHtml = $emailData->isHtml();

        if (
            mb_strpos($body, '{optInUrl}') === false &&
            mb_strpos($body, '{optInLink}') === false
        ) {
            if ($isHtml) {
                $body .= "<p>{optInLink}</p>";
            } else {
                $body .= "\n\n{optInUrl}";
            }
        }

        $url = $this->appConfig->getSiteUrl() . '/?entryPoint=confirmOptIn&id=' . $uniqueId->getIdValue();

        $linkHtml =
            '<a href='.$url.'>' .
            $this->defaultLanguage->translateLabel('Confirm Opt-In', 'labels', LeadCaptureEntity::ENTITY_TYPE) .
            '</a>';

        $body = str_replace('{optInUrl}', $url, $body);
        $body = str_replace('{optInLink}', $linkHtml, $body);

        $createdAt = $uniqueId->getCreatedAt()->toString();

        if ($createdAt) {
            $dateString = $this->dateTime->convertSystemDateTime($createdAt, null, $this->appConfig->getDateFormat());
            $timeString = $this->dateTime->convertSystemDateTime($createdAt, null, $this->appConfig->getTimeFormat());
            $dateTimeString = $this->dateTime->convertSystemDateTime($createdAt);

            $body = str_replace('{optInDate}', $dateString, $body);
            $body = str_replace('{optInTime}', $timeString, $body);
            $body = str_replace('{optInDateTime}', $dateTimeString, $body);
        }

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $email
            ->setSubject($subject)
            ->setBody($body)
            ->setIsHtml($isHtml)
            ->addToAddress($emailAddress);

        $smtpParams = null;

        $inboundEmailId = $leadCapture->getInboundEmailId();

        if ($inboundEmailId) {
            $account = $this->accountFactory->create($inboundEmailId);

            if (!$account->isAvailableForSending()) {
                throw new Error("Lead Capture: Group email account {$inboundEmailId} can't be used for sending.");
            }

            $smtpParams = $account->getSmtpParams();
        }

        $sender = $this->emailSender->create();

        if ($smtpParams) {
            $sender->withSmtpParams($smtpParams);
        }

        $sender
            ->withAttachments($emailData->getAttachmentList())
            ->send($email);
    }
}
