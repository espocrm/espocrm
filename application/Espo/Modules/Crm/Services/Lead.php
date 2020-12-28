<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Modules\Crm\Services;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ConflictSilent;

use Espo\ORM\Entity;

use Espo\Modules\Crm\Entities\Lead as LeadEntity;

use Espo\Core\Templates\Services\Person as PersonService;

use Espo\Core\Di;

class Lead extends PersonService implements

    Di\FieldUtilAware
{
    use Di\FieldUtilSetter;

    protected $linkMandatorySelectAttributeList = [
        'targetLists' => ['isOptedOut'],
    ];

    protected function afterCreateEntity(Entity $entity, $data)
    {
        if (!empty($data->emailId)) {
            $email = $this->getEntityManager()->getEntity('Email', $data->emailId);

            if ($email && !$email->get('parentId') && $this->getAcl()->check($email)) {
                $email->set([
                    'parentType' => 'Lead',
                    'parentId' => $entity->id
                ]);

                $this->getEntityManager()->saveEntity($email);
            }
        }
        if ($entity->get('campaignId')) {
            $campaign = $this->getEntityManager()->getEntity('Campaign', $entity->get('campaignId'));

            if ($campaign) {
                $log = $this->getEntityManager()->getEntity('CampaignLogRecord');

                $log->set([
                    'action' => 'Lead Created',
                    'actionDate' => date('Y-m-d H:i:s'),
                    'parentType' => 'Lead',
                    'parentId' => $entity->id,
                    'campaignId' => $campaign->id,
                ]);

                $this->getEntityManager()->saveEntity($log);
            }
        }
    }

    public function getConvertAttributes(string $id)
    {
        $lead = $this->getEntity($id);

        if (!$this->getAcl()->check($lead, 'read')) {
            throw new Forbidden();
        }

        $data = [];

        $entityList = $this->getMetadata()->get('entityDefs.Lead.convertEntityList', []);

        $ignoreAttributeList = ['createdAt', 'modifiedAt', 'modifiedById', 'modifiedByName', 'createdById', 'createdByName'];

        $convertFieldsDefs = $this->getMetadata()->get('entityDefs.Lead.convertFields', []);

        foreach ($entityList as $entityType) {
            if (!$this->getAcl()->checkScope($entityType, 'edit')) {
                continue;
            }

            $attributes = [];

            $target = $this->getEntityManager()->getEntity($entityType);

            $fieldMap = [];

            $fieldList = array_keys($this->getMetadata()->get('entityDefs.Lead.fields', []));

            foreach ($fieldList as $field) {
                if (!$this->getMetadata()->get('entityDefs.'.$entityType.'.fields.' . $field)) {
                    continue;
                }

                if (
                    $this->getMetadata()->get(['entityDefs', $entityType, 'fields', $field, 'type'])
                    !==
                    $this->getMetadata()->get(['entityDefs', 'Lead', 'fields', $field, 'type'])
                ) {
                    continue;
                }

                $fieldMap[$field] = $field;
            }
            if (array_key_exists($entityType, $convertFieldsDefs)) {
                foreach ($convertFieldsDefs[$entityType] as $field => $leadField) {
                    $fieldMap[$field] = $leadField;
                }
            }

            foreach ($fieldMap as $field => $leadField) {
                $type = $this->getMetadata()->get(['entityDefs', 'Lead', 'fields', $field, 'type']);

                if (in_array($type, ['file', 'image'])) {
                    $attachment = $lead->get($field);

                    if ($attachment) {
                        $attachment = $this->getEntityManager()
                            ->getRepository('Attachment')
                            ->getCopiedAttachment($attachment);

                        $idAttribute = $field . 'Id';
                        $nameAttribute = $field . 'Name';

                        if ($attachment) {
                            $attributes[$idAttribute] = $attachment->id;
                            $attributes[$nameAttribute] = $attachment->get('name');
                        }
                    }

                    continue;
                }
                else if (in_array($type, ['attachmentMultiple'])) {
                    $attachmentList = $lead->get($field);

                    if (count($attachmentList)) {
                        $idList = [];
                        $nameHash = (object) [];
                        $typeHash = (object) [];

                        foreach ($attachmentList as $attachment) {
                            $attachment = $this->getEntityManager()->getRepository('Attachment')->getCopiedAttachment($attachment);

                            if ($attachment) {
                                $idList[] = $attachment->id;

                                $nameHash->{$attachment->id} = $attachment->get('name');
                                $typeHash->{$attachment->id} = $attachment->get('type');
                            }
                        }

                        $attributes[$field . 'Ids'] = $idList;
                        $attributes[$field . 'Names'] = $nameHash;
                        $attributes[$field . 'Types'] = $typeHash;
                    }

                    continue;
                }

                $leadAttributeList = $this->fieldUtil->getAttributeList('Lead', $leadField);

                $attributeList = $this->fieldUtil->getAttributeList($entityType, $field);

                foreach ($attributeList as $i => $attribute) {
                    if (in_array($attribute, $ignoreAttributeList)) {
                        continue;
                    }

                    $leadAttribute = $leadAttributeList[$i] ?? null;

                    if (!$leadAttribute) {
                        throw new Error("Not compatible fields in 'convertFields' map.");
                    }

                    if (!$lead->has($leadAttribute)) {
                        continue;
                    }

                    $attributes[$attribute] = $lead->get($leadAttribute);
                }
            }

            $data[$entityType] = $attributes;
        }

        return $data;
    }

    public function convert(string $id, object $recordsData, ?object $additionalData = null) : LeadEntity
    {
        $lead = $this->getEntity($id);

        $additionalData = $additionalData ?? (object) [];

        if (!$this->getAcl()->check($lead, 'edit')) {
            throw new Forbidden();
        }

        $duplicateList = [];
        $duplicateCheck = !($additionalData->skipDuplicateCheck ?? false);

        $entityManager = $this->getEntityManager();

        $skipSave = false;

        if (!empty($recordsData->Account)) {
            $account = $entityManager->getEntity('Account');
            $account->set(get_object_vars($recordsData->Account));

            if ($duplicateCheck) {
                $rDuplicateList = $this->getServiceFactory()
                    ->create('Account')
                    ->findDuplicates($account, $recordsData->Account);

                if ($rDuplicateList) {
                    foreach ($rDuplicateList as $e) {
                        $item = $e->getValueMap();
                        $item->_entityType = $e->getEntityType();
                        $duplicateList[] = $item;
                        $skipSave = true;
                    }
                }
            }

            if (!$skipSave) {
                $entityManager->saveEntity($account);

                $lead->set('createdAccountId', $account->id);
            }
        }

        if (!empty($recordsData->Contact)) {
            $contact = $entityManager->getEntity('Contact');
            $contact->set(get_object_vars($recordsData->Contact));

            if (isset($account)) {
                $contact->set('accountId', $account->id);
            }

            if ($duplicateCheck) {
                $rDuplicateList = $this->getServiceFactory()
                    ->create('Contact')
                    ->findDuplicates($contact, $recordsData->Contact);

                if ($rDuplicateList) {
                    foreach ($rDuplicateList as $e) {
                        $item = $e->getValueMap();
                        $item->_entityType = $e->getEntityType();
                        $duplicateList[] = $item;
                        $skipSave = true;
                    }
                }
            }

            if (!$skipSave) {
                $entityManager->saveEntity($contact);

                $lead->set('createdContactId', $contact->id);
            }
        }

        if (!empty($recordsData->Opportunity)) {
            $opportunity = $entityManager->getEntity('Opportunity');
            $opportunity->set(get_object_vars($recordsData->Opportunity));

            if (isset($account)) {
                $opportunity->set('accountId', $account->id);
            }

            if (isset($contact)) {
                $opportunity->set('contactId', $contact->id);
            }

            if ($duplicateCheck) {
                $rDuplicateList = $this->getServiceFactory()
                    ->create('Opportunity')
                    ->findDuplicates($opportunity, $recordsData->Opportunity);

                if ($rDuplicateList) {
                    foreach ($rDuplicateList as $e) {
                        $item = $e->getValueMap();
                        $item->_entityType = $e->getEntityType();
                        $duplicateList[] = $item;
                        $skipSave = true;
                    }
                }
            }

            if (!$skipSave) {
                $entityManager->saveEntity($opportunity);

                if (isset($contact)) {
                    $entityManager->getRepository('Contact')->relate($contact, 'opportunities', $opportunity);
                }

                $lead->set('createdOpportunityId', $opportunity->id);
            }
        }

        if ($duplicateCheck && count($duplicateList)) {
            $reason = [
                'reason' => 'duplicate',
                'duplicates' => $duplicateList,
            ];

            throw new ConflictSilent(json_encode($reason));
        }

        $lead->set('status', 'Converted');

        $entityManager->saveEntity($lead);

        $leadRepisotory = $entityManager->getRepository('Lead');

        $meetings = $leadRepisotory
            ->getRelation($lead, 'meetings')
            ->select(['id', 'parentId', 'parentType'])
            ->find();

        foreach ($meetings as $meeting) {
            if (!empty($contact)) {
                $entityManager->getRepository('Meeting')->relate($meeting, 'contacts', $contact);
            }

            if (!empty($opportunity)) {
                $meeting->set('parentId', $opportunity->id);
                $meeting->set('parentType', 'Opportunity');

                $entityManager->saveEntity($meeting);
            }
            else if (!empty($account)) {
                $meeting->set('parentId', $account->id);
                $meeting->set('parentType', 'Account');

                $entityManager->saveEntity($meeting);
            }
        }

        $calls = $leadRepisotory
            ->getRelation($lead, 'calls')
            ->select(['id', 'parentId', 'parentType'])
            ->find();

        foreach ($calls as $call) {
            if (!empty($contact)) {
                $entityManager->getRepository('Call')->relate($call, 'contacts', $contact);
            }

            if (!empty($opportunity)) {
                $call->set('parentId', $opportunity->id);
                $call->set('parentType', 'Opportunity');

                $entityManager->saveEntity($call);
            }
            else if (!empty($account)) {
                $call->set('parentId', $account->id);
                $call->set('parentType', 'Account');

                $entityManager->saveEntity($call);
            }
        }

        $emails = $leadRepisotory
            ->getRelation($lead, 'emails')
            ->select(['id', 'parentId', 'parentType'])
            ->find();

        foreach ($emails as $email) {
            if (!empty($opportunity)) {
                $email->set('parentId', $opportunity->id);
                $email->set('parentType', 'Opportunity');

                $entityManager->saveEntity($email);
            }
            else if (!empty($account)) {
                $email->set('parentId', $account->id);
                $email->set('parentType', 'Account');

                $entityManager->saveEntity($email);
            }
        }

        $documents = $leadRepisotory
            ->getRelation($lead, 'documents')
            ->select(['id'])
            ->find();

        foreach ($documents as $document) {
            if (!empty($account)) {
                $entityManager->getRepository('Document')->relate($document, 'accounts', $account);
            }

            if (!empty($opportunity)) {
                $entityManager->getRepository('Document')->relate($document, 'opportunities', $opportunity);
            }
        }

        $streamService = $this->getStreamService();

        if ($streamService->checkIsFollowed($lead, $this->getUser()->id)) {
            if (!empty($opportunity)) {
                $streamService->followEntity($opportunity, $this->getUser()->id);
            }

            if (!empty($account)) {
                $streamService->followEntity($account, $this->getUser()->id);
            }

            if (!empty($contact)) {
                $streamService->followEntity($contact, $this->getUser()->id);
            }
        }

        return $lead;
    }
}
