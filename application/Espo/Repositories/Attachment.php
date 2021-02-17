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

use Espo\Core\Exceptions\Error;

use Espo\ORM\Entity;

use Espo\Core\Utils\Util;

use Espo\Core\Di;

class Attachment extends \Espo\Core\Repositories\Database implements
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

    public function beforeSave(Entity $entity, array $options = [])
    {
        parent::beforeSave($entity, $options);

        $storage = $entity->get('storage');

        if (!$storage) {
            $entity->set('storage', $this->config->get('defaultFileStorage', null));
        }

        if ($entity->isNew()) {
            if (!$entity->has('size') && $entity->has('contents')) {
                $contents = $entity->get('contents');

                $entity->set(
                    'size',
                    mb_strlen($contents, '8bit')
                );
            }
        }
    }

    public function save(Entity $entity, array $options = [])
    {
        $isNew = $entity->isNew();

        if ($isNew) {
            $entity->id = Util::generateId();

            if ($entity->has('contents')) {
                $contents = $entity->get('contents');
                if (is_string($contents)) {
                    $this->fileStorageManager->putContents($entity, $contents);
                }
            }
        }

        $result = parent::save($entity, $options);

        return $result;
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        $duplicateCount = $this->where([
            'OR' => [
                [
                    'sourceId' => $entity->getSourceId()
                ],
                [
                    'id' => $entity->getSourceId()
                ]
            ],
        ])->count();

        if ($duplicateCount === 0) {
            $this->fileStorageManager->unlink($entity);

            if (in_array($entity->get('type'), $this->imageTypeList)) {
                $this->removeImageThumbs($entity);
            }
        }
    }

    public function removeImageThumbs($entity)
    {
        foreach ($this->imageThumbList as $suffix) {
            $filePath = "data/upload/thumbs/".$entity->getSourceId()."_{$suffix}";
            if ($this->fileManager->isFile($filePath)) {
                $this->fileManager->removeFile($filePath);
            }
        }
    }

    public function getCopiedAttachment(Entity $entity, $role = null)
    {
        $attachment = $this->get();

        $attachment->set(array(
            'sourceId' => $entity->getSourceId(),
            'name' => $entity->get('name'),
            'type' => $entity->get('type'),
            'size' => $entity->get('size'),
            'role' => $entity->get('role')
        ));

        if ($role) {
            $attachment->set('role', $role);
        }

        $this->save($attachment);

        return $attachment;
    }

    public function getContents(Entity $entity) : ?string
    {
        return $this->fileStorageManager->getContents($entity);
    }

    public function getFilePath(Entity $entity) : string
    {
        return $this->fileStorageManager->getLocalFilePath($entity);
    }

    public function hasDownloadUrl(Entity $entity) : bool
    {
        return $this->fileStorageManager->hasDownloadUrl($entity);
    }

    public function getDownloadUrl(Entity $entity) : string
    {
        return $this->fileStorageManager->getDownloadUrl($entity);
    }
}
