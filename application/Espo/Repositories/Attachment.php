<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Utils\Util;

class Attachment extends \Espo\Core\ORM\Repositories\RDB
{
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

    protected function init()
    {
        parent::init();
        $this->addDependency('container');
        $this->addDependency('config');
    }

    protected function getFileManager()
    {
        return $this->getInjection('container')->get('fileManager');
    }

    protected function getFileStorageManager()
    {
        return $this->getInjection('container')->get('fileStorageManager');
    }

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    public function beforeSave(Entity $entity, array $options = array())
    {
        parent::beforeSave($entity, $options);

        $storage = $entity->get('storage');
        if (!$storage) {
            $entity->set('storage', $this->getConfig()->get('defaultFileStorage', null));
        }

        if ($entity->isNew()) {
            if (!$entity->has('size') && $entity->has('contents')) {
                $entity->set('size', mb_strlen($entity->get('contents')));
            }
        }
    }

    public function save(Entity $entity, array $options = array())
    {
        $isNew = $entity->isNew();

        if ($isNew) {
            $entity->id = Util::generateId();

            if (!empty($entity->id) && $entity->has('contents')) {
                $contents = $entity->get('contents');
                $storeResult = $this->getFileStorageManager()->putContents($entity, $contents);
                if ($storeResult === false) {
                    throw new \Espo\Core\Exceptions\Error("Could not store the file");
                }
            }
        }

        $result = parent::save($entity, $options);

        return $result;
    }

    protected function afterRemove(Entity $entity, array $options = array())
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
            $this->getFileStorageManager()->unlink($entity);

            if (in_array($entity->get('type'), $this->imageTypeList)) {
                $this->removeImageThumbs($entity);
            }
        }
    }

    public function removeImageThumbs($entity)
    {
        foreach ($this->imageThumbList as $suffix) {
            $filePath = "data/upload/thumbs/".$entity->getSourceId()."_{$suffix}";
            if ($this->getFileManager()->isFile($filePath)) {
                $this->getFileManager()->removeFile($filePath);
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

    public function getContents(Entity $entity)
    {
        return $this->getFileStorageManager()->getContents($entity);
    }

    public function getFilePath(Entity $entity)
    {
        return $this->getFileStorageManager()->getLocalFilePath($entity);
    }

    public function hasDownloadUrl(Entity $entity)
    {
        return $this->getFileStorageManager()->hasDownloadUrl($entity);
    }

    public function getDownloadUrl(Entity $entity)
    {
        return $this->getFileStorageManager()->getDownloadUrl($entity);
    }
}
