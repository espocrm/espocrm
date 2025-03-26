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

namespace Espo\Core\ORM;

use Espo\Core\Field\Link;
use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Defs\AttributeParam;
use Espo\ORM\BaseEntity;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity as OrmEntity;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Type\AttributeType;
use Espo\ORM\Type\RelationType;

use LogicException;
use stdClass;

/**
 * An entity.
 */
class Entity extends BaseEntity
{
    /**
     * Has a link-multiple field.
     */
    public function hasLinkMultipleField(string $field): bool
    {
        return
            $this->hasRelation($field) &&
            $this->getAttributeParam($field . 'Ids', AttributeParam::IS_LINK_MULTIPLE_ID_LIST);
    }

    /**
     * Has a link field.
     */
    public function hasLinkField(string $field): bool
    {
        return $this->hasAttribute($field . 'Id') && $this->hasRelation($field);
    }

    /**
     * Has a link-parent field.
     */
    public function hasLinkParentField(string $field): bool
    {
        return $this->getAttributeType($field . 'Type') === AttributeType::FOREIGN_TYPE &&
            $this->hasAttribute($field . 'Id');
    }

    /**
     * Load a parent-name field.
     */
    public function loadParentNameField(string $field): void
    {
        if (!$this->hasLinkParentField($field)) {
            throw new LogicException("Called `loadParentNameField` on non-link-parent field `$field`.");
        }

        $idAttribute = $field . 'Id';
        $nameAttribute = $field . 'Name';

        $parentId = $this->get($idAttribute);
        $parentType = $this->get($field . 'Type');

        if (!$this->entityManager) {
            throw new LogicException("No entity-manager.");
        }

        $toSetFetched = !$this->isNew() && !$this->hasFetched($idAttribute);

        if (!$parentId || !$parentType) {
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $this->set($nameAttribute, null);

            if ($toSetFetched) {
                $this->setFetched($nameAttribute, null);
            }

            return;
        }

        if (!$this->entityManager->hasRepository($parentType)) {
            return;
        }

        $repository = $this->entityManager->getRDBRepository($parentType);

        $select = [Attribute::ID, Field::NAME];

        $foreignEntity = $repository
            ->select($select)
            ->where([Attribute::ID => $parentId])
            ->findOne();

        $entityName = $foreignEntity ? $foreignEntity->get(Field::NAME) : null;

        $this->set($nameAttribute, $entityName);

        if ($toSetFetched) {
            $this->setFetched($nameAttribute, $entityName);
        }
    }

    /**
     * @param string $link
     * @return ?array{
     *     orderBy: string|array<int, array{string, string}>|null,
     *     order: ?string,
     * }
     */
    protected function getRelationOrderParams(string $link): ?array
    {
        $field = $link;

        $idsAttribute = $field . 'Ids';

        $foreignEntityType = $this->getRelationParam($field, RelationParam::ENTITY);

        if ($this->getAttributeParam($idsAttribute, 'orderBy')) {
            $defs = [
                'orderBy' => $this->getAttributeParam($idsAttribute, 'orderBy'),
                'order' => Order::ASC,
            ];

            if ($this->getAttributeParam($idsAttribute, 'orderDirection')) {
                $defs['order'] = $this->getAttributeParam($idsAttribute, 'orderDirection');
            }

            return $defs;
        }

        if ($this->getRelationParam($link, 'orderBy')) {
            $defs = [
                'orderBy' => $this->getRelationParam($link, 'orderBy'),
                'order' => Order::ASC,
            ];

            if ($this->getRelationParam($link, 'order')) {
                $defs['order'] = strtoupper($this->getRelationParam($link, 'order'));
            }

            return $defs;
        }

        if (!$foreignEntityType || !$this->entityManager) {
            return null;
        }

        $ormDefs = $this->entityManager->getMetadata()->getDefs();

        if (!$ormDefs->hasEntity($foreignEntityType)) {
            return null;
        }

        $entityDefs = $ormDefs->getEntity($foreignEntityType);

        $collectionDefs = $entityDefs->getParam('collection') ?? [];

        $orderBy = $collectionDefs['orderBy'] ?? null;
        $order = $collectionDefs['order'] ?? 'ASC';

        if (!$orderBy) {
            return null;
        }

        if (!$entityDefs->hasAttribute($orderBy)) {
            return null;
        }

        return [
            'orderBy' => $orderBy,
            'order' => $order,
        ];
    }

