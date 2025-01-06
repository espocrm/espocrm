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

namespace Espo\Classes\RecordHooks\EmailFilter;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Entities\EmailAccount as EmailAccountEntity;
use Espo\Entities\EmailFilter;
use Espo\Entities\InboundEmail as InboundEmailEntity;
use Espo\Entities\User as UserEntity;
use Espo\ORM\Entity;

/**
 * @implements SaveHook<EmailFilter>
 */
class BeforeSave implements SaveHook
{
    public function __construct(
        private Acl $acl
    ) {}

    /**
     * @inheritDoc
     */
    public function process(Entity $entity): void
    {
        // Check if own.
        if ($entity->isNew() && !$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden();
        }

        $this->controlEntityValues($entity);
    }

    /**
     * @throws Forbidden
     */
    private function controlEntityValues(EmailFilter $entity): void
    {
        if ($entity->isGlobal()) {
            $entity->setMultiple([
                'parentType' => null,
                'parentId' => null,
            ]);

            if ($entity->getAction() !== EmailFilter::ACTION_SKIP) {
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
                    EmailFilter::ACTION_NONE,
                    EmailFilter::ACTION_SKIP,
                    EmailFilter::ACTION_MOVE_TO_FOLDER,
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
                    EmailFilter::ACTION_SKIP,
                    EmailFilter::ACTION_MOVE_TO_GROUP_FOLDER,
                ]
            )
        ) {
            throw new Forbidden("Not allowed `action`.");
        }

        if (
            $entity->getParentType() === EmailAccountEntity::ENTITY_TYPE &&
            $entity->getAction() !== EmailFilter::ACTION_SKIP
        ) {
            throw new Forbidden("Not allowed `action`.");
        }

        if ($entity->getAction() !== EmailFilter::ACTION_MOVE_TO_FOLDER) {
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $entity->set('emailFolderId', null);
        }

        if ($entity->getAction() !== EmailFilter::ACTION_MOVE_TO_GROUP_FOLDER) {
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $entity->set('groupEmailFolderId', null);
        }
    }
}
