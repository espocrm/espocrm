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

namespace Espo\Tools\EmailFolder;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Entities\GroupEmailFolder;
use Espo\ORM\EntityManager;

class GroupFolderService
{
    private EntityManager $entityManager;
    private Acl $acl;

    public function __construct(EntityManager $entityManager, Acl $acl)
    {
        $this->entityManager = $entityManager;
        $this->acl = $acl;
    }

    /**
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function moveUp(string $id): void
    {
        /** @var ?GroupEmailFolder $entity */
        $entity = $this->entityManager->getEntityById(GroupEmailFolder::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden();
        }

        $currentIndex = $entity->getOrder();

        if (!is_int($currentIndex)) {
            throw new Error();
        }

        $previousEntity = $this->entityManager
            ->getRDBRepositoryByClass(GroupEmailFolder::class)
            ->where([
                'order<' => $currentIndex,
            ])
            ->order('order', true)
            ->findOne();

        if (!$previousEntity) {
            return;
        }

        $entity->set('order', $previousEntity->getOrder());

        $previousEntity->set('order', $currentIndex);

        $this->entityManager->saveEntity($entity);
        $this->entityManager->saveEntity($previousEntity);
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     */
    public function moveDown(string $id): void
    {
        /** @var ?GroupEmailFolder $entity */
        $entity = $this->entityManager->getEntityById(GroupEmailFolder::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new NotFound();
        }
        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden();
        }

        $currentIndex = $entity->getOrder();

        if (!is_int($currentIndex)) {
            throw new Error();
        }

        $nextEntity = $this->entityManager
            ->getRDBRepositoryByClass(GroupEmailFolder::class)
            ->where([
                'order>' => $currentIndex,
            ])
            ->order('order', false)
            ->findOne();

        if (!$nextEntity) {
            return;
        }

        $entity->set('order', $nextEntity->getOrder());

        $nextEntity->set('order', $currentIndex);

        $this->entityManager->saveEntity($entity);
        $this->entityManager->saveEntity($nextEntity);
    }
}
