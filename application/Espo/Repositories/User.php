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

namespace Espo\Repositories;

use Espo\Entities\Team;
use Espo\ORM\Entity;
use Espo\Core\Repositories\Database;
use Espo\ORM\Name\Attribute;
use Espo\Repositories\UserData as UserDataRepository;
use Espo\Entities\UserData;
use Espo\Entities\User as UserEntity;

/**
 * @extends Database<UserEntity>
 */
class User extends Database
{
    private const AUTHENTICATION_METHOD_HMAC = 'Hmac';

    /**
     * @param UserEntity $entity
     * @param array<string, mixed> $options
     * @return void
     */
    protected function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->has('type') && !$entity->getType()) {
            $entity->set('type', UserEntity::TYPE_REGULAR);
        }

        if ($entity->isApi()) {
            if ($entity->isAttributeChanged('userName')) {
                $entity->set('lastName', $entity->getUserName());
            }

            if ($entity->has('authMethod') && $entity->getAuthMethod() !== self::AUTHENTICATION_METHOD_HMAC) {
                $entity->clear('secretKey');
            }
        } else {
            if ($entity->isAttributeChanged('type')) {
                $entity->set('authMethod', null);
            }
        }

        parent::beforeSave($entity, $options);

        if ($entity->has('type') && !$entity->isPortal()) {
            $entity->set('portalRolesIds', []);
            $entity->set('portalRolesNames', (object) []);
            $entity->set('portalsIds', []);
            $entity->set('portalsNames', (object) []);
        }

        if ($entity->has('type') && $entity->isPortal()) {
            $entity->set('rolesIds', []);
            $entity->set('rolesNames', (object) []);
            $entity->set('teamsIds', []);
            $entity->set('teamsNames', (object) []);
            $entity->set('defaultTeamId', null);
            $entity->set('defaultTeamName', null);
        }
    }

    /**
     * @param array<string, mixed> $options
     * @return void
     */
    protected function afterSave(Entity $entity, array $options = [])
    {
        if ($this->entityManager->getLocker()->isLocked()) {
            $this->entityManager->getLocker()->commit();
        }

        parent::afterSave($entity, $options);
    }

    /**
     * @param array<string, mixed> $options
     * @return void
     */
    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        $userData = $this->getUserDataRepository()->getByUserId($entity->getId());

        if ($userData) {
            $this->entityManager->removeEntity($userData);
        }
    }

    /**
     * @param string[] $teamIds
     */
    public function checkBelongsToAnyOfTeams(string $userId, array $teamIds): bool
    {
        if ($teamIds === []) {
            return false;
        }

        return (bool) $this->entityManager
            ->getRDBRepository(Team::RELATIONSHIP_TEAM_USER)
            ->where([
                Attribute::DELETED => false,
                'userId' => $userId,
                'teamId' => $teamIds,
            ])
            ->findOne();
    }

    private function getUserDataRepository(): UserDataRepository
    {
        /** @var UserDataRepository */
        return $this->entityManager->getRepository(UserData::ENTITY_TYPE);
    }
}
