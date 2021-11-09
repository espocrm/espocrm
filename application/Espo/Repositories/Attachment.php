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

namespace Espo\Repositories;

use Espo\ORM\Entity;
use Espo\Entities\Attachment as AttachmentEntity;
use Espo\Core\Repositories\Database;

use Espo\Core\Di;

use Psr\Http\Message\StreamInterface;

/**
 * @extends Database<AttachmentEntity>
 */
class Attachment extends Database implements
    Di\FileManagerAware,
    Di\FileStorageManagerAware,
    Di\ConfigAware
{
    use Di\FileManagerSetter;
    use Di\FileStorageManagerSetter;
    use Di\ConfigSetter;

    protected $imageTypeList = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    protected $imageThumbList = [
        'xxx-small',
        'xx-small',
        'x-small',
        'small',
        'medium',
        'large',
        'x-large',
        'xx-large',
    ];

    protected function beforeSave(Entity $entity, array $options = [])
    {
        parent::beforeSave($entity, $options);

        if ($entity->isNew()) {
            $this->processBeforeSaveNew($entity);
        }
    }

    protected function processBeforeSaveNew(AttachmentEntity $entity): void
    {
        if (!$entity->get('storage')) {
            $defaultStorage = $this->config->get('defaultFileStorage');

            $entity->set('storage', $defaultStorage);
        }

        $contents = $entity->get('contents');

        if (is_null($contents)) {
            return;
        }

        $entity->set('size', strlen($contents));

        $this->fileStorageManager->putContents($entity, $contents);
    }

    /**
     * @param AttachmentEntity $entity
     */
    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        $duplicateCount = $this
            ->where([
                'OR' => [
                    [
                        'sourceId' => $entity->getSourceId()
                    ],
                    [
                        'id' => $entity->getSourceId()
                    ]
                ],
            ])
            ->count();

        if ($duplicateCount === 0) {
            $this->fileStorageManager->unlink($entity);

            if (in_array($entity->get('type'), $this->imageTypeList)) {
                $this->removeImageThumbs($entity);
            }
        }
    }

    public function removeImageThumbs(AttachmentEntity $entity)
    {
        foreach ($this->imageThumbList as $suffix) {
            $filePath = "data/upload/thumbs/" . $entity->getSourceId() . "_{$suffix}";

            if ($this->fileManager->isFile($filePath)) {
                $this->fileManager->removeFile($filePath);
            }
        }
    }

    public function getCopiedAttachment(AttachmentEntity $entity, $role = null): AttachmentEntity
    {
        $attachment = $this->get();

        $attachment->set([
            'sourceId' => $entity->getSourceId(),
            'name' => $entity->get('name'),
            'type' => $entity->get('type'),
            'size' => $entity->get('size'),
            'role' => $entity->get('role'),
        ]);

        if ($role) {
            $attachment->set('role', $role);
        }

        $this->save($attachment);

        return $attachment;
    }

    public function getContents(AttachmentEntity $entity): string
    {
        return $this->fileStorageManager->getContents($entity);
    }

    public function getStream(AttachmentEntity $entity): StreamInterface
    {
        return $this->fileStorageManager->getStream($entity);
    }

    public function getSize(AttachmentEntity $entity): int
    {
        return $this->fileStorageManager->getSize($entity);
    }

    public function getFilePath(AttachmentEntity $entity): string
    {
        return $this->fileStorageManager->getLocalFilePath($entity);
    }
}
