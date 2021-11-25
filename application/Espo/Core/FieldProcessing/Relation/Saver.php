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

namespace Espo\Core\FieldProcessing\Relation;

use Espo\ORM\Entity;

use Espo\Core\{
    ORM\EntityManager,
    FieldProcessing\Saver as SaverInterface,
    FieldProcessing\Saver\Params,
};

class Saver implements SaverInterface
{
    private $entityManager;

    private $linkMultipleSaver;

    private $manyRelationListMapCache = [];

    private $hasOneRelationListMapCache = [];

    private $belongsToHasOneRelationListMapCache = [];

    public function __construct(
        EntityManager $entityManager,
        LinkMultipleSaver $linkMultipleSaver
    ) {
        $this->entityManager = $entityManager;
        $this->linkMultipleSaver = $linkMultipleSaver;
    }

    public function process(Entity $entity, Params $params): void
    {
        $this->processMany($entity, $params);
        $this->processHasOne($entity);
        $this->processBelongsToHasOne($entity);
    }

    private function processMany(Entity $entity, Params $params): void
    {
        $entityType = $entity->getEntityType();

        foreach ($this->getManyRelationList($entityType) as $name) {
            $this->processManyItem($entity, $name, $params);
        }
    }

    private function processManyItem(Entity $entity, string $name, Params $params): void
    {
        $idListAttribute = $name . 'Ids';
        $columnsAttribute = $name . 'Columns';

        if (!$entity->has($idListAttribute) && !$entity->has($columnsAttribute)) {
            return;
        }

        $this->linkMultipleSaver->process($entity, $name, $params);
    }

    private function processHasOne(Entity $entity): void
    {
        $entityType = $entity->getEntityType();

        foreach ($this->getHasOneRelationList($entityType) as $name) {
            $this->processHasOneItem($entity, $name);
        }
    }

    private function processHasOneItem(Entity $entity, string $name): void
    {
        $entityType = $entity->getEntityType();

        $idAttribute = $name . 'Id';

        if (!$entity->has($idAttribute)) {
            return;
        }

        if (!$entity->isAttributeChanged($idAttribute)) {
            return;
        }

        $id = $entity->get($idAttribute);

        $defs = $this->entityManager->getDefs()->getEntity($entityType);

        $relationDefs = $defs->getRelation($name);

        $foreignKey = $relationDefs->getForeignKey();
        $foreignEntityType = $relationDefs->getForeignEntityType();

        $previousForeignEntity = $this->entityManager
            ->getRDBRepository($foreignEntityType)
            ->select(['id'])
            ->where([
                $foreignKey => $entity->getId(),
            ])
            ->findOne();

        if ($previousForeignEntity) {
            if (!$entity->isNew()) {
                $entity->setFetched($idAttribute, $previousForeignEntity->getId());
            }

            if (!$id) {
                $previousForeignEntity->set($foreignKey, null);

                $this->entityManager->saveEntity($previousForeignEntity, [
                    'skipAll' => true,
                ]);
            }
        }
        else if (!$entity->isNew()) {
            $entity->setFetched($idAttribute, null);
        }

        if ($id) {
            $this->entityManager
                ->getRDBRepository($entityType)
                ->getRelation($entity, $name)
                ->relateById($id);
        }
    }

    private function processBelongsToHasOne(Entity $entity): void
    {
        $entityType = $entity->getEntityType();

        foreach ($this->getBelongsToHasOneRelationList($entityType) as $name) {
            $this->processBelongsToHasOneItem($entity, $name);
        }
    }

    private function processBelongsToHasOneItem(Entity $entity, string $name): void
    {
        $entityType = $entity->getEntityType();

        $idAttribute = $name . 'Id';

        if (!$entity->get($idAttribute)) {
            return;
        }

        if (!$entity->isAttributeChanged($idAttribute)) {
            return;
        }

        $anotherEntity = $this->entityManager
            ->getRDBRepository($entityType)
            ->select(['id'])
            ->where([
                $idAttribute => $entity->get($idAttribute),
                'id!=' => $entity->getId(),
            ])
            ->findOne();

        if (!$anotherEntity) {
            return;
        }

        $anotherEntity->set($idAttribute, null);

        $this->entityManager->saveEntity($anotherEntity, [
            'skipAll' => true,
        ]);
    }

    private function getManyRelationList(string $entityType): array
    {
        if (array_key_exists($entityType, $this->manyRelationListMapCache)) {
            return $this->manyRelationListMapCache[$entityType];
        }

        $typeList = [
            Entity::HAS_MANY,
            Entity::MANY_MANY,
            Entity::HAS_CHILDREN,
        ];

        $defs = $this->entityManager->getDefs()->getEntity($entityType);

        $list = [];

        foreach ($defs->getRelationNameList() as $name) {
            $type = $defs->getRelation($name)->getType();

            if (!in_array($type, $typeList)) {
                continue;
            }

            if (!$defs->hasAttribute($name . 'Ids')) {
                continue;
            }

            if ($defs->hasField($name) && $defs->getField($name)->getParam('noSave')) {
                continue;
            }

            $list[] = $name;
        }

        $this->manyRelationListMapCache[$entityType] = $list;

        return $list;
    }

    private function getHasOneRelationList(string $entityType): array
    {
        if (array_key_exists($entityType, $this->hasOneRelationListMapCache)) {
            return $this->hasOneRelationListMapCache[$entityType];
        }

        $ormDefs = $this->entityManager->getDefs();

        $defs = $ormDefs->getEntity($entityType);

        $list = [];

        foreach ($defs->getRelationNameList() as $name) {
            $relationDefs = $defs->getRelation($name);

            $type = $relationDefs->getType();

            if ($type !== Entity::HAS_ONE) {
                continue;
            }

            if (!$relationDefs->hasForeignEntityType()) {
                continue;
            }

            if (!$relationDefs->hasForeignKey()) {
                continue;
            }

            if (!$defs->hasAttribute($name . 'Id')) {
                continue;
            }

            if ($defs->hasField($name) && $defs->getField($name)->getParam('noSave')) {
                continue;
            }

            $list[] = $name;
        }

        $this->hasOneRelationListMapCache[$entityType] = $list;

        return $list;
    }

    private function getBelongsToHasOneRelationList(string $entityType): array
    {
        if (array_key_exists($entityType, $this->belongsToHasOneRelationListMapCache)) {
            return $this->belongsToHasOneRelationListMapCache[$entityType];
        }

        $ormDefs = $this->entityManager->getDefs();

        $defs = $ormDefs->getEntity($entityType);

        $list = [];

        foreach ($defs->getRelationNameList() as $name) {
            $relationDefs = $defs->getRelation($name);

            $type = $relationDefs->getType();

            if ($type !== Entity::BELONGS_TO) {
                continue;
            }

            if (!$relationDefs->hasForeignRelationName()) {
                continue;
            }

            if (!$relationDefs->hasForeignEntityType()) {
                continue;
            }

            if (!$defs->hasAttribute($name . 'Id')) {
                continue;
            }

            $foreignEntityType = $relationDefs->getForeignEntityType();
            $foreignRelationName = $relationDefs->getForeignRelationName();

            $foreignType = $ormDefs
                ->getEntity($foreignEntityType)
                ->getRelation($foreignRelationName)
                ->getType();

            if ($foreignType !== Entity::HAS_ONE) {
                continue;
            }

            $list[] = $name;
        }

        $this->belongsToHasOneRelationListMapCache[$entityType] = $list;

        return $list;
    }
}
