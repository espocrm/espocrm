<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

use Espo\Core\Portal\Acl\Events\PortalUserRoleUpdate;
use Espo\Core\Acl\Events\UserRoleUpdate;
use Espo\Core\Utils\Event\EventDispatcher;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\System\SystemState;
use Espo\Entities\Portal;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;

class Clearer
{
    public function __construct(
        private FileManager $fileManager,
        private EntityManager $entityManager,
        private SystemState $systemState,
        private EventDispatcher $eventDispatcher,
    ) {}

    public function clearForAllInternalUsers(): void
    {
        $this->fileManager->removeInDir('data/cache/application/acl');
        $this->fileManager->removeInDir('data/cache/application/aclMap');

        $this->bumpSystemStateVersionNumber();
    }

    public function clearForAllPortalUsers(): void
    {
        $this->fileManager->removeInDir('data/cache/application/aclPortal');
        $this->fileManager->removeInDir('data/cache/application/aclPortalMap');

        $this->bumpSystemStateVersionNumber();
    }

    public function clearForUser(User $user): void
    {
        if ($user->isPortal()) {
            $this->clearForPortalUser($user);

            return;
        }

        $file = basename($user->getId() . '.php');
        $dir = basename($user->getId());

        $this->fileManager->remove('data/cache/application/acl/' . $file);
        $this->fileManager->remove('data/cache/application/aclMap/' . $file);
        $this->fileManager->removeInDir('data/cache/application/acl/' . $dir, true);
        $this->fileManager->removeInDir('data/cache/application/aclMap/' . $dir, true);

        $this->eventDispatcher->dispatch(new UserRoleUpdate($user->getId()));
    }

    private function clearForPortalUser(User $user): void
    {
        $portals = $this->entityManager
            ->getRDBRepositoryByClass(Portal::class)
            ->select(Attribute::ID)
            ->find();

        foreach ($portals as $portal) {
            $part = basename($portal->getId()) . '/' . basename($user->getId() . '.php');

            $this->fileManager->remove('data/cache/application/aclPortal/' . $part);
            $this->fileManager->remove('data/cache/application/aclPortalMap/' . $part);

            $event = new PortalUserRoleUpdate(
                userId: $user->getId(),
                portalId: $portal->getId(),
            );

            $this->eventDispatcher->dispatch($event);
        }
    }

    private function bumpSystemStateVersionNumber(): void
    {
        $this->systemState->bumpVersionNumber();
    }
}
