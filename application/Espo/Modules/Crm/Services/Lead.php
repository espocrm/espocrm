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

namespace Espo\Modules\Crm\Services;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ConflictSilent;

use Espo\Core\Utils\Json;

use Espo\ORM\Entity;

use Espo\Modules\Crm\Entities\Lead as LeadEntity;

use Espo\Services\Record;

use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Entities\Attachment;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Crm\Entities\Contact;

use Espo\Core\Di;

use stdClass;

/**
 * @extends Record<\Espo\Modules\Crm\Entities\Lead>
 */
class Lead extends Record implements

    Di\FieldUtilAware
{
    use Di\FieldUtilSetter;

    protected $linkMandatorySelectAttributeList = [
        'targetLists' => ['isOptedOut'],
    ];

    protected function afterCreateEntity(Entity $entity, $data)
    {
        if (!empty($data->emailId)) {
            $email = $this->entityManager->getEntity('Email', $data->emailId);

            if ($email && !$email->get('parentId') && $this->acl->check($email)) {
                $email->set([
                    'parentType' => 'Lead',
                    'parentId' => $entity->getId(),
                ]);

                $this->entityManager->saveEntity($email);
            }
        }
        if ($entity->get('campaignId')) {
            $campaign = $this->entityManager->getEntity('Campaign', $entity->get('campaignId'));

            if ($campaign) {
                $log = $this->entityManager->getNewEntity('CampaignLogRecord');

                $log->set([
                    'action' => 'Lead Created',
                    'actionDate' => date('Y-m-d H:i:s'),
                    'parentType' => 'Lead',
                    'parentId' => $entity->getId(),
                    'campaignId' => $campaign->getId(),
                ]);

                $this->entityManager->saveEntity($log);
            }
        }
    }

    /**
     * @return array<string,array<string,mixed>>
     * @throws Forbidden
     */
    public function getConvertAttributes(string $id): array
    {
        /** @var LeadEntity $lead */
        $lead = $this->getEntity($id);

        if (!$this->acl->check($lead, 'read')) {
            throw new Forbidden();
        }

        $data = [];

        /** @var string[] $entityList */
        $entityList = $this->metadata->get('entityDefs.Lead.convertEntityList', []);

        $ignoreAttributeList = ['createdAt', 'modifiedAt', 'modifiedById', 'modifiedByName', 'createdById', 'createdByName'];

        /** @var array<string,array<string,string>> $convertFieldsDefs */
        $convertFieldsDefs = $this->metadata->get('entityDefs.Lead.convertFields', []);

        foreach ($entityList as $entityType) {
            if (!$this->acl->checkScope($entityType, 'edit')) {
                continue;
            }

            $attributes = [];
            $fieldMap = [];

            /** @var string[] $fieldList */
            $fieldList = array_keys($this->metadata->get('entityDefs.Lead.fields', []));

            foreach ($fieldList as $field) {
                if (!$this->metadata->get('entityDefs.'.$entityType.'.fields.' . $field)) {
                    continue;
                }

                if (
                    $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type'])
                    !==
                    $this->metadata->get(['entityDefs', 'Lead', 'fields', $field, 'type'])
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
                $type = $this->metadata->get(['entityDefs', 'Lead', 'fields', $field, 'type']);

                if (in_array($type, ['file', 'image'])) {
                    $attachment = $lead->get($field);

                    if ($attachment) {
                        $attachment = $this->getAttachmentRepository()->getCopiedAttachment($attachment);

                        $idAttribute = $field . 'Id';
                        $nameAttribute = $field . 'Name';

                        $attributes[$idAttribute] = $attachment->getId();
                        $attributes[$nameAttribute] = $attachment->get('name');
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
                            $attachment = $this->getAttachmentRepository()->getCopiedAttachment($attachment);

                            $idList[] = $attachment->getId();

                            $nameHash->{$attachment->getId()} = $attachment->get('name');
                            $typeHash->{$attachment->getId()} = $attachment->get('type');
                        }

                        $attributes[$field . 'Ids'] = $idList;
                        $attributes[$field . 'Names'] = $nameHash;
                        $attributes[$field . 'Types'] = $typeHash;
                    }

                    continue;
                }
                else if ($type === 'linkMultiple') {
                    $attributes[$field . 'Ids'] = $lead->get($leadField . 'Ids');
                    $attributes[$field . 'Names'] = $lead->get($leadField . 'Names');
                    $attributes[$field . 'Columns'] = $lead->get($leadField . 'Columns');

                    continue;
                }

                $leadAttributeList = $this->fieldUtil->getAttributeList('Lead', $leadField);

                $attributeList = $this->fieldUtil->getAttributeList($entityType, $field);

                if (count($attributeList) !== count($leadAttributeList)) {
                    continue;
                }

                foreach ($attributeList as $i => $attribute) {
                    if (in_array($attribute, $ignoreAttributeList)) {
                        continue;
                    }

                    $leadAttribute = $leadAttributeList[$i] ?? null;

                    if (!$leadAttribute || !$lead->has($leadAttribute)) {
                        continue;
                    }

                    $attributes[$attribute] = $lead->get($leadAttribute);
                }
            }

            $data[$entityType] = $attributes;
        }

        return $data;
    }

    /**
     * @param stdClass|null $additionalData
     * @throws Forbidden
     * @throws ConflictSilent
     */
    public function convert(string $id, object $recordsData, ?object $additionalData = null): LeadEntity
    {
        /** @var LeadEntity $lead */
        $lead = $this->getEntity($id);

        $additionalData = $additionalData ?? (object) [];

        if (!$this->acl->check($lead, 'edit')) {
            throw new Forbidden();
        }

        $duplicateList = [];
        $duplicateCheck = !($additionalData->skipDuplicateCheck ?? false);

        $entityManager = $this->entityManager;

        $skipSave = false;

        $contact = null;
        $account = null;
        $opportunity = null;

        if (!empty($recordsData->Account)) {
            $account = $entityManager->getNewEntity('Account');

            $account->set(get_object_vars($recordsData->Account));

            if ($duplicateCheck) {
                /** @var Account[] $rDuplicateList */
                $rDuplicateList = $this->recordServiceContainer
                    ->get('Account')
                    ->findDuplicates($account);

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

                $lead->set('createdAccountId', $account->getId());
            }
        }

        if (!empty($recordsData->Contact)) {
            $contact = $entityManager->getNewEntity('Contact');

            $contact->set(get_object_vars($recordsData->Contact));

            if ($account && $account->hasId()) {
                $contact->set('accountId', $account->getId());
            }

            if ($duplicateCheck) {
                /** @var Contact[] $rDuplicateList */
                $rDuplicateList = $this->recordServiceContainer
                    ->get('Contact')
                    ->findDuplicates($contact);

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

                $lead->set('createdContactId', $contact->getId());
            }
        }

        if (!empty($recordsData->Opportunity)) {
            $opportunity = $entityManager->getNewEntity('Opportunity');

            $opportunity->set(get_object_vars($recordsData->Opportunity));

            if ($account && $account->hasId()) {
                $opportunity->set('accountId', $account->getId());
            }

            if ($contact && $contact->hasId()) {
                $opportunity->set('contactId', $contact->getId());
            }

            if ($duplicateCheck) {
                /** @var Opportunity[] $rDuplicateList */
                $rDuplicateList = $this->recordServiceContainer
                    ->get('Opportunity')
                    ->findDuplicates($opportunity);

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

                if ($contact && $contact->hasId()) {
                    $entityManager->getRDBRepository('Contact')->relate($contact, 'opportunities', $opportunity);
                }

                $lead->set('createdOpportunityId', $opportunity->getId());
            }
        }

        if ($duplicateCheck && count($duplicateList)) {
            $reason = [
                'reason' => 'duplicate',
                'duplicates' => $duplicateList,
            ];

            throw new ConflictSilent(Json::encode($reason));
        }

        $lead->set('status', 'Converted');

        $entityManager->saveEntity($lead);

        $leadRepisotory = $entityManager->getRDBRepository('Lead');

        $meetings = $leadRepisotory
            ->getRelation($lead, 'meetings')
            ->select(['id', 'parentId', 'parentType'])
            ->find();

        foreach ($meetings as $meeting) {
            if ($contact &&  $contact->hasId()) {
                $entityManager->getRDBRepository('Meeting')->relate($meeting, 'contacts', $contact);
            }

            if ($opportunity && $opportunity->hasId()) {
                $meeting->set('parentId', $opportunity->getId());
                $meeting->set('parentType', 'Opportunity');

                $entityManager->saveEntity($meeting);
            }
            else if ($account && $account->hasId()) {
                $meeting->set('parentId', $account->getId());
                $meeting->set('parentType', 'Account');

                $entityManager->saveEntity($meeting);
            }
        }

        $calls = $leadRepisotory
            ->getRelation($lead, 'calls')
            ->select(['id', 'parentId', 'parentType'])
            ->find();

        foreach ($calls as $call) {
            if ($contact && $contact->hasId()) {
                $entityManager->getRDBRepository('Call')->relate($call, 'contacts', $contact);
            }

            if ($opportunity && $opportunity->hasId()) {
                $call->set('parentId', $opportunity->getId());
                $call->set('parentType', 'Opportunity');

                $entityManager->saveEntity($call);
            }
            else if ($account && $account->hasId()) {
                $call->set('parentId', $account->getId());
                $call->set('parentType', 'Account');

                $entityManager->saveEntity($call);
            }
        }

        $emails = $leadRepisotory
            ->getRelation($lead, 'emails')
            ->select(['id', 'parentId', 'parentType'])
            ->find();

        foreach ($emails as $email) {
            if ($opportunity && $opportunity->hasId()) {
                $email->set('parentId', $opportunity->getId());
                $email->set('parentType', 'Opportunity');

                $entityManager->saveEntity($email);
            }
            else if ($account && $account->hasId()) {
                $email->set('parentId', $account->getId());
                $email->set('parentType', 'Account');

                $entityManager->saveEntity($email);
            }
        }

        $documents = $leadRepisotory
            ->getRelation($lead, 'documents')
            ->select(['id'])
            ->find();

        foreach ($documents as $document) {
            if ($account && $account->hasId()) {
                $entityManager->getRDBRepository('Document')->relate($document, 'accounts', $account);
            }

            if ($opportunity && $opportunity->hasId()) {
                $entityManager->getRDBRepository('Document')->relate($document, 'opportunities', $opportunity);
            }
        }

        $streamService = $this->getStreamService();

        if ($streamService->checkIsFollowed($lead, $this->getUser()->getId())) {
            if ($opportunity && $opportunity->hasId()) {
                $streamService->followEntity($opportunity, $this->getUser()->getId());
            }

            if ($account && $account->hasId()) {
                $streamService->followEntity($account, $this->getUser()->getId());
            }

            if ($contact && $contact->hasId()) {
                $streamService->followEntity($contact, $this->getUser()->getId());
            }
        }

        return $lead;
    }

    private function getAttachmentRepository(): AttachmentRepository
    {
        /** @var AttachmentRepository */
        return $this->entityManager->getRepository(Attachment::ENTITY_TYPE);
    }
}
