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

namespace Espo\Classes\AclPortal\Attachment;

use Espo\Entities\Attachment;
use Espo\Entities\Note;
use Espo\Entities\Settings;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\Core\Acl\AccessEntityCREDChecker;
use Espo\Core\Acl\ScopeData;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Portal\Acl\DefaultAccessChecker;
use Espo\Core\Portal\Acl\Traits\DefaultAccessCheckerDependency;
use Espo\Core\Portal\AclManager;

/**
 * @implements AccessEntityCREDChecker<Attachment>
 */
class AccessChecker implements AccessEntityCREDChecker
{
    use DefaultAccessCheckerDependency;

    private DefaultAccessChecker $defaultAccessChecker;
    private AclManager $aclManager;
    private EntityManager $entityManager;

    public function __construct(
        DefaultAccessChecker $defaultAccessChecker,
        AclManager $aclManager,
        EntityManager $entityManager
    ) {
        $this->defaultAccessChecker = $defaultAccessChecker;
        $this->aclManager = $aclManager;
        $this->entityManager = $entityManager;
    }

    public function checkEntityRead(User $user, Entity $entity, ScopeData $data): bool
    {
        /** @var Attachment $entity */

        if ($entity->get('parentType') === Settings::ENTITY_TYPE) {
            // Allow the logo.
            return true;
        }

        $parent = null;

        $parentType = $entity->get('parentType');
        $parentId = $entity->get('parentId');

        $relatedType = $entity->get('relatedType');
        $relatedId = $entity->get('relatedId');

        if ($parentId && $parentType) {
            $parent = $this->entityManager->getEntityById($parentType, $parentId);
        } else if ($relatedId && $relatedType) {
            $parent = $this->entityManager->getEntityById($relatedType, $relatedId);
        }

        if (!$parent) {
            if ($entity->get('createdById') === $user->getId()) {
                return true;
            }

            return false;
        }

        if ($parent->getEntityType() === Note::ENTITY_TYPE) {
            /** @var Note $parent */
            $result = $this->checkEntityReadNoteParent($user, $parent);

            if ($result !== null) {
                return $result;
            }
        } else if ($this->aclManager->checkEntity($user, $parent)) {
            if (
                $entity->getTargetField() &&
                !$this->aclManager->checkField($user, $parent->getEntityType(), $entity->getTargetField())
            ) {
                return false;
            }

            return true;
        }

        if ($this->defaultAccessChecker->checkEntityRead($user, $entity, $data)) {
            return true;
        }

        return false;
    }

    private function checkEntityReadNoteParent(User $user, Note $note): ?bool
    {
        if ($note->isInternal()) {
            return false;
        }

        if ($note->getTargetType() === Note::TARGET_PORTALS) {
            $intersect = array_intersect(
                $note->getLinkMultipleIdList('portals'),
                $user->getLinkMultipleIdList('portals')
            );

            if (count($intersect)) {
                return true;
            }

            return false;
        }

        if ($note->getTargetType() === Note::TARGET_USERS) {
            $isRelated = $this->entityManager
                ->getRDBRepository(Note::ENTITY_TYPE)
                ->getRelation($note, 'users')
                ->isRelated($user);

            if ($isRelated) {
                return true;
            }

            return false;
        }

        if (!$note->getParentId() || !$note->getParentType()) {
            return null;
        }

        $parent = $this->entityManager->getEntityById($note->getParentType(), $note->getParentId());

        if ($parent && $this->aclManager->checkEntity($user, $parent)) {
            return true;
        }

        return null;
    }
}
