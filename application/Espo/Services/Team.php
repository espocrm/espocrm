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

namespace Espo\Services;

use \Espo\ORM\Entity;

class Team extends Record
{
    protected function init()
    {
        $this->addDependency('fileManager');
    }

    protected $linkSelectParams = array(
        'users' => array(
            'additionalColumns' => array(
                'role' => 'teamRole'
            )
        )
    );

    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
    }

    public function afterUpdateEntity(Entity $entity, $data)
    {
        parent::afterUpdateEntity($entity, $data);
        if (property_exists($data, 'rolesIds')) {
            $this->clearRolesCache();
        }
    }

    protected function clearRolesCache()
    {
        $this->getFileManager()->removeInDir('data/cache/application/acl');
    }

    public function link($id, $link, $foreignId)
    {
        $result = parent::link($id, $link, $foreignId);

        if ($link === 'users') {
            $this->getFileManager()->removeFile('data/cache/application/acl/' . $foreignId . '.php');
        }

        return $result;
    }

    public function unlink($id, $link, $foreignId)
    {
        $result = parent::unlink($id, $link, $foreignId);

        if ($link === 'users') {
            $this->getFileManager()->removeFile('data/cache/application/acl/' . $foreignId . '.php');
        }

        return $result;
    }

    public function massLink($id, $link, $where, $selectData = null)
    {
        $result = parent::massLink($id, $link, $where, $selectData);

        if ($link === 'users') {
            $this->getFileManager()->removeInDir('data/cache/application/acl');
        }

        return $result;
    }
}
