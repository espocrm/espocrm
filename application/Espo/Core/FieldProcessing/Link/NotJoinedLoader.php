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

namespace Espo\Core\FieldProcessing\Link;

use Espo\ORM\Entity;

use Espo\Core\{
    ORM\EntityManager,
    FieldProcessing\Loader as LoaderInterface,
    FieldProcessing\Loader\Params,
};

use Espo\ORM\Defs as OrmDefs;

class NotJoinedLoader implements LoaderInterface
{
    private $ormDefs;

    private $entityManager;

    private $fieldListCacheMap = [];

    public function __construct(OrmDefs $ormDefs, EntityManager $entityManager)
    {
        $this->ormDefs = $ormDefs;
        $this->entityManager = $entityManager;
    }

    public function process(Entity $entity, Params $params): void
    {
        foreach ($this->getFieldList($entity->getEntityType()) as $field) {
            $this->processItem($entity, $field);
        }
    }

    private function processItem(Entity $entity, string $field): void
    {
        $nameAttribute = $field . 'Name';
        $idAttribute = $field . 'Id';

        $id = $entity->get($idAttribute);

        if (!$id) {
            $entity->set($nameAttribute, null);

            return;
        }

        if ($entity->get($nameAttribute)) {
            return;
        }

        $foreignEntityType = $this->ormDefs
            ->getEntity($entity->getEntityType())
            ->getRelation($field)
            ->getForeignEntityType();

        $foreignEntity = $this->entityManager
            ->getRDBRepository($foreignEntityType)
            ->select(['id', 'name'])
            ->where(['id' => $id])
            ->findOne();

        if (!$foreignEntity) {
            $entity->set($nameAttribute, null);

            return;
        }

        $entity->set($nameAttribute, $foreignEntity->get('name'));
    }

    /**
     * @return string[]
     */
    private function getFieldList(string $entityType): array
    {
        if (array_key_exists($entityType, $this->fieldListCacheMap)) {
            return $this->fieldListCacheMap[$entityType];
        }

        $list = [];

        $entityDefs = $this->ormDefs->getEntity($entityType);

        foreach ($entityDefs->getRelationList() as $relationDefs) {
            if ($relationDefs->getType() !== Entity::BELONGS_TO) {
                continue;
            }

            if (!$relationDefs->getParam('noJoin')) {
                continue;
            }

            if (!$relationDefs->hasForeignEntityType()) {
                continue;
            }

            $foreignEntityType = $relationDefs->getForeignEntityType();

            if (!$this->entityManager->hasRepository($foreignEntityType)) {
                continue;
            }

            $name = $relationDefs->getName();

            if (!$entityDefs->hasAttribute($name . 'Id')) {
                continue;
            }

            if (!$entityDefs->hasAttribute($name . 'Name')) {
                continue;
            }

            $list[] = $name;
        }

        $this->fieldListCacheMap[$entityType] = $list;

        return $list;
    }
}
