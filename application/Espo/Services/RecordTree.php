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

namespace Espo\Services;

use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Name\Field;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Order;
use Espo\Core\Acl\Table as AclTable;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\UpdateParams;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\Where\Item as WhereItem;
use Espo\Core\Acl\Exceptions\NotImplemented;

use ArrayAccess;
use stdClass;

/**
 * @template TEntity of Entity
 * @extends Record<TEntity>
 */
class RecordTree extends Record
{
    private const MAX_DEPTH = 2;

    private ?Entity $seed = null;

    /** @var ?string */
    protected $subjectEntityType = null;
    /** @var ?string */
    protected $categoryField = null;

    /**
     * @param array{where?: ?WhereItem, onlyNotEmpty?: bool} $params
     * @return ?Collection<Entity>
     * @throws Forbidden
     * @throws BadRequest
     */
    public function getTree(
        ?string $parentId = null,
        array $params = [],
        ?int $maxDepth = null
    ): ?Collection {

        if (!$this->acl->check($this->entityType, Table::ACTION_READ)) {
            throw new Forbidden();
        }

        /** @noinspection PhpRedundantOptionalArgumentInspection */
        return $this->getTreeInternal($parentId, $params, $maxDepth, 0);
    }

    /**
     * @param array{where?: ?WhereItem, onlyNotEmpty?: bool} $params
     * @return ?Collection<Entity>
     * @throws BadRequest
     * @throws Forbidden
     */
    private function getTreeInternal(
        ?string $parentId = null,
        array $params = [],
        ?int $maxDepth = null,
        int $level = 0
    ): ?Collection {

        if (!$maxDepth) {
            $maxDepth = self::MAX_DEPTH;
        }

        if ($level === $maxDepth) {
            return null;
        }

        $searchParams = SearchParams::create();

        if (isset($params['where'])) {
            $searchParams = $searchParams->withWhere($params['where']);
        }

        $selectBuilder = $this->selectBuilderFactory
            ->create()
            ->from($this->entityType)
            ->withStrictAccessControl()
            ->withSearchParams($searchParams)
            ->buildQueryBuilder()
            ->where([
                'parentId' => $parentId,
            ]);

        $selectBuilder->order([]);

        if ($this->hasOrder()) {
            $selectBuilder->order('order', Order::ASC);
        }

        $selectBuilder->order(Field::NAME, Order::ASC);

        $filterItems = false;

        if ($this->checkFilterOnlyNotEmpty()) {
            $filterItems = true;
        }

        $collection = $this->getRepository()
            ->clone($selectBuilder->build())
            ->find();

        if (
            (!empty($params['onlyNotEmpty']) || $filterItems) &&
            $collection instanceof ArrayAccess
        ) {
            foreach ($collection as $i => $entity) {
                if ($this->checkItemIsEmpty($entity)) {
                    unset($collection[$i]);
                }
            }
        }

        foreach ($collection as $entity) {
            $childList = $this->getTreeInternal($entity->getId(), $params, $maxDepth, $level + 1);

            $entity->set('childList', $childList?->getValueMapList());
        }

        return $collection;
    }

    protected function checkFilterOnlyNotEmpty(): bool
    {
        try {
            if (!$this->acl->checkScope($this->getSubjectEntityType(), Table::ACTION_CREATE)) {
                return true;
            }
        } catch (NotImplemented) {
            return false;
        }

        return false;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    protected function checkItemIsEmpty(Entity $entity): bool
    {
        $entityType = $this->getSubjectEntityType();

        // If used without an actual subject entity.
        if (!$this->entityManager->hasRepository($entityType)) {
            return true;
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->withStrictAccessControl()
            ->withWhere(
                WhereItem::fromRaw([
                    'type' => 'inCategory',
                    'attribute' => $this->getCategoryField(),
                    'value' => $entity->getId(),
                ])
            )
            ->build();

        $one = $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($query)
            ->select([Attribute::ID])
            ->findOne();

        if ($one) {
            return false;
        }

        return true;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function getCategoryData(?string $id): ?stdClass
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_READ)) {
            throw new Forbidden();
        }

        if ($id === null) {
            return null;
        }

        $category = $this->entityManager->getEntityById($this->entityType, $id);

        if (!$category) {
            throw new NotFound();
        }

        if (!$this->acl->check($category, AclTable::ACTION_READ)) {
            throw new Forbidden();
        }

        return (object) [
            'upperId' => $category->get('parentId'),
            'upperName' => $category->get('parentName'),
            'id' => $id,
            'name' => $category->get(Field::NAME),
        ];
    }

