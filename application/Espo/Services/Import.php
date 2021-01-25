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

namespace Espo\Services;

use Espo\Core\{
    Exceptions\Forbidden,
    Exceptions\NotFound,
    Exceptions\Error,
    Record\Collection as RecordCollection,
    Di,
    Select\SearchParams,
};

use Espo\{
    ORM\Entity,
    Services\Record,
    Tools\Import\Import as ImportTool,
};

use StdClass;

class Import extends Record implements

    Di\FileManagerAware,
    Di\FileStorageManagerAware
{
    use Di\FileManagerSetter;
    use Di\FileStorageManagerSetter;

    const REVERT_PERMANENTLY_REMOVE_PERIOD_DAYS = 2;

    protected function getFileStorageManager()
    {
        return $this->fileStorageManager;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getAcl()
    {
        return $this->acl;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }

    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);

        $importedCount = $this->getRepository()->countResultRecords($entity, 'imported');
        $duplicateCount = $this->getRepository()->countResultRecords($entity, 'duplicates');
        $updatedCount = $this->getRepository()->countResultRecords($entity, 'updated');

        $entity->set([
            'importedCount' => $importedCount,
            'duplicateCount' => $duplicateCount,
            'updatedCount' => $updatedCount,
        ]);
    }

    public function findLinked(string $id, string $link, array $params) : RecordCollection
    {
        if (!in_array($link, ['imported', 'duplicates', 'updated'])) {
            return parent::findLinked($id, $link, $params);
        }

        $entity = $this->getRepository()->get($id);

        $foreignEntityType = $entity->get('entityType');

        if (!$this->getAcl()->check($entity, 'read')) {
            throw new Forbidden();
        }

        if (!$this->getAcl()->check($foreignEntityType, 'read')) {
            throw new Forbidden();
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from($foreignEntityType)
            ->withStrictAccessControl()
            ->withSearchParams(SearchParams::fromRaw($params))
            ->build();

        $collection = $this->getRepository()->findResultRecords($entity, $link, $query);

        $recordService = $this->recordServiceContainer->get($foreignEntityType);

        foreach ($collection as $e) {
            $recordService->loadAdditionalFieldsForList($e);
            $recordService->prepareEntityForOutput($e);
        }

        $total = $this->getRepository()->countResultRecords($entity, $link, $query);

        return new RecordCollection($collection, $total);
    }

    public function uploadFile(string $contents) : string
    {
        $attachment = $this->getEntityManager()->getEntity('Attachment');

        $attachment->set('type', 'text/csv');
        $attachment->set('role', 'Import File');
        $attachment->set('name', 'import-file.csv');
        $attachment->set('contents', $contents);

        $this->getEntityManager()->saveEntity($attachment);

        return $attachment->id;
    }

    public function revert(string $id)
    {
        $import = $this->entityManager->getEntity('Import', $id);
        if (empty($import)) {
            throw new NotFound("Could not find import record.");
        }

        if (!$this->getAcl()->check($import, 'delete')) {
            throw new Forbidden("No access import record.");
        }

        $importEntityList = $this->entityManager->getRepository('ImportEntity')
            ->sth()
            ->where([
                'importId' => $import->id,
                'isImported' => true,
            ])
            ->find();

        $removeFromDb = false;
        $createdAt = $import->get('createdAt');

        if ($createdAt) {
            $dtNow = new \DateTime();
            $createdAtDt = new \DateTime($createdAt);
            $dayDiff = ($dtNow->getTimestamp() - $createdAtDt->getTimestamp()) / 60 / 60 / 24;
            if ($dayDiff < self::REVERT_PERMANENTLY_REMOVE_PERIOD_DAYS) {
                $removeFromDb = true;
            }
        }

        foreach ($importEntityList as $importEntity) {
            $entityType = $importEntity->get('entityType');
            $entityId = $importEntity->get('entityId');

            if (!$entityType || !$entityId) {
                continue;
            }

            if (!$this->entityManager->hasRepository($entityType)) {
                continue;
            }

            $entity = $this->entityManager->getRepository($entityType)
                ->select(['id'])
                ->where(['id' => $entityId])
                ->findOne();

            if (!$entity) {
                continue;
            }

            $this->entityManager->removeEntity($entity, [
                'noStream' => true,
                'noNotifications' => true,
                'import' => true,
                'silent' => true,
            ]);

            if ($removeFromDb) {
                $this->entityManager->getRepository($entityType)->deleteFromDb($entityId);
            }
        }

        $this->getEntityManager()->removeEntity($import);

        $this->processActionHistoryRecord('delete', $import);

        return true;
    }

    public function removeDuplicates(string $id)
    {
        $import = $this->entityManager->getEntity('Import', $id);
        if (empty($import)) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($import, 'delete')) {
            throw new Forbidden();
        }

        $importEntityList = $this->entityManager->getRepository('ImportEntity')
            ->sth()
            ->where([
                'importId' => $import->id,
                'isDuplicate' => true,
            ])
            ->find();

        foreach ($importEntityList as $importEntity) {
            $entityType = $importEntity->get('entityType');
            $entityId = $importEntity->get('entityId');

            if (!$entityType || !$entityId) {
                continue;
            }

            if (!$this->entityManager->hasRepository($entityType)) {
                continue;
            }

            $entity = $this->entityManager->getRepository($entityType)
                ->select(['id'])
                ->where(['id' => $entityId])
                ->findOne();

            if (!$entity) {
                continue;
            }

            $this->entityManager->removeEntity($entity, [
                'noStream' => true,
                'noNotifications' => true,
                'import' => true,
                'silent' => true,
            ]);

            $this->entityManager->getRepository($entityType)->deleteFromDb($entityId);
        }
    }

    protected function createImportTool() : ImportTool
    {
        return $this->injectableFactory->create(ImportTool::class);
    }

    public function jobRunIdleImport(StdClass $data)
    {
        if (
            empty($data->userId) ||
            empty($data->userId) ||
            !isset($data->importAttributeList) ||
            !isset($data->params) ||
            !isset($data->entityType)
        ) {
            throw new Error("Import: Bad job data.");
        }

        $entityType = $data->entityType;
        $params = json_decode(json_encode($data->params), true);
        $attachmentId = $data->attachmentId;
        $importId = $data->importId;
        $importAttributeList = $data->importAttributeList;
        $userId = $data->userId;

        $user = $this->getEntityManager()->getEntity('User', $userId);

        if (!$user) {
            throw new Error("Import: User not found.");
        }

        if (!$user->get('isActive')) {
            throw new Error("Import: User is not active.");
        }

        return $this->createImportTool()
            ->setEntityType($entityType)
            ->setAttributeList($importAttributeList)
            ->setAttachmentId($attachmentId)
            ->setParams($params)
            ->setId($importId)
            ->setUser($user)
            ->run();
    }

    public function importById(string $id, bool $startFromLastIndex = false, bool $forceResume = false) : StdClass
    {
        $import = $this->getEntityManager()->getEntity('Import', $id);

        if (!$import) {
            throw new NotFound("Import '{$id}' not found.");
        }

        $status = $import->get('status');

        if ($status !== 'Standby') {
            if (in_array($status, ['In Process', 'Failed'])) {
                if (!$forceResume) {
                    throw new Forbidden("Import has '{$status}' status. Use -r flag to force resume.");
                }
            } else {
                throw new Forbidden("Can't run import with '{$status}' status.");
            }
        }

        $entityType = $import->get('entityType');
        $attributeList = $import->get('attributeList') ?? [];

        $params = $import->get('params') ?? (object) [];
        $params = json_decode(json_encode($params), true);

        $params['startFromLastIndex'] = $startFromLastIndex;

        $attachmentId = $import->get('fileId');

        return $this->createImportTool()
            ->setEntityType($entityType)
            ->setAttributeList($attributeList)
            ->setAttachmentId($attachmentId)
            ->setParams($params)
            ->setId($id)
            ->run();
    }

    public function import(string $entityType, array $attributeList, string $attachmentId, array $params = []) : StdClass
    {
        $result = $this->createImportTool()
            ->setEntityType($entityType)
            ->setAttributeList($attributeList)
            ->setAttachmentId($attachmentId)
            ->setParams($params)
            ->run();

        $id = $result->id ?? null;

        if ($id) {
            $import = $this->entityManager->getEntity('Import', $id);

            if ($import) {
                $this->processActionHistoryRecord('create', $import);
            }
        }

        return $result;
    }

    public function importFileWithParamsId(string $contents, string $importParamsId) : StdClass
    {
        if (!$contents) {
            throw new Error("File contents is empty.");
        }

        $source = $this->getEntityManager()->getEntity('Import', $importParamsId);

        if (!$source) {
            throw new Error("Import {$importParamsId} not found.");
        }

        $entityType = $source->get('entityType');
        $attributeList = $source->get('attributeList') ?? [];

        $params = $source->get('params') ?? (object) [];
        $params = json_decode(json_encode($params), true);

        unset($params['idleMode']);
        unset($params['manualMode']);

        $attachmentId = $this->uploadFile($contents);

        return $this->import($entityType, $attributeList, $attachmentId, $params);
    }

    public function unmarkAsDuplicate(string $importId, string $entityType, string $entityId)
    {
        $e = $this->getEntityManager()
            ->getRepository('ImportEntity')
            ->where([
                'importId' => $importId,
                'entityType' => $entityType,
                'entityId' => $entityId,
            ])
            ->findOne();

        if (!$e) {
            throw new NotFound();
        }

        $e->set('isDuplicate', false);

        $this->getEntityManager()->saveEntity($e);
    }
}
