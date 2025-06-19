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

namespace Espo\Tools\CategoryTree;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Templates\Entities\CategoryTree;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\UpdateBuilder;
use Espo\ORM\Repository\Option\SaveOption;
use Espo\Tools\CategoryTree\Move\LoopReferenceChecker;
use Espo\Tools\CategoryTree\Move\MoveParams;

class MoveService
{
    private const ATTR_PARENT_ID = 'parentId';
    private const ATTR_ORDER = 'order';

    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private LoopReferenceChecker $loopReferenceChecker,
    ) {}

    /**
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     */
    public function move(CategoryTree $entity, MoveParams $params): void
    {
        $hasOrder = $entity->hasAttribute(self::ATTR_ORDER);

        if (!$hasOrder && $params->type !== MoveParams::TYPE_INTO) {
            throw new Error("Order not supported.");
        }

        $entityType = $entity->getEntityType();

        $reference = null;

        if ($params->referenceId) {
            $reference = $this->entityManager->getEntityById($entityType, $params->referenceId);

            if (!$reference) {
                throw new NotFound("No reference record found.");
            }
        }

        if ($params->type === MoveParams::TYPE_INTO) {
            $this->processInto($reference, $entity, $params);

            return;
        }

        if (!$reference) {
            throw new Error("No reference.");
        }

        $parentId = $reference->get(self::ATTR_PARENT_ID);

        if ($parentId !== $entity->get(self::ATTR_PARENT_ID) && $parentId) {
            $parent = $this->entityManager->getEntityById($entityType, $parentId);

            if ($parent && !$this->acl->checkEntityEdit($parent)) {
                throw new Forbidden("No edit access to target category.");
            }

            if ($parent) {
                $this->checkReferenceNoLoop($parent, $entity);
            }
        }

        if ($params->type === MoveParams::TYPE_AFTER) {
            $this->processAfter($reference, $entity);

            return;
        }

        $this->processBefore($reference, $entity);
    }

    private function incrementAfter(Entity $reference): void
    {
        $update = UpdateBuilder::create()
            ->in($reference->getEntityType())
            ->where([
                self::ATTR_PARENT_ID => $reference->get(self::ATTR_PARENT_ID),
                self::ATTR_ORDER . '>' => $reference->get(self::ATTR_ORDER),
            ])
            ->set([
                self::ATTR_ORDER => Expr::add(Expr::column(self::ATTR_ORDER), 2)
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);
    }

    private function decrementBefore(Entity $reference): void
    {
        $update = UpdateBuilder::create()
            ->in($reference->getEntityType())
            ->where([
                self::ATTR_PARENT_ID => $reference->get(self::ATTR_PARENT_ID),
                self::ATTR_ORDER . '<' => $reference->get(self::ATTR_ORDER),
            ])
            ->set([
                self::ATTR_ORDER => Expr::subtract(Expr::column(self::ATTR_ORDER), 2)
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);
    }

    private function rearrange(Entity $reference): void
    {
        $entities = $this->entityManager
            ->getRDBRepository($reference->getEntityType())
            ->where([
                self::ATTR_PARENT_ID => $reference->get(self::ATTR_PARENT_ID),
            ])
            ->order(self::ATTR_ORDER)
            ->find();

        foreach ($entities as $i => $entity) {
            $entity->set(self::ATTR_ORDER, $i + 1);

            $this->entityManager->saveEntity($entity, [SaveOption::SKIP_ALL => true]);
        }
    }

    private function processAfter(Entity $reference, CategoryTree $entity): void
    {
        $this->incrementAfter($reference);

        $order = ($reference->get(self::ATTR_ORDER) ?? 0) + 1;

        $entity->set(self::ATTR_ORDER, $order);
        $entity->set(self::ATTR_PARENT_ID, $reference->get(self::ATTR_PARENT_ID));

        $this->entityManager->saveEntity($entity);

        $this->rearrange($reference);
    }

    private function processBefore(Entity $reference, CategoryTree $entity): void
    {
        $this->decrementBefore($reference);

        $order = ($reference->get(self::ATTR_ORDER) ?? 0) - 1;

        $entity->set(self::ATTR_ORDER, $order);
        $entity->set(self::ATTR_PARENT_ID, $reference->get(self::ATTR_PARENT_ID));

        $this->entityManager->saveEntity($entity);

        $this->rearrange($reference);
    }

    /**
     * @throws Forbidden
     */
    private function processInto(?Entity $reference, CategoryTree $entity, MoveParams $params): void
    {
        if ($reference && !$this->acl->checkEntityEdit($reference)) {
            throw new Forbidden("No edit access to target category.");
        }

        if ($reference) {
            $this->checkReferenceNoLoop($reference, $entity);
        }

        $entity->setMultiple([
            self::ATTR_PARENT_ID => $params->referenceId,
            self::ATTR_ORDER => null,
        ]);

        $this->entityManager->saveEntity($entity);
    }

    /**
     * @throws Forbidden
     */
    private function checkReferenceNoLoop(Entity $reference, CategoryTree $entity): void
    {
        $this->loopReferenceChecker->check($entity, $reference);
    }
}
