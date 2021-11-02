<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Field\LinkMultiple;

use Espo\{
    ORM\Entity,
    ORM\Value\ValueFactory,
    ORM\Defs,
    ORM\EntityManager,
};

use Espo\Core\{
    Field\LinkMultiple,
    Field\LinkMultipleItem,
    ORM\Entity as CoreEntity,
};

use RuntimeException;
use InvalidArgumentException;
use StdClass;

class LinkMultipleFactory implements ValueFactory
{
    /**
     * @var Defs
     */
    private $ormDefs;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(Defs $ormDefs, EntityManager $entityManager)
    {
        $this->ormDefs = $ormDefs;
        $this->entityManager = $entityManager;
    }

    public function isCreatableFromEntity(Entity $entity, string $field): bool
    {
        $entityType = $entity->getEntityType();

        $entityDefs = $this->ormDefs->getEntity($entityType);

        if (!$entityDefs->hasField($field)) {
            return false;
        }

        return $entityDefs->getField($field)->getType() === 'linkMultiple';
    }

    public function createFromEntity(Entity $entity, string $field): LinkMultiple
    {
        if (!$this->isCreatableFromEntity($entity, $field)) {
            throw new RuntimeException();
        }

        if (!$entity instanceof CoreEntity) {
            throw new InvalidArgumentException();
        }

        $itemList = [];

        if (!$entity->has($field . 'Ids')) {
            $this->loadLinkMultipleField($entity, $field);
        }

        $idList = $entity->getLinkMultipleIdList($field);

        $nameMap = $entity->get($field . 'Names') ?? (object) [];

        $columnData = null;

        if ($entity->hasAttribute($field . 'Columns')) {
            $columnData = $entity->get($field . 'Columns') ?
                $entity->get($field . 'Columns') :
                $this->loadColumnData($entity, $field);
        }

        foreach ($idList as $id) {
            $item = LinkMultipleItem::create($id);

            if ($columnData && property_exists($columnData, $id)) {
                $item = $this->addColumnValues($item, $columnData->$id);
            }

            $name = $nameMap->$id ?? null;

            if ($name !== null) {
                $item = $item->withName($name);
            }

            $itemList[] = $item;
        }

        return new LinkMultiple($itemList);
    }

    private function loadLinkMultipleField(CoreEntity $entity, $field): void
    {
        $columns = $this->ormDefs
            ->getEntity($entity->getEntityType())
            ->getField($field)
            ->getParam('columns');

        $entity->loadLinkMultipleField($field, $columns);
    }

    private function loadColumnData(Entity $entity, string $field): StdClass
    {
        $columnData = (object) [];

        $select = ['id'];

        $columns = $this->ormDefs
            ->getEntity($entity->getEntityType())
            ->getField($field)
            ->getParam('columns') ?? [];

        if (count($columns) === 0) {
            return $columnData;
        }

        foreach ($columns as $item) {
            $select[] = $item;
        }

        $collection = $this->entityManager
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, $field)
            ->select($select)
            ->find();

        foreach ($collection as $itemEntity) {
            $id = $itemEntity->getId();

            foreach ($columns as $column => $attribute) {
                $columnData->$id->$column = $itemEntity->get($attribute);
            }
        }

        return $columnData;
    }

    private function addColumnValues(LinkMultipleItem $item, object $data)
    {
        foreach (get_object_vars($data) as $column => $value) {
            $item = $item->withColumnValue($column, $value);
        }

        return $item;
    }
}
