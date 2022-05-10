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

namespace Espo\Tools\MassUpdate;

use Espo\Core\MassAction\QueryBuilder;
use Espo\Core\MassAction\Params;
use Espo\Core\MassAction\Result;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Record\ServiceFactory;
use Espo\Core\Record\Service;

use Espo\Core\Utils\FieldUtil;
use Espo\Core\Exceptions\Forbidden;

use Espo\ORM\EntityManager;
use Espo\ORM\Entity;

use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Entities\User;
use Espo\Entities\Attachment;

use Exception;
use RuntimeException;
use stdClass;

class Processor
{
    private ValueMapPreparator $valueMapPreparator;

    private QueryBuilder $queryBuilder;

    private Acl $acl;

    private ServiceFactory $serviceFactory;

    private EntityManager $entityManager;

    private FieldUtil $fieldUtil;

    private User $user;

    private const PERMISSION = 'massUpdatePermission';

    public function __construct(
        ValueMapPreparator $valueMapPreparator,
        QueryBuilder $queryBuilder,
        Acl $acl,
        ServiceFactory $serviceFactory,
        EntityManager $entityManager,
        FieldUtil $fieldUtil,
        User $user
    ) {
        $this->valueMapPreparator = $valueMapPreparator;
        $this->queryBuilder = $queryBuilder;
        $this->acl = $acl;
        $this->serviceFactory = $serviceFactory;
        $this->entityManager = $entityManager;
        $this->fieldUtil = $fieldUtil;
        $this->user = $user;
    }

    public function process(Params $params, Data $data): Result
    {
        $entityType = $params->getEntityType();

        if (!$this->acl->check($entityType, Table::ACTION_EDIT)) {
            throw new Forbidden("No edit access for '{$entityType}'.");
        }

        if ($this->acl->get(self::PERMISSION) !== Table::LEVEL_YES) {
            throw new Forbidden("No mass-update permission.");
        }

        $service = $this->serviceFactory->create($entityType);

        $filteredData = $this->filterData($data, $service);

        if ($filteredData->getAttributeList() === []) {
            return new Result(0, []);
        }

        $copyFieldList = $this->detectFieldToCopyList($entityType, $filteredData);

        $query = $this->queryBuilder->build($params);

        $collection = $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($query)
            ->sth()
            ->find();

        $ids = [];
        $count = 0;

        foreach ($collection as $i => $entity) {
            $itemResult = $this->processEntity($entity, $filteredData, $i, $copyFieldList, $service);

            if (!$itemResult) {
                continue;
            }

            $ids[] = $entity->getId();
            $count++;
        }

        return new Result($count, $ids);
    }

    /**
     * @param Service<Entity> $service
     */
    private function filterData(Data $data, Service $service): Data
    {
        $filteredData = $data;

        $values = $data->getValues();

        $service->filterUpdateInput($values);

        foreach ($data->getAttributeList() as $attribute) {
            if (!property_exists($values, $attribute)) {
                $filteredData = $filteredData->without($attribute);

                continue;
            }

            $action = $filteredData->getAction($attribute) ?? Action::UPDATE;
            $value = $values->$attribute;

            $filteredData = $filteredData->with($attribute, $value, $action);
        }

        return $filteredData;
    }

    /**
     * @param string[] $fieldToCopyList
     * @param Service<Entity> $service
     */
    private function processEntity(Entity $entity, Data $data, int $i, array $fieldToCopyList, Service $service): bool
    {
        if (!$this->acl->check($entity, Table::ACTION_EDIT)) {
            return false;
        }

        $values = $this->prepareItemValueMap($entity, $data, $i, $fieldToCopyList);

        $entity->set($values);

        try {
            $service->processValidation($entity, $values);
        }
        catch (Exception $e) {
            return false;
        }

        if (!$service->checkAssignment($entity)) {
            return false;
        }

        $this->entityManager->saveEntity($entity, [
            'massUpdate' => true,
            'skipStreamNotesAcl' => true,
            'modifiedById' => $this->user->getId(),
        ]);

        $service->processActionHistoryRecord('update', $entity);

        return true;
    }

    /**
     * @param string[] $copyFieldList
     */
    private function prepareItemValueMap(Entity $entity, Data $data, int $i, array $copyFieldList): stdClass
    {
        $dataModified = $this->copy($entity->getEntityType(), $data, $i, $copyFieldList);

        return $this->valueMapPreparator->prepare($entity, $dataModified);
    }

    /**
     * @param string[] $copyFieldList
     */
    private function copy(string $entityType, Data $data, int $i, array $copyFieldList): Data
    {
        if (!count($copyFieldList)) {
            return $data;
        }

        if ($i === 0) {
            return $data;
        }

        foreach ($copyFieldList as $field) {
            $type = $this->fieldUtil->getEntityTypeFieldParam($entityType, $field, 'type');

            if ($type === 'file' || $type === 'image') {
                $data = $this->copyFileField($field, $data);

                continue;
            }

             if ($type === 'attachmentMultiple') {
                $data = $this->copyAttachmentMultipleField($field, $data);

                continue;
            }
        }

        return $data;
    }

    private function copyFileField(string $field, Data $data): Data
    {
        $attribute = $field . 'Id';

        $id = $data->getValue($attribute);

        if (!$id) {
            return $data;
        }

        $attachment = $this->entityManager->getEntityById(Attachment::ENTITY_TYPE, $id);

        if (!$attachment) {
            return $data->with($attribute, null);
        }

        /** @var AttachmentRepository $attachmentRepository */
        $attachmentRepository = $this->entityManager->getRepository(Attachment::ENTITY_TYPE);

        $copiedAttachment = $attachmentRepository->getCopiedAttachment($attachment);

        return $data->with($attribute, $copiedAttachment->getId());
    }

    private function copyAttachmentMultipleField(string $field, Data $data): Data
    {
        $attribute = $field . 'Ids';

        $ids = $data->getValue($attribute) ?? [];

        if (!is_array($ids)) {
            throw new RuntimeException("Bad link-multiple-ids value.");
        }

        if (!count($ids)) {
            return $data;
        }

        /** @var AttachmentRepository $attachmentRepository */
        $attachmentRepository = $this->entityManager->getRepository(Attachment::ENTITY_TYPE);

        $copiedIds = [];

        foreach ($ids as $id) {
            $attachment = $this->entityManager->getEntityById(Attachment::ENTITY_TYPE, $id);

            if (!$attachment) {
                continue;
            }

            $copiedIds[] = $attachmentRepository
                ->getCopiedAttachment($attachment)
                ->getId();
        }

        return $data->with($attribute, $copiedIds);
    }

    /**
     * @return string[]
     */
    private function detectFieldToCopyList(string $entityType, Data $data): array
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
                if ($data->getValue($attribute)) {
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
