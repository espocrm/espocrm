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

namespace Espo\Modules\Crm\Tools\Lead;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\ConflictSilent;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Document;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Crm\Tools\Lead\Convert\Params;
use Espo\Modules\Crm\Tools\Lead\Convert\Values;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Tools\Stream\Service as StreamService;

class ConvertService
{
    public function __construct(
        private Acl $acl,
        private ServiceContainer $recordServiceContainer,
        private EntityManager $entityManager,
        private User $user,
        private StreamService $streamService,
        private Metadata $metadata,
        private FieldUtil $fieldUtil
    ) {}

    /**
     * Convert a lead.
     *
     * @throws Forbidden
     * @throws Conflict
     * @throws NotFound
     */
    public function convert(string $id, Values $records, Params $params): Lead
    {
        $lead = $this->getLead($id);

        $duplicateList = [];
        $skipSave = false;

        $duplicateCheck = !$params->skipDuplicateCheck();

        $account = $this->processAccount(
            $lead,
            $records,
            $duplicateCheck,
            $duplicateList,
            $skipSave
        );

        $contact = $this->processContact(
            $lead,
            $records,
            $duplicateCheck,
            $duplicateList,
            $skipSave,
            $account
        );

        $opportunity = $this->processOpportunity(
            $lead,
            $records,
            $duplicateCheck,
            $duplicateList,
            $skipSave,
            $account,
            $contact
        );

        if ($duplicateCheck && count($duplicateList)) {
            throw ConflictSilent::createWithBody('duplicate', Json::encode($duplicateList));
        }

        $lead->setStatus(Lead::STATUS_CONVERTED);

        $this->entityManager->saveEntity($lead);

        $this->processLinks($lead, $account, $contact, $opportunity);
        $this->processStream($lead, $account, $contact, $opportunity);

        return $lead;
    }

