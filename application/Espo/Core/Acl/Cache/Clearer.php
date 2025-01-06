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

namespace Espo\Core\Acl\Cache;

use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Entities\Portal;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;

/**
 * @todo Clear cache in AclManager.
 */
class Clearer
{
    public function __construct(private FileManager $fileManager, private EntityManager $entityManager)
    {}

    public function clearForAllInternalUsers(): void
    {
        $this->fileManager->removeInDir('data/cache/application/acl');
        $this->fileManager->removeInDir('data/cache/application/aclMap');
    }

    public function clearForAllPortalUsers(): void
    {
        $this->fileManager->removeInDir('data/cache/application/aclPortal');
        $this->fileManager->removeInDir('data/cache/application/aclPortalMap');
    }

    public function clearForUser(User $user): void
    {
        if ($user->isPortal()) {
            $this->clearForPortalUser($user);

            return;
        }

        $part = $user->getId() . '.php';

        $this->fileManager->remove('data/cache/application/acl/' . $part);
        $this->fileManager->remove('data/cache/application/aclMap/' . $part);
    }

    private function clearForPortalUser(User $user): void
    {
        $portals = $this->entityManager
            ->getRDBRepositoryByClass(Portal::class)
            ->select(Attribute::ID)
            ->find();

        foreach ($portals as $portal) {
            $part = $portal->getId() . '/' . $user->getId() . '.php';

            $this->fileManager->remove('data/cache/application/aclPortal/' . $part);
            $this->fileManager->remove('data/cache/application/aclPortalMap/' . $part);
        }
    }
}
