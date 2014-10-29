<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
namespace Espo\Repositories;

use Espo\Core\ORM\Repositories\RDB;
use Espo\Core\Utils\File\Manager;
use Espo\ORM\Entity;

class Attachment extends
    RDB
{

    protected $dependencies = array(
        'fileManager',
    );

    public function save(Entity $entity)
    {
        $isNew = $entity->isNew();
        $result = parent::save($entity);
        if ($isNew) {
            if (!empty($entity->id) && $entity->has('contents')) {
                $contents = $entity->get('contents');
                $this->getFileManager()->putContents('data/upload/' . $entity->id, $contents);
            }
        }
        return $result;
    }

    /**
     * @return Manager
     * @since 1.0
     */
    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
    }

    protected function afterRemove(Entity $entity)
    {
        parent::afterRemove($entity);
        $this->getFileManager()->removeFile('data/upload/' . $entity->id);
    }
}

