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

namespace Espo\Tools\Import;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Forbidden;

use Espo\Core\Record\ServiceContainer;
use Espo\Core\Acl;
use Espo\Core\Acl\Table;

use Espo\ORM\EntityManager;

use Espo\Entities\Import as ImportEntity;
use Espo\Entities\Attachment;

use DateTime;

class Service
{
    private const REVERT_PERMANENTLY_REMOVE_PERIOD_DAYS = 2;

    private $factory;

    private $recordServiceContainer;

    private $entityManager;

    private $acl;

    public function __construct(
        ImportFactory $factory,
        ServiceContainer $recordServiceContainer,
        EntityManager $entityManager,
        Acl $acl
    ) {
        $this->factory = $factory;
        $this->recordServiceContainer = $recordServiceContainer;
        $this->entityManager = $entityManager;
        $this->acl = $acl;
    }

    public function import(
        string $entityType,
        array $attributeList,
        string $attachmentId,
        Params $params
    ): Result {

        if (!$this->acl->check($entityType, Table::ACTION_CREATE)) {
            throw new Forbidden("No create access for '{$entityType}'.");
        }

        $result = $this->factory
            ->create()
            ->setEntityType($entityType)
            ->setAttributeList($attributeList)
            ->setAttachmentId($attachmentId)
            ->setParams($params)
            ->run();

        $id = $result->getId();

        if ($id) {
            $import = $this->entityManager->getEntity(ImportEntity::ENTITY_TYPE, $id);

            if ($import) {
                $this->recordServiceContainer
                    ->get(ImportEntity::ENTITY_TYPE)
                    ->processActionHistoryRecord('create', $import);
            }
        }

        return $result;
    }

    public function importContentsWithParamsId(string $contents, string $importParamsId): Result
    {
        if (!$contents) {
            throw new Error("Contents is empty.");
        }

        /** @var ?ImportEntity $source */
        $source = $this->entityManager->getEntity(ImportEntity::ENTITY_TYPE, $importParamsId);

        if (!$source) {
            throw new Error("Import '{$importParamsId}' not found.");
        }

        $entityType = $source->getTargetEntityType();
        $attributeList = $source->getTargetAttributeList() ?? [];

        $params = Params::fromRaw($source->getParams())
            ->withIdleMode(false)
            ->withManualMode(false);

        $attachmentId = $this->uploadFile($contents);

        return $this->import($entityType, $attributeList, $attachmentId, $params);
    }

    public function importById(string $id, bool $startFromLastIndex = false, bool $forceResume = false): Result
    {
        /** @var ?ImportEntity $import */
        $import = $this->entityManager->getEntity(ImportEntity::ENTITY_TYPE, $id);

        if (!$import) {
            throw new NotFound("Import '{$id}' not found.");
        }

        $status = $import->getStatus();

        if ($status !== ImportEntity::STATUS_STANDBY) {
            if (!in_array($status, [ImportEntity::STATUS_IN_PROCESS, ImportEntity::STATUS_FAILED])) {
                throw new Forbidden("Can't run import with '{$status}' status.");
            }

            if (!$forceResume) {
                throw new Forbidden("Import has '{$status}' status. Use -r flag to force resume.");
            }
        }

        $entityType = $import->getTargetEntityType();
        $attributeList = $import->getTargetAttributeList() ?? [];

        $params = Params::fromRaw($import->getParams())
            ->withStartFromLastIndex($startFromLastIndex);

        $attachmentId = $import->getFileId();

        return $this->factory
            ->create()
            ->setEntityType($entityType)
            ->setAttributeList($attributeList)
            ->setAttachmentId($attachmentId)
            ->setParams($params)
            ->setId($id)
            ->run();
    }

    public function revert(string $id): void
    {
        $import = $this->entityManager->getEntity('Import', $id);

        if (!$import) {
            throw new NotFound("Could not find import record.");
        }

        if (!$this->acl->checkEntityDelete($import)) {
            throw new Forbidden("No access import record.");
        }

        $importEntityList = $this->entityManager
            ->getRDBRepository('ImportEntity')
            ->sth()
            ->where([
                'importId' => $import->getId(),
                'isImported' => true,
            ])
            ->find();

        $removeFromDb = false;

        $createdAt = $import->get('createdAt');

        if ($createdAt) {
            $dtNow = new DateTime();
            $createdAtDt = new DateTime($createdAt);

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

            $entity = $this->entityManager
                ->getRDBRepository($entityType)
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
                $this->entityManager
                    ->getRDBRepository($entityType)
                    ->deleteFromDb($entityId);
            }
        }

        $this->entityManager->removeEntity($import);

        $this->recordServiceContainer
            ->get(ImportEntity::ENTITY_TYPE)
            ->processActionHistoryRecord('delete', $import);
    }

    /**
     * @return string Attachment ID.
     */
    public function uploadFile(string $contents): string
    {
        $attachment = $this->entityManager->getEntity(Attachment::ENTITY_TYPE);

        $attachment->set('type', 'text/csv');
        $attachment->set('role', 'Import File');
        $attachment->set('name', 'import-file.csv');
        $attachment->set('contents', $contents);

        $this->entityManager->saveEntity($attachment);

        return $attachment->getId();
    }

    public function removeDuplicates(string $id): void
    {
        $import = $this->entityManager->getEntity(ImportEntity::ENTITY_TYPE, $id);

        if (!$import) {
            throw new NotFound("Import '{$id}' not found.");
        }

        if (!$this->acl->checkEntityDelete($import)) {
            throw new Forbidden("No delete access.");
        }

        $importEntityList = $this->entityManager
            ->getRDBRepository('ImportEntity')
            ->sth()
            ->where([
                'importId' => $import->getId(),
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

            $entity = $this->entityManager
                ->getRDBRepository($entityType)
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

            $this->entityManager
                ->getRDBRepository($entityType)
                ->deleteFromDb($entityId);
        }
    }

    public function unmarkAsDuplicate(string $importId, string $entityType, string $entityId): void
    {
        $entity = $this->entityManager
            ->getRDBRepository('ImportEntity')
            ->where([
                'importId' => $importId,
                'entityType' => $entityType,
                'entityId' => $entityId,
            ])
            ->findOne();

        if (!$entity) {
            throw new NotFound();
        }

        $entity->set('isDuplicate', false);

        $this->entityManager->saveEntity($entity);
    }
}