    /**
     * @return string[]
     * @throws Forbidden
     */
    public function getTreeItemPath(?string $parentId = null): array
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_READ)) {
            throw new Forbidden();
        }

        $arr = [];

        while (1) {
            if (empty($parentId)) {
                break;
            }

            $parent = $this->entityManager->getEntityById($this->entityType, $parentId);

            if ($parent) {
                $parentId = $parent->get('parentId');

                array_unshift($arr, $parent->getId());
            } else {
                $parentId = null;
            }
        }

        return $arr;
    }

    private function getSeed(): Entity
    {
        if (empty($this->seed)) {
            $this->seed = $this->entityManager->getNewEntity($this->entityType);
        }

        return $this->seed;
    }

    private function hasOrder(): bool
    {
        $seed = $this->getSeed();

        if ($seed->hasAttribute('order')) {
            return true;
        }

        return false;
    }

    /**
     * @throws Forbidden
     * @throws Error
     * @todo Refactor.
     */
    protected function beforeCreateEntity(Entity $entity, $data)
    {
        parent::beforeCreateEntity($entity, $data);

        if (!empty($data->parentId)) {
            $parent = $this->entityManager->getEntityById($this->entityType, $data->parentId);

            if (!$parent) {
                throw new Error("Tried to create tree item entity with not existing parent.");
            }

            if (!$this->acl->check($parent, Table::ACTION_EDIT)) {
                throw new Forbidden();
            }
        }
    }

    /**
     * @throws Forbidden
     * @throws BadRequest
     */
    protected function beforeDeleteEntity(Entity $entity)
    {
        parent::beforeDeleteEntity($entity);

        $childCategory = $this->entityManager
            ->getRelation($entity, 'children')
            ->findOne();

        if ($childCategory) {
            throw Forbidden::createWithBody(
                'cannotRemoveCategoryWithChildCategory',
                Error\Body::create()->withMessageTranslation('cannotRemoveCategoryWithChildCategory')
            );
        }

        if (!$this->checkItemIsEmpty($entity)) {
            throw Forbidden::createWithBody(
                'cannotRemoveNotEmptyCategory',
                Error\Body::create()->withMessageTranslation('cannotRemoveNotEmptyCategory')
            );
        }
    }

    public function update(string $id, stdClass $data, UpdateParams $params): Entity
    {
        if (!empty($data->parentId) && $data->parentId === $id) {
            throw new Forbidden();
        }

        return parent::update($id, $data, $params);
    }

    public function link(string $id, string $link, string $foreignId): void
    {
        if ($id == $foreignId) {
            throw new Forbidden();
        }

        parent::link($id, $link, $foreignId);
    }

    /**
     * @return string[]
     * @throws Forbidden
     * @throws BadRequest
     */
    public function getLastChildrenIdList(?string $parentId = null): array
    {
        if (!$this->acl->check($this->entityType, Table::ACTION_READ)) {
            throw new Forbidden();
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from($this->entityType)
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->where([
                'parentId' => $parentId,
            ])
            ->build();

        $idList = [];

        $includingRecords = false;

        if ($this->checkFilterOnlyNotEmpty()) {
            $includingRecords = true;
        }

        $collection = $this->getRepository()
            ->clone($query)
            ->select([Attribute::ID])
            ->find();

        foreach ($collection as $entity) {
            $subQuery = $this->selectBuilderFactory
                ->create()
                ->from($this->entityType)
                ->withStrictAccessControl()
                ->buildQueryBuilder()
                ->where([
                    'parentId' => $entity->getId(),
                ])
                ->build();

            $count = $this->getRepository()
                ->clone($subQuery)
                ->count();

            if (!$count) {
                $idList[] = $entity->getId();

                continue;
            }

            if ($includingRecords) {
                $isNotEmpty = false;

                $subCollection = $this->getRepository()
                    ->clone($subQuery)
                    ->find();

                foreach ($subCollection as $subEntity) {
                    if (!$this->checkItemIsEmpty($subEntity)) {
                        $isNotEmpty = true;

                        break;
                    }
                }

                if (!$isNotEmpty) {
                    $idList[] = $entity->getId();
                }
            }
        }

        return $idList;
    }

    private function getSubjectEntityType(): string
    {
        return $this->metadata->get("scopes.$this->entityType.categoryParentEntityType") ??
            $this->subjectEntityType ??
            substr($this->entityType, 0, strlen($this->entityType) - 8);
    }

    private function getCategoryField(): string
    {
        return $this->metadata->get("scopes.$this->entityType.categoryField") ??
            $this->categoryField ??
            'category';
    }
}