    /**
     * Get values for the conversion form.
     *
     * @throws Forbidden
     * @throws NotFound
     */
    public function getValues(string $id): Values
    {
        $lead = $this->getLead($id);

        $values = Values::create();

        /** @var string[] $entityList */
        $entityList = $this->metadata->get('entityDefs.Lead.convertEntityList', []);

        $ignoreAttributeList = [
            'createdAt',
            'modifiedAt',
            'modifiedById',
            'modifiedByName',
            'createdById',
            'createdByName',
        ];

        /** @var array<string, array<string, string>> $convertFieldsDefs */
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
                if (!$this->metadata->get('entityDefs.' . $entityType . '.fields.' . $field)) {
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
                $type = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']);

                if (in_array($type, ['file', 'image'])) {
                    $attachment = $lead->get($leadField);

                    if ($attachment) {
                        $attachment = $this->getAttachmentRepository()->getCopiedAttachment($attachment);

                        $idAttribute = $field . 'Id';
                        $nameAttribute = $field . 'Name';

                        $attributes[$idAttribute] = $attachment->getId();
                        $attributes[$nameAttribute] = $attachment->getName();
                    }

                    continue;
                }
                else if ($type === 'attachmentMultiple') {
                    $attachmentList = $lead->get($leadField);

                    if (count($attachmentList)) {
                        $idList = [];
                        $nameHash = (object) [];
                        $typeHash = (object) [];

                        foreach ($attachmentList as $attachment) {
                            $attachment = $this->getAttachmentRepository()->getCopiedAttachment($attachment);

                            $idList[] = $attachment->getId();

                            $nameHash->{$attachment->getId()} = $attachment->getName();
                            $typeHash->{$attachment->getId()} = $attachment->getType();
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

                $leadAttributeList = $this->fieldUtil->getAttributeList(Lead::ENTITY_TYPE, $leadField);

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

            $values = $values->with($entityType, (object) $attributes);
        }

        return $values;
    }

    private function getAttachmentRepository(): AttachmentRepository
    {
        /** @var AttachmentRepository */
        return $this->entityManager->getRepository(Attachment::ENTITY_TYPE);
    }

    /**
     * @param Entity[] $duplicateList
     */
    private function processAccount(
        Lead $lead,
        Values $records,
        bool $duplicateCheck,
        array &$duplicateList,
        bool &$skipSave
    ): ?Account {

        if (!$records->has(Account::ENTITY_TYPE)) {
            return null;
        }

        $account = $this->entityManager->getRDBRepositoryByClass(Account::class)->getNew();

        $account->set($records->get(Account::ENTITY_TYPE));

        if ($duplicateCheck) {
            $itemDuplicateList = $this->recordServiceContainer
                ->getByClass(Account::class)
                ->findDuplicates($account) ?? [];

            foreach ($itemDuplicateList as $e) {
                $duplicateList[] = (object) [
                    'id' => $e->getId(),
                    'name' => $e->getName(),
                    '_entityType' => $e->getEntityType(),
                ];

                $skipSave = true;
            }
        }

        if (!$skipSave) {
            $this->entityManager->saveEntity($account);

            $lead->set('createdAccountId', $account->getId());
        }

        return $account;
    }

    /**
     * @param Entity[] $duplicateList
     */
    private function processContact(
        Lead $lead,
        Values $records,
        bool $duplicateCheck,
        array &$duplicateList,
        bool &$skipSave,
        ?Account $account
    ): ?Contact {

        if (!$records->has(Contact::ENTITY_TYPE)) {
            return null;
        }

        $contact = $this->entityManager->getRDBRepositoryByClass(Contact::class)->getNew();

        $contact->set($records->get(Contact::ENTITY_TYPE));

        if ($account && $account->hasId()) {
            $contact->set('accountId', $account->getId());
        }

        if ($duplicateCheck) {
            $itemDuplicateList = $this->recordServiceContainer
                ->getByClass(Contact::class)
                ->findDuplicates($contact) ?? [];

            foreach ($itemDuplicateList as $e) {
                $duplicateList[] = (object) [
                    'id' => $e->getId(),
                    'name' => $e->getName(),
                    '_entityType' => $e->getEntityType(),
                ];

                $skipSave = true;
            }
        }

        if (!$skipSave) {
            $this->entityManager->saveEntity($contact);

            $lead->set('createdContactId', $contact->getId());
        }

        return $contact;
    }

    /**
     * @param Entity[] $duplicateList
     */
    private function processOpportunity(
        Lead $lead,
        Values $records,
        bool $duplicateCheck,
        array &$duplicateList,
        bool &$skipSave,
        ?Account $account,
        ?Contact $contact,
    ): ?Opportunity {

        if (!$records->has(Opportunity::ENTITY_TYPE)) {
            return null;
        }

        $opportunity = $this->entityManager->getRDBRepositoryByClass(Opportunity::class)->getNew();

        $opportunity->set($records->get(Opportunity::ENTITY_TYPE));

        if ($account && $account->hasId()) {
            $opportunity->set('accountId', $account->getId());
        }

        if ($contact && $contact->hasId()) {
            $opportunity->set('contactId', $contact->getId());
        }

        if ($duplicateCheck) {
            $itemDuplicateList = $this->recordServiceContainer
                ->getByClass(Opportunity::class)
                ->findDuplicates($opportunity) ?? [];

            foreach ($itemDuplicateList as $e) {
                $duplicateList[] = (object)[
                    'id' => $e->getId(),
                    'name' => $e->getName(),
                    '_entityType' => $e->getEntityType(),
                ];

                $skipSave = true;
            }
        }

        if (!$skipSave) {
            $this->entityManager->saveEntity($opportunity);

            if ($contact && $contact->hasId()) {
                $this->entityManager
                    ->getRDBRepository(Contact::ENTITY_TYPE)
                    ->getRelation($contact, 'opportunities')
                    ->relate($opportunity);
            }

            $lead->set('createdOpportunityId', $opportunity->getId());
        }

        return $opportunity;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    private function getLead(string $id): Lead
    {
        $lead = $this->recordServiceContainer
            ->getByClass(Lead::class)
            ->getEntity($id);

        if (!$lead) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityEdit($lead)) {
            throw new Forbidden("No edit access.");
        }

        return $lead;
    }

    private function processLinks(
        Lead $lead,
        ?Account $account,
        ?Contact $contact,
        ?Opportunity $opportunity
    ): void {

        $leadRepository = $this->entityManager->getRDBRepositoryByClass(Lead::class);

        /** @var Collection<Meeting> $meetings */
        $meetings = $leadRepository
            ->getRelation($lead, 'meetings')
            ->select(['id', 'parentId', 'parentType'])
            ->find();

        foreach ($meetings as $meeting) {
            if ($contact && $contact->hasId()) {
                $this->entityManager
                    ->getRDBRepository(Meeting::ENTITY_TYPE)
                    ->getRelation($meeting, 'contacts')
                    ->relate($contact);
            }

            if ($opportunity && $opportunity->hasId()) {
                $meeting->set('parentId', $opportunity->getId());
                $meeting->set('parentType', Opportunity::ENTITY_TYPE);

                $this->entityManager->saveEntity($meeting);
            } else if ($account && $account->hasId()) {
                $meeting->set('parentId', $account->getId());
                $meeting->set('parentType', Account::ENTITY_TYPE);

                $this->entityManager->saveEntity($meeting);
            }
        }

        /** @var Collection<Call> $calls */
        $calls = $leadRepository
            ->getRelation($lead, 'calls')
            ->select(['id', 'parentId', 'parentType'])
            ->find();

        foreach ($calls as $call) {
            if ($contact && $contact->hasId()) {
                $this->entityManager
                    ->getRDBRepository(Call::ENTITY_TYPE)
                    ->getRelation($call, 'contacts')
                    ->relate($contact);
            }

            if ($opportunity && $opportunity->hasId()) {
                $call->set('parentId', $opportunity->getId());
                $call->set('parentType', Opportunity::ENTITY_TYPE);

                $this->entityManager->saveEntity($call);
            } else if ($account && $account->hasId()) {
                $call->set('parentId', $account->getId());
                $call->set('parentType', Account::ENTITY_TYPE);

                $this->entityManager->saveEntity($call);
            }
        }

        /** @var Collection<Email> $emails */
        $emails = $leadRepository
            ->getRelation($lead, 'emails')
            ->select(['id', 'parentId', 'parentType'])
            ->find();

        foreach ($emails as $email) {
            if ($opportunity && $opportunity->hasId()) {
                $email->set('parentId', $opportunity->getId());
                $email->set('parentType', Opportunity::ENTITY_TYPE);

                $this->entityManager->saveEntity($email);
            } else if ($account && $account->hasId()) {
                $email->set('parentId', $account->getId());
                $email->set('parentType', Account::ENTITY_TYPE);

                $this->entityManager->saveEntity($email);
            }
        }

        /** @var Collection<Document> $documents */
        $documents = $leadRepository
            ->getRelation($lead, 'documents')
            ->select(['id'])
            ->find();

        foreach ($documents as $document) {
            if ($account && $account->hasId()) {
                $this->entityManager
                    ->getRDBRepository(Document::ENTITY_TYPE)
                    ->getRelation($document, 'accounts')
                    ->relate($account);
            }

            if ($opportunity && $opportunity->hasId()) {
                $this->entityManager
                    ->getRDBRepository(Document::ENTITY_TYPE)
                    ->getRelation($document, 'opportunities')
                    ->relate($opportunity);
            }
        }
    }

    private function processStream(
        Lead $lead,
        ?Account $account,
        ?Contact $contact,
        ?Opportunity $opportunity
    ): void {

        if (!$this->streamService->checkIsFollowed($lead, $this->user->getId())) {
            return;
        }

        if ($opportunity && $opportunity->hasId()) {
            $this->streamService->followEntity($opportunity, $this->user->getId());
        }

        if ($account && $account->hasId()) {
            $this->streamService->followEntity($account, $this->user->getId());
        }

        if ($contact && $contact->hasId()) {
            $this->streamService->followEntity($contact, $this->user->getId());
        }
    }
}