    /**
     * Load a link-multiple field. Should be used wisely. Consider using `getLinkMultipleIdList` instead.
     *
     * @param ?array<string, string> $columns Deprecated as of v9.0.
     * @todo Add a method to load and set only fetched values?
     * @internal
     */
    public function loadLinkMultipleField(string $field, ?array $columns = null): void
    {
        if (!$this->hasLinkMultipleField($field)) {
            throw new LogicException("Called `loadLinkMultipleField` on non-link-multiple field `$field`.");
        }

        if (!$this->entityManager) {
            throw new LogicException("No entity-manager.");
        }

        $select = [Attribute::ID, Field::NAME];

        $hasType = $this->hasAttribute($field . 'Types');

        if ($hasType) {
            $select[] = 'type';
        }

        $columns ??= $this->getLinkMultipleColumnsFromDefs($field);

        if ($columns) {
            foreach ($columns as $it) {
                $select[] = $it;
            }
        }

        $selectBuilder = $this->entityManager
            ->getRDBRepository($this->getEntityType())
            ->getRelation($this, $field)
            ->select($select);

        $orderBy = null;
        $order = null;

        $orderParams = $this->getRelationOrderParams($field);

        if ($orderParams) {
            $orderBy = $orderParams['orderBy'] ?? null;
            /** @var string|bool|null $order */
            $order = $orderParams['order'] ?? null;
        }

        if ($orderBy) {
            if (is_string($orderBy) && !in_array($orderBy, $select)) {
                $selectBuilder->select($orderBy);
            }

            if (is_string($order)) {
                $order = strtoupper($order);

                if ($order !== Order::ASC && $order !== Order::DESC) {
                    $order = Order::ASC;
                }
            }

            $selectBuilder->order($orderBy, $order);
        }

        $collection = $selectBuilder->find();

        $ids = [];
        $names = (object) [];
        $types = (object) [];
        $columnsData = (object) [];

        foreach ($collection as $e) {
            $id = $e->getId();

            $ids[] = $id;

            $names->$id = $e->get(Field::NAME);

            if ($hasType) {
                $types->$id = $e->get('type');
            }

            if (!$columns) {
                continue;
            }

            $columnsData->$id = (object) [];

            foreach ($columns as $column => $foreignAttribute) {
                $columnsData->$id->$column = $e->get($foreignAttribute);
            }
        }

        $idsAttribute = $field . 'Ids';
        $namesAttribute = $field . 'Names';
        $typesAttribute = $field . 'Types';
        $columnsAttribute = $field . 'Columns';

        $toSetFetched = !$this->isNew() && !$this->hasFetched($idsAttribute);

        $this->setInContainerNotWritten($idsAttribute, $ids);
        $this->setInContainerNotWritten($namesAttribute, $names);

        if ($toSetFetched) {
            $this->setFetched($idsAttribute, $ids);
            $this->setFetched($namesAttribute, $names);
        }

        if ($hasType) {
            $this->set($typesAttribute, $types);

            if ($toSetFetched) {
                $this->setFetched($typesAttribute, $types);
            }
        }

        if ($columns) {
            $this->setInContainerNotWritten($columnsAttribute, $columnsData);

            if ($toSetFetched) {
                $this->setFetched($columnsAttribute, $columnsData);
            }
        }
    }

    /**
     * Load a link field. If a value is already set, it will set only a fetched value.
     */
    public function loadLinkField(string $field): void
    {
        if (!$this->hasLinkField($field)) {
            throw new LogicException("Called `loadLinkField` on non-link field '$field'.");
        }

        if (
            $this->getRelationType($field) !== RelationType::HAS_ONE &&
            $this->getRelationType($field) !== RelationType::BELONGS_TO
        ) {
            throw new LogicException("Can't load link '$field'.");
        }

        if (!$this->entityManager) {
            throw new LogicException("No entity-manager.");
        }

        $select = [Attribute::ID, Field::NAME];

        $entity = $this->entityManager
            ->getRelation($this, $field)
            ->select($select)
            ->findOne();

        $entityId = null;
        $entityName = null;

        if ($entity) {
            $entityId = $entity->getId();
            $entityName = $entity->get(Field::NAME);
        }

        $idAttribute = $field . 'Id';
        $nameAttribute = $field . 'Name';

        if (!$this->isNew() && !$this->hasFetched($idAttribute)) {
            $this->setFetched($idAttribute, $entityId);
            $this->setFetched($nameAttribute, $entityName);
        }

        if ($this->has($idAttribute)) {
            return;
        }

        $this->setInContainerNotWritten($idAttribute, $entityId);
        $this->setInContainerNotWritten($nameAttribute, $entityName);
    }

