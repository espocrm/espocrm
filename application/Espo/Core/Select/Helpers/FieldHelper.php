<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Select\Helpers;

use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\ORM\BaseEntity;

/**
 * @todo Rewrite using EntityDefs class. Then write unit tests.
 */
class FieldHelper
{
    private ?Entity $seed = null;

    public function __construct(private string $entityType, private EntityManager $entityManager)
    {}

    private function getSeed(): Entity
    {
        return $this->seed ?? $this->entityManager->getNewEntity($this->entityType);
    }

    public function hasAssignedUsersField(): bool
    {
        if (
            $this->getSeed()->hasRelation('assignedUsers') &&
            $this->getSeed()->hasAttribute('assignedUsersIds')
        ) {
            return true;
        }

        return false;
    }

    public function hasAssignedUserField(): bool
    {
        if ($this->getSeed()->hasAttribute('assignedUserId')) {
            return true;
        }

        return false;
    }

    public function hasCreatedByField(): bool
    {
        if ($this->getSeed()->hasAttribute('createdById')) {
            return true;
        }

        return false;
    }

    public function hasTeamsField(): bool
    {
        if (
            $this->getSeed()->hasRelation('teams') &&
            $this->getSeed()->hasAttribute('teamsIds')
        ) {
            return true;
        }

        return false;
    }

    public function hasContactField(): bool
    {
        return
            $this->getSeed()->hasAttribute('contactId') &&
            $this->getRelationParam($this->getSeed(), 'contact', 'entity') === 'Contact';
    }

    public function hasContactsRelation(): bool
    {
        return
            $this->getSeed()->hasRelation('contacts') &&
            $this->getRelationParam($this->getSeed(), 'contacts', 'entity') === 'Contact';
    }

    public function hasParentField(): bool
    {
        return
            $this->getSeed()->hasAttribute('parentId') &&
            $this->getSeed()->hasRelation('parent');
    }

    public function hasAccountField(): bool
    {
        return
            $this->getSeed()->hasAttribute('accountId') &&
            $this->getRelationParam($this->getSeed(), 'account', 'entity') === 'Account';
    }

    public function hasAccountsRelation(): bool
    {
        return
            $this->getSeed()->hasRelation('accounts') &&
            $this->getRelationParam($this->getSeed(), 'accounts', 'entity') === 'Account';
    }

    /**
     * @return mixed
     */
    private function getRelationParam(Entity $entity, string $relation, string $param)
    {
        if ($entity instanceof BaseEntity) {
            return $entity->getRelationParam($relation, $param);
        }

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (!$entityDefs->hasRelation($relation)) {
            return null;
        }

        return $entityDefs->getRelation($relation)->getParam($param);
    }
}
