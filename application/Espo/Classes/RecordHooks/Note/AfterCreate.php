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

namespace Espo\Classes\RecordHooks\Note;

use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Note;
use Espo\Entities\Note as NoteEntity;
use Espo\Entities\Preferences;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Tools\Stream\Service;

/**
 * @implements SaveHook<Note>
 * @noinspection PhpUnused
 */
class AfterCreate implements SaveHook
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private Metadata $metadata,
        private Service $streamService
    ) {}

    public function process(Entity $entity): void
    {
        $parentType = $entity->getParentType();
        $parentId = $entity->getParentId();

        if (
            $entity->getType() !== NoteEntity::TYPE_POST ||
            !$parentType ||
            !$parentId
        ) {
            return;
        }

        if (!$this->metadata->get(['scopes', $parentType, 'stream'])) {
            return;
        }

        $preferences = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $this->user->getId());

        if (!$preferences) {
            return;
        }

        if (!$preferences->get('followEntityOnStreamPost')) {
            return;
        }

        $parent = $this->entityManager->getEntityById($parentType, $parentId);

        if (!$parent || $this->user->isSystem() || $this->user->isApi()) {
            return;
        }

        $this->streamService->followEntity($parent, $this->user->getId());
    }
}