    /**
     * Get a link-multiple name.
     */
    public function getLinkMultipleName(string $field, string $id): ?string
    {
        $namesAttribute = $field . 'Names';

        if (!$this->hasAttribute($namesAttribute)) {
            throw new LogicException("Called `getLinkMultipleName` on non-link-multiple field `$field.");
        }

        if (!$this->has($namesAttribute) && !$this->isNew()) {
            $this->loadLinkMultipleField($field);
        }

        $object = $this->get($namesAttribute) ?? (object) [];

        if (!$object instanceof stdClass) {
            throw new LogicException("Non-object value in `$namesAttribute`.");
        }

        return $object->$id ?? null;
    }

    /**
     * Set a link-multiple name.
     */
    public function setLinkMultipleName(string $field, string $id, ?string $value): void
    {
        $namesAttribute = $field . 'Names';

        if (!$this->hasAttribute($namesAttribute)) {
            throw new LogicException("Called `setLinkMultipleName` on non-link-multiple field `$field.");
        }

        if (!$this->has($namesAttribute)) {
            return;
        }

        $object = $this->get($namesAttribute) ?? (object) [];

        if (!$object instanceof stdClass) {
            throw new LogicException("Non-object value in `$namesAttribute`.");
        }

        $object->$id = $value;

        $this->set($namesAttribute, $object);
    }

    /**
     * Get a link-multiple column value.
     */
    public function getLinkMultipleColumn(string $field, string $column, string $id): mixed
    {
        $columnsAttribute = $field . 'Columns';

        if (!$this->hasAttribute($columnsAttribute)) {
            throw new LogicException("Called `getLinkMultipleColumn` on not supported field `$field.");
        }

        if (!$this->has($columnsAttribute) && !$this->isNew()) {
            $this->loadLinkMultipleField($field);
        }

        $object = $this->get($columnsAttribute) ?? (object) [];

        if (!$object instanceof stdClass) {
            throw new LogicException("Non-object value in `$columnsAttribute`.");
        }

        return $object->$id->$column ?? null;
    }

    /**
     * Set a link-multiple column value.
     */
    public function setLinkMultipleColumn(string $field, string $column, string $id, mixed $value): void
    {
        $columnsAttribute = $field . 'Columns';

        if (!$this->hasAttribute($columnsAttribute)) {
            throw new LogicException("Called `setLinkMultipleColumn` on non-link-multiple field `$field.");
        }

        if (!$this->has($columnsAttribute) && !$this->isNew()) {
            $this->loadLinkMultipleField($field);
        }

        $object = $this->get($columnsAttribute) ?? (object) [];

        if (!$object instanceof stdClass) {
            throw new LogicException("Non-object value in `$columnsAttribute`.");
        }

        $object->$id ??= (object) [];
        $object->$id->$column = $value;

        $this->set($columnsAttribute, $object);
    }

    /**
     * Set link-multiple IDs.
     *
     * @param string[] $idList
     */
    public function setLinkMultipleIdList(string $field, array $idList): void
    {
        $idsAttribute = $field . 'Ids';

        if (!$this->hasAttribute($idsAttribute)) {
            throw new LogicException("Called `setLinkMultipleIdList` on non-link-multiple field `$field.");
        }

        $this->set($idsAttribute, $idList);
    }

    /**
     * Add an ID to a link-multiple field.
     */
    public function addLinkMultipleId(string $field, string $id): void
    {
        $idsAttribute = $field . 'Ids';

        if (!$this->hasAttribute($idsAttribute)) {
            throw new LogicException("Called `addLinkMultipleId` on non-link-multiple field `$field.");
        }

        if (!$this->has($idsAttribute)) {
            if (!$this->isNew()) {
                $this->loadLinkMultipleField($field);
            } else {
                $this->set($idsAttribute, []);
            }
        }

        if (!$this->has($idsAttribute)) {
            return;
        }

        $idList = $this->get($idsAttribute);

        if ($idList === null) {
            throw new LogicException("Null value set in `$idsAttribute`.");
        }

        if (!is_array($idList)) {
            throw new LogicException("Non-array value set in `$idsAttribute`.");
        }

        if (in_array($id, $idList)) {
            return;
        }

        $idList[] = $id;

        $this->set($idsAttribute, $idList);
    }

