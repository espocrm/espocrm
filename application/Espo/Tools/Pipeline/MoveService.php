<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Tools\Pipeline;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Entities\Pipeline;
use Espo\Entities\PipelineStage;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Query\SelectBuilder;

class MoveService
{
    public const string TYPE_TOP = 'top';
    public const string TYPE_BOTTOM = 'bottom';
    public const string TYPE_UP = 'up';
    public const string TYPE_DOWN = 'down';

    public function __construct(
        private EntityManager $entityManager,
        private SelectBuilderFactory $selectBuilderFactory,
        private CacheClearer $cacheClearer,
    ) {}

    /**
     * @param self::TYPE_TOP|self::TYPE_BOTTOM|self::TYPE_UP|self::TYPE_DOWN $type
     * @throws BadRequest
     * @throws Forbidden
     */
    public function move(
        Pipeline $entity,
        string $type,
        SearchParams $searchParams,
    ): void {

        $builder = $this->createSelectBuilder($searchParams, $entity);

        if ($type === self::TYPE_TOP) {
            $this->moveToTop($entity, $builder);
        } else if ($type === self::TYPE_BOTTOM) {
            $this->moveToBottom($entity, $builder);
        } else if ($type === self::TYPE_UP) {
            $this->moveUp($entity, $builder);
        } else {
            $this->moveDown($entity, $builder);
        }

        $this->reOrder($entity::class);

        $this->cacheClearer->clear();
    }

    /**
     * @param class-string<Pipeline|PipelineStage> $className
     */
    public function reOrder(string $className, ?string $parentId = null): void
    {
        $this->entityManager
            ->getTransactionManager()
            ->run(fn () => $this->reOrderInternal($className, $parentId));
    }

    /**
     * @param class-string<Pipeline|PipelineStage> $className
     */
    private function reOrderInternal(string $className, ?string $parentId = null): void
    {
        $this->entityManager
            ->getRDBRepositoryByClass($className)
            ->select(Attribute::ID)
            ->forUpdate()
            ->sth()
            ->find();

        $builder = $this->entityManager
            ->getRDBRepositoryByClass($className)
            ->sth()
            ->order(Pipeline::FIELD_ORDER);

        if ($className === PipelineStage::class) {
            $builder->where([
                PipelineStage::ATTR_PIPELINE_ID => $parentId,
            ]);
        }

        $collection = $builder->find();

        foreach ($collection as $i => $entity) {
            $order = $i + 1;

            if ($entity->getOrder() === $order) {
                continue;
            }

            $entity->set(Pipeline::FIELD_ORDER, $order);

            $this->entityManager->saveEntity($entity, [SaveOption::SKIP_HOOKS => true]);
        }
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function createSelectBuilder(
        SearchParams $searchParams,
        Pipeline $entity,
    ): SelectBuilder {

        /** @noinspection PhpRedundantOptionalArgumentInspection */
        return $this->selectBuilderFactory
            ->create()
            ->from($entity::ENTITY_TYPE)
            ->withSearchParams($searchParams)
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->limit(null, null)
            ->order([]);
    }

    private function moveUp(
        Pipeline $entity,
        SelectBuilder $builder,
    ): void {

        $query = $builder
            ->where([Pipeline::FIELD_ORDER . '<' => $entity->getOrder()])
            ->order(Pipeline::FIELD_ORDER, Order::DESC)
            ->build();

        $another = $this->entityManager
            ->getRDBRepositoryByClass($entity::class)
            ->clone($query)
            ->findOne();

        if (!$another) {
            return;
        }

        $index = $entity->getOrder();

        $entity->set(Pipeline::FIELD_ORDER, $another->getOrder());
        $another->set(Pipeline::FIELD_ORDER, $index);

        $this->entityManager->saveEntity($entity);
        $this->entityManager->saveEntity($another);
    }

    private function moveDown(
        Pipeline $entity,
        SelectBuilder $builder,
    ): void {

        $query = $builder
            ->where([Pipeline::FIELD_ORDER . '>' => $entity->getOrder()])
            ->order(Pipeline::FIELD_ORDER, Order::ASC)
            ->build();

        $another = $this->entityManager
            ->getRDBRepositoryByClass($entity::class)
            ->clone($query)
            ->findOne();

        if (!$another) {
            return;
        }

        $index = $entity->getOrder();

        $entity->set(Pipeline::FIELD_ORDER, $another->getOrder());
        $another->set(Pipeline::FIELD_ORDER, $index);

        $this->entityManager->saveEntity($entity);
        $this->entityManager->saveEntity($another);

        $this->entityManager->refreshEntity($entity);
        $this->entityManager->refreshEntity($another);
    }

    private function moveToTop(
        Pipeline $entity,
        SelectBuilder $builder,
    ): void {

        $query = $builder
            ->where([Pipeline::FIELD_ORDER . '<' => $entity->getOrder()])
            ->order(Pipeline::FIELD_ORDER, Order::ASC)
            ->build();

        $another = $this->entityManager
            ->getRDBRepositoryByClass($entity::class)
            ->clone($query)
            ->findOne();

        if (!$another) {
            return;
        }

        $entity->set(Pipeline::FIELD_ORDER, $another->getOrder() - 1);

        $this->entityManager->saveEntity($entity);
    }

    private function moveToBottom(
        Pipeline $entity,
        SelectBuilder $builder,
    ): void {

        $query = $builder
            ->where([Pipeline::FIELD_ORDER . '>' => $entity->getOrder()])
            ->order(Pipeline::FIELD_ORDER, Order::DESC)
            ->build();

        $another = $this->entityManager
            ->getRDBRepositoryByClass($entity::class)
            ->clone($query)
            ->findOne();

        if (!$another) {
            return;
        }

        $entity->set(Pipeline::FIELD_ORDER, $another->getOrder() + 1);

        $this->entityManager->saveEntity($entity);
    }
}
