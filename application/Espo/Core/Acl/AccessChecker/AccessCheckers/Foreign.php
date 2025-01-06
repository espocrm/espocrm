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

namespace Espo\Core\Acl\AccessChecker\AccessCheckers;

use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\Core\Utils\Metadata;
use Espo\Core\Acl\DefaultAccessChecker;
use Espo\Core\Acl\Traits\DefaultAccessCheckerDependency;
use Espo\Core\Acl\AccessEntityCreateChecker;
use Espo\Core\Acl\AccessEntityReadChecker;
use Espo\Core\Acl\AccessEntityEditChecker;
use Espo\Core\Acl\AccessEntityDeleteChecker;
use Espo\Core\Acl\AccessEntityStreamChecker;
use Espo\Core\Acl\ScopeData;

use Espo\ORM\EntityManager;

use LogicException;

/**
 * Access is determined by access to a foreign entity.
 *
 * @implements AccessEntityCreateChecker<Entity>
 * @implements AccessEntityReadChecker<Entity>
 * @implements AccessEntityEditChecker<Entity>
 * @implements AccessEntityDeleteChecker<Entity>
 * @implements AccessEntityStreamChecker<Entity>
 */
class Foreign implements

    AccessEntityCreateChecker,
    AccessEntityReadChecker,
    AccessEntityEditChecker,
    AccessEntityDeleteChecker,
    AccessEntityStreamChecker
{
    use DefaultAccessCheckerDependency;

    public function __construct(
        private Metadata $metadata,
        DefaultAccessChecker $defaultAccessChecker,
        private EntityManager $entityManager
    ) {
        $this->defaultAccessChecker = $defaultAccessChecker;
    }

    private function getForeignEntity(Entity $entity): ?Entity
    {
        $entityType = $entity->getEntityType();

        $link = $this->metadata->get(['aclDefs', $entityType, 'link']);

        if (!$link) {
            throw new LogicException("No `link` in aclDefs for {$entityType}.");
        }

        if ($entity->isNew()) {
            $foreignEntityType = $this->entityManager
                ->getDefs()
                ->getEntity($entityType)
                ->getRelation($link)
                ->getForeignEntityType();

            /** @var ?string $id */
            $id = $entity->get($link . 'Id');

            if (!$id) {
                return null;
            }

            return $this->entityManager->getEntityById($foreignEntityType, $id);
        }

        return $this->entityManager
            ->getRDBRepository($entityType)
            ->getRelation($entity, $link)
            ->findOne();
    }

    public function checkEntityCreate(User $user, Entity $entity, ScopeData $data): bool
    {
        $foreign = $this->getForeignEntity($entity);

        if (!$foreign) {
            return false;
        }

        // @todo Check parent 'edit' access.

        return $this->defaultAccessChecker->checkEntityCreate($user, $foreign, $data);
    }

    public function checkEntityRead(User $user, Entity $entity, ScopeData $data): bool
    {
        $foreign = $this->getForeignEntity($entity);

        if (!$foreign) {
            return false;
        }

        return $this->defaultAccessChecker->checkEntityRead($user, $foreign, $data);
    }

    public function checkEntityEdit(User $user, Entity $entity, ScopeData $data): bool
    {
        $foreign = $this->getForeignEntity($entity);

        if (!$foreign) {
            return false;
        }

        return $this->defaultAccessChecker->checkEntityEdit($user, $foreign, $data);
    }

    public function checkEntityDelete(User $user, Entity $entity, ScopeData $data): bool
    {
        $foreign = $this->getForeignEntity($entity);

        if (!$foreign) {
            if ($user->isAdmin()) {
                return true;
            }

            return false;
        }

        return $this->defaultAccessChecker->checkEntityDelete($user, $foreign, $data);
    }

    public function checkEntityStream(User $user, Entity $entity, ScopeData $data): bool
    {
        $foreign = $this->getForeignEntity($entity);

        if (!$foreign) {
            return false;
        }

        return $this->defaultAccessChecker->checkEntityStream($user, $foreign, $data);
    }
}
