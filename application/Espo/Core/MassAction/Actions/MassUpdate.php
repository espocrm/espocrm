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

namespace Espo\Core\MassAction\Actions;

use Espo\Repositories\Attachment as AttachmentRepository;

use Espo\Core\{
    MassAction\QueryBuilder,
    MassAction\Params,
    MassAction\Result,
    MassAction\Data,
    MassAction\MassAction,
    Acl,
    Record\ServiceContainer as RecordServiceContainer,
    ORM\EntityManager,
    Utils\FieldUtil,
    Utils\ObjectUtil,
    Exceptions\Forbidden,
};

use Exception;
use stdClass;

class MassUpdate implements MassAction
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var Acl
     */
    protected $acl;

    /**
     * @var RecordServiceContainer
     */
    protected $recordServiceContainer;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var FieldUtil
     */
    protected $fieldUtil;

    public function __construct(
        QueryBuilder $queryBuilder,
        Acl $acl,
        RecordServiceContainer $recordServiceContainer,
        EntityManager $entityManager,
        FieldUtil $fieldUtil
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->acl = $acl;
        $this->recordServiceContainer = $recordServiceContainer;
        $this->entityManager = $entityManager;
        $this->fieldUtil = $fieldUtil;
    }

    public function process(Params $params, Data $data): Result
    {
        $entityType = $params->getEntityType();

        if (!$this->acl->check($entityType, 'edit')) {
            throw new Forbidden("No edit access for '{$entityType}'.");
        }

        if ($this->acl->get('massUpdatePermission') !== 'yes') {
            throw new Forbidden("No mass-update permission.");
        }

        $valueMap = $data->getRaw();

        $service = $this->recordServiceContainer->get($entityType);

        $repository = $this->entityManager->getRDBRepository($entityType);

        $service->filterUpdateInput($valueMap);

        $fieldToCopyList = $this->detectFieldToCopyList($entityType, $valueMap);

        $query = $this->queryBuilder->build($params);

        $collection = $repository
            ->clone($query)
            ->sth()
            ->find();

        $ids = [];

        $count = 0;

        foreach ($collection as $i => $entity) {
            if (!$this->acl->check($entity, 'edit')) {
                continue;
            }

            $itemValueMap = $this->prepareItemValueMap($entityType, $valueMap, $i, $fieldToCopyList);

            $entity->set($itemValueMap);

            try {
                $service->processValidation($entity, $itemValueMap);
            }
            catch (Exception $e) {
                continue;
            }

            if (!$service->checkAssignment($entity)) {
                continue;
            }

            $repository->save($entity, [
                'massUpdate' => true,
                'skipStreamNotesAcl' => true,
            ]);

            $ids[] = $entity->getId();

            $count++;

            $service->processActionHistoryRecord('update', $entity);
        }

        $result = [
            'count' => $count,
            'ids' => $ids,
        ];

        return Result::fromArray($result);
    }

    protected function prepareItemValueMap(
        string $entityType,
        stdClass $valueMap,
        int $i,
        array $fieldToCopyList
    ): stdClass {

        $clonedValueMap = ObjectUtil::clone($valueMap);

        if (!count($fieldToCopyList)) {
            return $clonedValueMap;
        }

        if ($i === 0) {
            return $clonedValueMap;
        }

        foreach ($fieldToCopyList as $field) {
            $type = $this->fieldUtil->getEntityTypeFieldParam($entityType, $field, 'type');

            if ($type === 'file' || $type === 'image') {
                $this->copyFileField($field, $clonedValueMap);

                continue;
            }

             if ($type === 'attachmentMultiple') {
                $this->copyAttachmentMultipleField($field, $clonedValueMap);

                continue;
            }
        }

        return $clonedValueMap;
    }

    protected function copyFileField(string $field, stdClass $valueMap): void
    {
        $idAttribute = $field . 'Id';

        $id = $valueMap->$idAttribute ?? null;

        if (!$id) {
            return;
        }

        $attachment = $this->entityManager->getEntity('Attachment', $id);

        if (!$attachment) {
            $valueMap->$idAttribute = null;

            return;
        }

        /** @var AttachmentRepository $attachmentRepository */
        $attachmentRepository = $this->entityManager->getRepository('Attachment');

        $copiedAttachment = $attachmentRepository->getCopiedAttachment($attachment);

        $valueMap->$idAttribute = $copiedAttachment->getId();
    }

    protected function copyAttachmentMultipleField(string $field, stdClass $valueMap): void
    {
        $idsAttribute = $field . 'Ids';

        $ids = $valueMap->$idsAttribute ?? [];

        if (!count($ids)) {
            return;
        }

        /** @var AttachmentRepository $attachmentRepository */
        $attachmentRepository = $this->entityManager->getRepository('Attachment');

        $copiedIds = [];

        foreach ($ids as $id) {
            $attachment = $this->entityManager->getEntity('Attachment', $id);

            if (!$attachment) {
                continue;
            }

            $copiedAttachment = $attachmentRepository->getCopiedAttachment($attachment);

            $copiedIds[] = $copiedAttachment->getId();
        }

        $valueMap->$idsAttribute = $copiedIds;
    }

    protected function detectFieldToCopyList(string $entityType, stdClass $valueMap): array
    {
        $resultFieldList = [];

        $fieldList = array_merge(
            $this->fieldUtil->getFieldByTypeList($entityType, 'file'),
            $this->fieldUtil->getFieldByTypeList($entityType, 'image'),
            $this->fieldUtil->getFieldByTypeList($entityType, 'attachmentMultiple')
        );

        foreach ($fieldList as $field) {
            $actualAttributeList = $this->fieldUtil->getActualAttributeList($entityType, $field);

            $met = false;

            foreach ($actualAttributeList as $attribute) {
                $value = $valueMap->$attribute ?? null;

                if ($value) {
                    $met = true;
                }
            }

            if ($met) {
                $resultFieldList[] = $field;
            }
        }

        return $resultFieldList;
    }
}