    /**
     * Remove an ID from link-multiple field.
     */
    public function removeLinkMultipleId(string $field, string $id): void
    {
        if (!$this->hasLinkMultipleId($field, $id)) {
            return;
        }

        $list = $this->getLinkMultipleIdList($field);

        $index = array_search($id, $list);

        if ($index !== false) {
            unset($list[$index]);

            $list = array_values($list);
        }

        $this->setLinkMultipleIdList($field, $list);
    }

    /**
     * Get link-multiple field IDs.
     *
     * @return string[]
     */
    public function getLinkMultipleIdList(string $field): array
    {
        $idsAttribute = $field . 'Ids';

        if (!$this->hasAttribute($idsAttribute)) {
            throw new LogicException("Called `getLinkMultipleIdList` for non-link-multiple field `$field.");
        }

        if (!$this->has($idsAttribute) && !$this->isNew()) {
            $this->loadLinkMultipleField($field);
        }

        /** @var string[] */
        return $this->get($idsAttribute) ?? [];
    }

    /**
     * Get previous link-multiple field IDs.
     *
     * @return string[]
     * @since 9.1.0
     */
    public function getFetchedLinkMultipleIdList(string $field): array
    {
        $idsAttribute = $field . 'Ids';

        if (!$this->hasAttribute($idsAttribute)) {
            throw new LogicException("Called `getFetchedLinkMultipleIdList` for non-link-multiple field `$field.");
        }

        if (!$this->isNew()) {
            if (!$this->has($idsAttribute)) {
                $this->loadLinkMultipleField($field);
            } else if (!$this->hasFetched($field)) {
                // Set but not loaded.

                $attributes = [
                    $field . 'Ids',
                    $field . 'Names',
                    $field . 'Types',
                    $field . 'Columns',
                ];

                $map = array_reduce($attributes, function ($p, $item) {
                    if (!$this->has($item)) {
                        return $p;
                    }

                    $p[$item] = $this->get($item);

                    return $p;
                }, []);

                $this->loadLinkMultipleField($field);

                // Restore set values.
                $this->setMultiple($map);
            }
        }

        if (!$this->has($idsAttribute) && !$this->isNew()) {
            $this->loadLinkMultipleField($field);
        }

        /** @var string[] */
        return $this->getFetched($idsAttribute) ?? [];
    }

    /**
     * Has an ID in a link-multiple field.
     */
    public function hasLinkMultipleId(string $field, string $id): bool
    {
        $idsAttribute = $field . 'Ids';

        if (!$this->hasAttribute($idsAttribute)) {
            throw new LogicException("Called `hasLinkMultipleId` for non-link-multiple field `$field.");
        }

        if (!$this->has($idsAttribute) && !$this->isNew()) {
            $this->loadLinkMultipleField($field);
        }

        if (!$this->has($idsAttribute)) {
            return false;
        }

        /** @var string[] $idList */
        $idList = $this->get($idsAttribute) ?? [];

        return in_array($id, $idList);
    }

    /**
     * @return string[]|null
     */
    private function getLinkMultipleColumnsFromDefs(string $field): ?array
    {
        if (!$this->entityManager) {
            return null;
        }

        $entityDefs = $this->entityManager->getDefs()->getEntity($this->entityType);

        /** @var ?array<string, string> $columns */
        $columns = $entityDefs->tryGetField($field)?->getParam('columns');

        if (!$columns) {
            return $columns;
        }

        $foreignEntityType = $entityDefs->tryGetRelation($field)?->tryGetForeignEntityType();

        if ($foreignEntityType) {
            $foreignEntityDefs = $this->entityManager->getDefs()->getEntity($foreignEntityType);

            foreach ($columns as $column => $attribute) {
                if (!$foreignEntityDefs->hasAttribute($attribute)) {
                    // For backward compatibility. If foreign attributes defined in the field do not exist.
                    unset($columns[$column]);
                }
            }
        }

        return $columns;
    }

    /**
     * @since 9.0.0
     */
    protected function setRelatedLinkOrEntity(string $relation, Link|LinkParent|OrmEntity|null $related): static
    {
        if ($related instanceof Entity || $related === null) {
            $this->relations->set($relation, $related);

            return $this;
        }

        $this->setValueObject($relation, $related);

        return $this;
    }
}
