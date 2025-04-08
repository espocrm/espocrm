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

namespace Espo\Tools\MassUpdate;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\FieldProcessing\LinkMultiple\ListLoader as LinkMultipleLoader;
use Espo\Core\FieldProcessing\Loader\Params as LoaderParams;
use Espo\Core\MassAction\QueryBuilder;
use Espo\Core\MassAction\Params;
use Espo\Core\MassAction\Result;
use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Record\Access\LinkCheck;
use Espo\Core\Record\ActionHistory\Action as RecordAction;
use Espo\Core\Record\ServiceFactory;
use Espo\Core\Record\Service;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\ObjectUtil;
use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Entities\User;
use Espo\Entities\Attachment;
use Espo\Core\ORM\Type\FieldType;

use Exception;
use RuntimeException;
use stdClass;

class Processor
{
    public function __construct(
        private ValueMapPreparator $valueMapPreparator,
        private QueryBuilder $queryBuilder,
        private Acl $acl,
        private ServiceFactory $serviceFactory,
        private EntityManager $entityManager,
        private FieldUtil $fieldUtil,
        private User $user,
        private LinkCheck $linkCheck,
        private LinkMultipleLoader $linkMultipleLoader,
        private Log $log,
    ) {}

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function process(Params $params, Data $data): Result
    {
        $entityType = $params->getEntityType();

        if (!$this->acl->check($entityType, Table::ACTION_EDIT)) {
            throw new Forbidden("No edit access for '$entityType'.");
        }

        if ($this->acl->getPermissionLevel(Acl\Permission::MASS_UPDATE) !== Table::LEVEL_YES) {
            throw new Forbidden("No mass-update permission.");
        }

        $service = $this->serviceFactory->create($entityType);

        $filteredData = $this->filterData($entityType, $data, $service);

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
            $itemResult = $this->processEntity(
                entity: $entity,
                data: $filteredData,
                i: $i,
                fieldToCopyList: $copyFieldList,
                service: $service,
            );

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
    private function filterData(string $entityType, Data $data, Service $service): Data
    {
        $filteredData = $data;

        $values = $data->getValues();

        $service->filterUpdateInput($values);
        $service->sanitizeInput($values);
        $this->filterDisabledFields($entityType, $values);

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
    private function processEntity(
        Entity $entity,
        Data $data,
        int $i,
        array $fieldToCopyList,
        Service $service
    ): bool {

        $service->loadAdditionalFields($entity);

        if (!$this->acl->check($entity, Table::ACTION_EDIT)) {
            return false;
        }

        $values = $this->prepareItemValueMap($entity, $data, $i, $fieldToCopyList);

        $service->filterInputReadOnlySaved($entity, $values);

        if (count(get_object_vars($values)) === 0) {
            return false;
        }

        $entity->set($values);

        try {
            $service->processValidation($entity, $values);
        } catch (Exception) {
            return false;
        }

        if (!$service->checkAssignment($entity)) {
            return false;
        }

        try {
            $this->linkCheck->processFields($entity);
        } catch (Forbidden) {
            return false;
        }

        try {
            $this->entityManager->saveEntity($entity, [
                SaveOption::MASS_UPDATE => true,
                SaveOption::MODIFIED_BY_ID => $this->user->getId(),
                'skipStreamNotesAcl' => true,
            ]);
        } catch (Exception $e) {
            $this->log->info("Mass update save exception. Record: {id}.", [
                'exception' => $e,
                'id' => $entity->getId(),
            ]);

            return false;
        }

        $service->processActionHistoryRecord(RecordAction::UPDATE, $entity);

        return true;
    }

    /**
     * @param string[] $copyFieldList
     */
    private function prepareItemValueMap(Entity $entity, Data $data, int $i, array $copyFieldList): stdClass
    {
        $dataModified = $this->copy($entity->getEntityType(), $data, $i, $copyFieldList);

        $values = $this->valueMapPreparator->prepare($entity, $dataModified);

        return ObjectUtil::clone($values);
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

            if ($type === FieldType::FILE || $type === FieldType::IMAGE) {
                $data = $this->copyFileField($field, $data);

                continue;
            }

             if ($type === FieldType::ATTACHMENT_MULTIPLE) {
                $data = $this->copyAttachmentMultipleField($field, $data);

                //continue;
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

        $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getById($id);

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
            $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getById($id);

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
            $this->fieldUtil->getFieldByTypeList($entityType, FieldType::FILE),
            $this->fieldUtil->getFieldByTypeList($entityType, FieldType::IMAGE),
            $this->fieldUtil->getFieldByTypeList($entityType, FieldType::ATTACHMENT_MULTIPLE)
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

    private function filterDisabledFields(string $entityType, stdClass $values): void
    {
        $fieldDefsList = array_filter(
            $this->entityManager
                ->getDefs()
                ->getEntity($entityType)
                ->getFieldList(),
            fn ($it) => $it->getParam('massUpdateDisabled')
        );

        foreach ($fieldDefsList as $fieldDefs) {
            foreach ($this->fieldUtil->getActualAttributeList($entityType, $fieldDefs->getName()) as $attribute) {
                unset($values->$attribute);
            }
        }
    }
}
