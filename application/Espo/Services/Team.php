<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

    public function afterUpdate(Entity $entity, array $data = array())
    {
        parent::afterUpdate($entity, $data);
        if (array_key_exists('rolesIds', $data)) {
            $this->clearRolesCache();
        }
    }

    protected function clearRolesCache($id)
    {
        $this->getFileManager()->removeInDir('data/cache/application/acl');
    }

    public function linkEntity($id, $link, $foreignId)
    {
        $result = parent::linkEntity($id, $link, $foreignId);

        if ($link === 'users') {
            $this->getFileManager()->removeFile('data/cache/application/acl/' . $foreignId . '.php');
        }

        return $result;
    }

    public function unlinkEntity($id, $link, $foreignId)
    {
        $result = parent::unlinkEntity($id, $link, $foreignId);

        if ($link === 'users') {
            $this->getFileManager()->removeFile('data/cache/application/acl/' . $foreignId . '.php');
        }

        return $result;
    }

    public function linkEntityMass($id, $link, $where, $selectData = null)
    {
        $result = parent::linkEntityMass($id, $link, $where, $selectData);

        if ($link === 'users') {
            $this->getFileManager()->removeInDir('data/cache/application/acl');
        }

        return $result;
    }
}

