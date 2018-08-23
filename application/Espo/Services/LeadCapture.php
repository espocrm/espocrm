<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

use \Espo\ORM\Entity;

use Espo\Core\Utils\Util;

use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Error;

class LeadCapture extends Record
{
    protected $readOnlyAttributeList = ['apiKey'];

    protected function init()
    {
        $this->addDependency('fieldManagerUtil');
        $this->addDependency('container');
        $this->addDependency('defaultLanguage');
    }

    protected function getMailSender()
    {
        return $this->getInjection('container')->get('mailSender');
    }

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        $entity->set('exampleRequestMethod', 'POST');

        $requestUrl = $this->getConfig()->getSiteUrl() . '/api/v1/LeadCapture/' . $entity->get('apiKey');
        $entity->set('exampleRequestUrl', $requestUrl);

        $fieldManagerUtil = $this->getInjection('fieldManagerUtil');

        $requestPayload = "```{\n";

        $attributeList = [];

        $attributeIgnoreList = ['emailAddressIsOptedOut'];

        $fieldList = $entity->get('fieldList');
        if (is_array($fieldList)) {
            foreach ($fieldList as $field) {
                foreach ($fieldManagerUtil->getActualAttributeList('Lead', $field) as $attribute) {
                    if (!in_array($attribute, $attributeIgnoreList)) {
                        $attributeList[] = $attribute;
                    }
                }
            }
        }

        foreach ($attributeList as $i => $attribute) {
            $requestPayload .= "    " . $attribute . ": " . strtoupper(Util::camelCaseToUnderscore($attribute));
            if ($i < count($attributeList) - 1) {
                $requestPayload .= ",";
            }

            $requestPayload .= "\n";
        }

