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

use Espo\ORM\Entity;

use Espo\Core\Select\SearchParams;

use Espo\Core\Di;

class Team extends Record implements

    Di\FileManagerAware,
    Di\DataManagerAware
{
    use Di\FileManagerSetter;
    use Di\DataManagerSetter;

    public function afterUpdateEntity(Entity $entity, $data)
    {
        parent::afterUpdateEntity($entity, $data);

        if (property_exists($data, 'rolesIds')) {
            $this->clearRolesCache();
        }
    }

    protected function clearRolesCache()
    {
        $this->fileManager->removeInDir('data/cache/application/acl');
        $this->fileManager->removeInDir('data/cache/application/aclMap');

        $this->dataManager->updateCacheTimestamp();
    }

    public function link(string $id, string $link, string $foreignId): void
    {
        parent::link($id, $link, $foreignId);

        if ($link === 'users') {
            $this->fileManager->removeFile('data/cache/application/acl/' . $foreignId . '.php');
            $this->fileManager->removeFile('data/cache/application/aclMap/' . $foreignId . '.php');

            $this->dataManager->updateCacheTimestamp();
        }
    }

    public function unlink(string $id, string $link, string $foreignId): void
    {
        parent::unlink($id, $link, $foreignId);

        if ($link === 'users') {
            $this->fileManager->removeFile('data/cache/application/acl/' . $foreignId . '.php');
            $this->fileManager->removeFile('data/cache/application/aclMap/' . $foreignId . '.php');

            $this->dataManager->updateCacheTimestamp();
        }
    }

    public function massLink(string $id, string $link, SearchParams $searchParams): bool
    {
        $result = parent::massLink($id, $link, $searchParams);

        if ($link === 'users') {
            $this->clearRolesCache();
        }

        return $result;
    }
}
