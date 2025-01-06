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

namespace Espo\Repositories;

use Espo\ORM\Entity;
use Espo\Entities\Attachment as AttachmentEntity;
use Espo\Core\Repositories\Database;
use Espo\Core\FileStorage\Storages\EspoUploadDir;
use Espo\Core\Di;

use Psr\Http\Message\StreamInterface;

/**
 * @extends Database<AttachmentEntity>
 */
class Attachment extends Database implements
    Di\FileStorageManagerAware,
    Di\ConfigAware
{
    use Di\FileStorageManagerSetter;
    use Di\ConfigSetter;

    /**
     * @param AttachmentEntity $entity
     */
    protected function beforeSave(Entity $entity, array $options = [])
    {
        parent::beforeSave($entity, $options);

        if ($entity->isNew()) {
            $this->processBeforeSaveNew($entity);
        }
    }

    protected function processBeforeSaveNew(AttachmentEntity $entity): void
    {
        if ($entity->isBeingUploaded()) {
            $entity->setStorage(EspoUploadDir::NAME);
        }

        if (!$entity->getStorage()) {
            $defaultStorage = $this->config->get('defaultFileStorage');

            $entity->setStorage($defaultStorage);
        }

        $contents = $entity->get('contents');

        if (is_null($contents)) {
            return;
        }

        if (!$entity->isBeingUploaded()) {
            $entity->setSize(strlen($contents));
        }

        $this->fileStorageManager->putContents($entity, $contents);
    }

    /**
     * Copy an attachment record (to reuse the same file w/o copying it in the storage).
     */
    public function getCopiedAttachment(AttachmentEntity $entity, ?string $role = null): AttachmentEntity
    {
        $new = $this->getNew();

        $new
            ->setSourceId($entity->getSourceId())
            ->setName($entity->getName())
            ->setType($entity->getType())
            ->setSize($entity->getSize())
            ->setRole($entity->getRole());

        if ($role) {
            $new->setRole($role);
        }

        $this->save($new);

        return $new;
    }

    public function getContents(AttachmentEntity $entity): string
    {
        return $this->fileStorageManager->getContents($entity);
    }

    public function getStream(AttachmentEntity $entity): StreamInterface
    {
        return $this->fileStorageManager->getStream($entity);
    }

    /**
     * A size in bytes.
     */
    public function getSize(AttachmentEntity $entity): int
    {
        return $this->fileStorageManager->getSize($entity);
    }

    public function getFilePath(AttachmentEntity $entity): string
    {
        return $this->fileStorageManager->getLocalFilePath($entity);
    }
}
