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

namespace Espo\Core\FieldProcessing\Link;

use Espo\Core\Name\Field;
use Espo\ORM\Entity;

use Espo\Core\FieldProcessing\Loader as LoaderInterface;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\ORM\EntityManager;
use Espo\ORM\Defs as OrmDefs;
use Espo\ORM\Name\Attribute;

/**
 * @implements LoaderInterface<Entity>
 */
class NotJoinedLoader implements LoaderInterface
{
    /** @var array<string, string[]> */
    private array $fieldListCacheMap = [];

    public function __construct(
        private OrmDefs $ormDefs,
        private EntityManager $entityManager
    ) {}

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
            ->select([Attribute::ID, Field::NAME])
            ->where([Attribute::ID => $id])
            ->findOne();

        if (!$foreignEntity) {
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $entity->set($nameAttribute, null);

            return;
        }

        $name = $foreignEntity->get(Field::NAME);

        if ($name === null) {
            $foreignEntity = $this->entityManager
                ->getRDBRepository($foreignEntityType)
                ->getById($id);

            if ($foreignEntity) {
                $name = $foreignEntity->get(Field::NAME);
            }
        }

        $entity->set($nameAttribute, $name);
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

            // Commented to load name of leads w/o person name.
            /*if (!$relationDefs->getParam('noJoin')) {
                continue;
            }*/

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
