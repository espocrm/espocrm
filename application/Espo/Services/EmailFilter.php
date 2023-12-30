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

namespace Espo\Services;

use Espo\Entities\EmailAccount as EmailAccountEntity;
use Espo\Entities\EmailFilter as EmailFilterEntity;
use Espo\Entities\InboundEmail as InboundEmailEntity;
use Espo\Entities\User as UserEntity;
use Espo\ORM\Entity;

use Espo\Core\Exceptions\Forbidden;
use stdClass;

/**
 * @extends Record<EmailFilterEntity>
 */
class EmailFilter extends Record
{
    /**
     * @param EmailFilterEntity $entity
     * @throws Forbidden
     */
    protected function beforeCreateEntity(Entity $entity, $data)
    {
        parent::beforeCreateEntity($entity, $data);

        // Check if own.
        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden();
        }

        $this->controlEntityValues($entity);
    }

    /**
     * @param EmailFilterEntity $entity
     * @throws Forbidden
     */
    protected function beforeUpdateEntity(Entity $entity, $data)
    {
        parent::beforeUpdateEntity($entity, $data);

        $this->controlEntityValues($entity);
    }

    /**
     * @throws Forbidden
     */
    private function controlEntityValues(EmailFilterEntity $entity): void
    {
        if ($entity->isGlobal()) {
            $entity->set('parentId', null);
            $entity->set('parentType', null);

            if ($entity->getAction() !== EmailFilterEntity::ACTION_SKIP) {
                throw new Forbidden("Not allowed `action`.");
            }
        }

        if ($entity->getParentType() && !$entity->getParentId()) {
            throw new Forbidden("Not allowed `parentId` value.");
        }

        if (
            $entity->getParentType() === UserEntity::ENTITY_TYPE &&
            !in_array(
                $entity->getAction(),
                [
                    EmailFilterEntity::ACTION_NONE,
                    EmailFilterEntity::ACTION_SKIP,
                    EmailFilterEntity::ACTION_MOVE_TO_FOLDER,
                ]
            )
        ) {
            throw new Forbidden("Not allowed `action`.");
        }

        if (
            $entity->getParentType() === InboundEmailEntity::ENTITY_TYPE &&
            !in_array(
                $entity->getAction(),
                [
                    EmailFilterEntity::ACTION_SKIP,
                    EmailFilterEntity::ACTION_MOVE_TO_GROUP_FOLDER,
                ]
            )
        ) {
            throw new Forbidden("Not allowed `action`.");
        }

        if (
            $entity->getParentType() === EmailAccountEntity::ENTITY_TYPE &&
            $entity->getAction() !== EmailFilterEntity::ACTION_SKIP
        ) {
            throw new Forbidden("Not allowed `action`.");
        }

        if ($entity->getAction() !== EmailFilterEntity::ACTION_MOVE_TO_FOLDER) {
            $entity->set('emailFolderId', null);
        }

        if ($entity->getAction() !== EmailFilterEntity::ACTION_MOVE_TO_GROUP_FOLDER) {
            $entity->set('groupEmailFolderId', null);
        }
    }

    public function filterUpdateInput(stdClass $data): void
    {
        parent::filterUpdateInput($data);

        unset($data->isGlobal);
        unset($data->parentId);
        unset($data->parentType);
    }
}
