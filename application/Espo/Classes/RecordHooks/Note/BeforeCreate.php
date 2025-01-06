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

use Espo\Core\Acl;
use Espo\Core\Acl\Table as AclTable;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Entities\Note;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Tools\Stream\NoteUtil;

/**
 * @implements SaveHook<Note>
 * @noinspection PhpUnused
 */
class BeforeCreate implements SaveHook
{
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private User $user,
        private NoteUtil $noteUtil
    ) {}

    public function process(Entity $entity): void
    {
        $this->checkParent($entity);

        if (!$entity->isPost() && !$this->user->isAdmin()) {
            throw new Forbidden("Only 'Post' type allowed.");
        }

        if ($this->user->isPortal()) {
            $entity->set('isInternal', false);
        }

        if ($entity->isPost()) {
            $this->noteUtil->handlePostText($entity);
        }

        $targetType = $entity->getTargetType();

        $entity->clear('isPinned');
        $entity->clear('isGlobal');

        switch ($targetType) {
            case Note::TARGET_ALL:

                $entity->clear('usersIds');
                $entity->clear('teamsIds');
                $entity->clear('portalsIds');
                $entity->set('isGlobal', true);

                break;

            case Note::TARGET_SELF:

                $entity->clear('usersIds');
                $entity->clear('teamsIds');
                $entity->clear('portalsIds');
                $entity->setUsersIds([$this->user->getId()]);
                $entity->set('isForSelf', true);

                break;

            case Note::TARGET_USERS:

                $entity->clear('teamsIds');
                $entity->clear('portalsIds');

                break;

            case Note::TARGET_TEAMS:

                $entity->clear('usersIds');
                $entity->clear('portalsIds');

                break;

            case Note::TARGET_PORTALS:

                $entity->clear('usersIds');
                $entity->clear('teamsIds');

                break;
        }
    }

    /**
     * @throws Forbidden
     */
    private function checkParent(Note $entity): void
    {
        if (!$entity->getParentType() || !$entity->getParentId()) {
            return;
        }

        $parent = $this->entityManager->getEntityById($entity->getParentType(), $entity->getParentId());

        if ($parent && $this->acl->check($parent, AclTable::ACTION_READ)) {
            return;
        }

        throw new Forbidden("No access to parent.");
    }
}