        $requestPayload .= '}```';
        $entity->set('exampleRequestPayload', $requestPayload);
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        $apiKey = $this->generateApiKey();
        $entity->set('apiKey', $apiKey);
    }

    public function generateNewApiKeyForEntity($id)
    {
        $entity = $this->getEntity($id);
        if (!$entity) throw new NotFound();

        $apiKey = $this->generateApiKey();
        $entity->set('apiKey', $apiKey);

        $this->getEntityManager()->saveEntity($entity);

        $this->prepareEntityForOutput($entity);

        return $entity;
    }

    public function generateApiKey()
    {
        return bin2hex(random_bytes(16));
    }

    public function leadCapture($apiKey, $data)
    {
        $leadCapture = $this->getEntityManager()->getRepository('LeadCapture')->where([
            'apiKey' => $apiKey
        ])->findOne();

        if (!$leadCapture) throw new NotFound('Api key is not valid.');

        if ($leadCapture->get('optInConfirmation')) {
            if (empty($data->emailAddress)) {
                throw new Error('LeadCapture: No emailAddress passed in the payload.');
            }
            if (!$leadCapture->get('optInConfirmationEmailTemplateId')) {
                throw new Error('LeadCapture: No optInConfirmationEmailTemplate specified.');
            }
            $lead = $this->getLeadWithPopulatedData($leadCapture, $data);

            $lifetime = $leadCapture->get('optInConfirmationLifetime');
            if (!$lifetime) $lifetime = 100;

            $dt = new \DateTime();
            $dt->modify('+' . $lifetime . ' hours');
            $terminateAt = $dt->format('Y-m-d H:i:s');

            $uniqueId = $this->getEntityManager()->getEntity('UniqueId');
            $uniqueId->set([
                'terminateAt' => $terminateAt,
                'data' => (object) [
                    'leadCaptureId' => $leadCapture->id,
                    'data' => $data
                ]
            ]);
            $this->getEntityManager()->saveEntity($uniqueId);

            $job = $this->getEntityManager()->getEntity('Job');
            $job->set([
                'serviceName' => 'LeadCapture',
                'methodName' => 'jobOptInConfirmation',
                'data' => (object) [
                    'id' => $uniqueId->get('name')
                ]
            ]);
            $this->getEntityManager()->saveEntity($job);

            return true;
        }

        return $this->leadCaptureProceed($leadCapture, $data);
    }

    protected function getLeadWithPopulatedData(Entity $leadCapture, $data)
    {
        $lead = $this->getEntityManager()->getEntity('Lead');

        $fieldList = $leadCapture->get('fieldList');
        if (empty($fieldList)) throw new Error('No field list specified.');

        $isEmpty = true;
        foreach ($fieldList as $field) {
            $attributeList = $this->getInjection('fieldManagerUtil')->getActualAttributeList('Lead', $field);
            if (empty($attributeList)) continue;
            foreach ($attributeList as $attribute) {
                if (property_exists($data, $attribute)) {
                    $lead->set($attribute, $data->$attribute);
                    if (!empty($data->$attribute)) {
                        $isEmpty = false;
                    }
                }
            }
        }

        if ($isEmpty) throw new BadRequest('No appropriate data in payload.');

        if ($leadCapture->get('leadSource')) {
            $lead->set('source', $leadCapture->get('leadSource'));
        }

        if ($leadCapture->get('campaignId')) {
            $lead->set('campaignId', $leadCapture->get('campaignId'));
        }

        return $lead;
    }

    public function leadCaptureProceed(Entity $leadCapture, $data)
    {
        $lead = $this->getLeadWithPopulatedData($leadCapture, $data);

        $campaingService = $this->getServiceFactory()->create('Campaign');

        if ($leadCapture->get('campaignId')) {
            $campaign = $this->getEntityManager()->getEntity('Campaign', $leadCapture->get('campaignId'));
        }

        $duplicate = null;
        $contact = null;
        $toRelateLead = false;

        $target = $lead;

        if ($lead->get('emailAddress') || $lead->get('phoneNumber')) {
            $groupOr = [];
            if ($lead->get('emailAddress')) {
                $groupOr['emailAddress'] = $lead->get('emailAddress');
            }
            if ($lead->get('phoneNumber')) {
                $groupOr['phoneNumber'] = $lead->get('phoneNumber');
            }

            $duplicate = $this->getEntityManager()->getRepository('Lead')->where(['OR' => $groupOr])->findOne();
            $contact = $this->getEntityManager()->getRepository('Contact')->where(['OR' => $groupOr])->findOne();
            if ($contact) {
                $target = $contact;
            }
        }

        if ($duplicate) {
            $lead = $duplicate;
            if (!$contact) {
                $target = $lead;
            }
        }

        if ($leadCapture->get('subscribeToTargetList') && $leadCapture->get('targetListId')) {
            if ($contact) {
                if ($leadCapture->get('subscribeContactToTargetList')) {
                    $isAlreadyOptedIn = $this->getEntityManager()->getRepository('Contact')->isRelated($contact, 'targetLists', $leadCapture->get('targetListId'));
                    if ($campaign && !$isAlreadyOptedIn) {
                        $this->getEntityManager()->getRepository('Contact')->relate($contact, 'targetLists', $leadCapture->get('targetListId'));
                        $campaingService->logOptedIn($campaign->id, null, $contact);
                    }
                }
            } else {
                $isAlreadyOptedIn = $this->getEntityManager()->getRepository('Lead')->isRelated($lead, 'targetLists', $leadCapture->get('targetListId'));
                if (!$isAlreadyOptedIn) {
                    $toRelateLead = true;
                }
            }
        }

        $isNew = !$duplicate && !$contact;

        if (!$contact) {
            if ($leadCapture->get('targetTeamId')) {
                $lead->addLinkMultipleId('teams', $leadCapture->get('targetTeamId'));
            }

            $this->getEntityManager()->saveEntity($lead);

            if (!$duplicate) {
                if ($campaign) {
                    $campaingService->logLeadCreated($campaign->id, $lead);
                }
            }

            if ($toRelateLead) {
                $this->getEntityManager()->getRepository('Lead')->relate($lead, 'targetLists', $leadCapture->get('targetListId'));
                if ($campaign) {
                    $campaingService->logOptedIn($campaign->id, null, $lead);
                }
            }
        }

        $logRecord = $this->getEntityManager()->getEntity('LeadCaptureLogRecord');
        $logRecord->set([
            'targetId' => $target->id,
            'targetType' => $target->getEntityType(),
            'leadCaptureId' => $leadCapture->id,
            'isCreated' => $isNew,
            'data' => $data
        ]);

        if (!empty($data->description)) {
            $logRecord->set('description', $description);
        }

        $this->getEntityManager()->saveEntity($logRecord);

        return true;
    }

    public function jobOptInConfirmation($jobData)
    {
        if (empty($jobData->id)) throw new Error();

        $uniqueId = $this->getEntityManager()->getRepository('UniqueId')->where([
            'name' => $jobData->id
        ])->findOne();

        if (!$uniqueId) throw new Error("LeadCapture: UniqueId not found.");

        $uniqueIdData = $uniqueId->get('data');
        if (empty($uniqueIdData->data)) throw new Error("LeadCapture: data not found.");
        if (empty($uniqueIdData->leadCaptureId)) throw new Error("LeadCapture: leadCaptureId not found.");
        $data = $uniqueIdData->data;
        $leadCaptureId = $uniqueIdData->leadCaptureId;

        $terminateAt = $uniqueId->get('terminateAt');
        if (time() > strtotime($terminateAt)) {
            throw new Error("LeadCapture: Opt-in cofrmation expired.");
        }

        $leadCapture = $this->getEntityManager()->getEntity('LeadCapture', $leadCaptureId);
        if (!$leadCapture) throw new Error("LeadCapture: LeadCapture not found.");

        $optInConfirmationEmailTemplateId = $leadCapture->get('optInConfirmationEmailTemplateId');
        if (!$optInConfirmationEmailTemplateId) throw new Error("LeadCapture: No optInConfirmationEmailTemplateId.");

        $emailTemplate = $this->getEntityManager()->getEntity('EmailTemplate', $optInConfirmationEmailTemplateId);
        if (!$emailTemplate) throw new Error("LeadCapture: EmailTemplate not found.");

        $lead = $this->getEntityManager()->getEntity('Lead');
        $lead->set($data);

        $emailData = $this->getServiceFactory()->create('EmailTemplate')->parseTemplate($emailTemplate, [
            'Person' => $lead,
            'Lead' => $lead
        ]);

        $emailAddress = $lead->get('emailAddress');
        if (!$emailAddress) throw new Error();

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

        $url = $this->getConfig()->getSiteUrl() . '/?entryPoint=confirmOptIn&id=' . $uniqueId->get('name');
        $linkHtml = '<a href='.$url.'>'.$this->getInjection('defaultLanguage')->translate('Confirm Opt-In', 'labels', 'LeadCapture').'</a>';

        $body = str_replace('{optInUrl}', $url, $body);
        $body = str_replace('{optInLink}', $linkHtml, $body);

        $email = $this->getEntityManager()->getEntity('Email');
        $email->set([
            'to' => $emailAddress,
            'subject' => $subject,
            'body' => $body,
            'isHtml' => $isHtml
        ]);

        $this->getMailSender()->send($email);

        return true;
    }

    public function confirmOptIn($id)
    {
        $uniqueId = $this->getEntityManager()->getRepository('UniqueId')->where([
            'name' => $id
        ])->findOne();

        if (!$uniqueId) throw new NotFound("LeadCapture Confirm: UniqueId not found.");

        $uniqueIdData = $uniqueId->get('data');
        if (empty($uniqueIdData->data)) throw new Error("LeadCapture Confirm: data not found.");
        if (empty($uniqueIdData->leadCaptureId)) throw new Error("LeadCapture Confirm: leadCaptureId not found.");
        $data = $uniqueIdData->data;
        $leadCaptureId = $uniqueIdData->leadCaptureId;

        $terminateAt = $uniqueId->get('terminateAt');
        if (time() > strtotime($terminateAt)) {
            return (object) [
                'status' => 'expired',
                'message' => $this->getInjection('defaultLanguage')->translate('optInConfirmationExpired', 'messages', 'LeadCapture')
            ];
        }

        $leadCapture = $this->getEntityManager()->getEntity('LeadCapture', $leadCaptureId);
        if (!$leadCapture) throw new Error("LeadCapture Confirm: LeadCapture not found.");

        if (empty($uniqueIdData->isProcessed)) {
            $this->leadCaptureProceed($leadCapture, $data);
            $uniqueIdData->isProcessed = true;
            $uniqueId->set('data', $uniqueIdData);
            $this->getEntityManager()->saveEntity($uniqueId);
        }

        return (object) [
            'status' => 'success',
            'message' => $leadCapture->get('optInConfirmationSuccessMessage'),
            'leadCaptureName' => $leadCapture->get('name'),
            'leadCaptureId' => $leadCapture->id
        ];
    }
}
