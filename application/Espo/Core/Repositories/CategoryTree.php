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

namespace Espo\Core\Repositories;

use Espo\ORM\Entity;
use Espo\ORM\Mapper\BaseMapper;

/**
 * @template TEntity of Entity
 * @extends Database<Entity>
 */
class CategoryTree extends Database
{
    /**
     * @param array<string, mixed> $options
     * @return void
     */
    protected function afterSave(Entity $entity, array $options = [])
    {
        parent::afterSave($entity, $options);

        $parentId = $entity->get('parentId');

        $em = $this->entityManager;

        $pathEntityType = $entity->getEntityType() . 'Path';

        if ($entity->isNew()) {
            if ($parentId) {
                $subSelect1 = $em->getQueryBuilder()
                    ->select()
                    ->from($pathEntityType)
                    ->select(['ascendorId', "'" . $entity->getId() . "'"])
                    ->where([
                        'descendorId' => $parentId,
                    ])
                    ->build();

                $subSelect2 = $em->getQueryBuilder()
                    ->select()
                    ->select(["'" . $entity->getId() . "'", "'" . $entity->getId() . "'"])
                    ->build();

                $select = $em->getQueryBuilder()
                    ->union()
                    ->all()
                    ->query($subSelect1)
                    ->query($subSelect2)
                    ->build();

                $insert = $em->getQueryBuilder()
                    ->insert()
                    ->into($pathEntityType)
                    ->columns(['ascendorId', 'descendorId'])
                    ->valuesQuery($select)
                    ->build();

                $em->getQueryExecutor()->execute($insert);

                return;
            }

            $insert = $em->getQueryBuilder()
                ->insert()
                ->into($pathEntityType)
                ->columns(['ascendorId', 'descendorId'])
                ->values([
                    'ascendorId' => $entity->getId(),
                    'descendorId' => $entity->getId(),
                ])
                ->build();

            $em->getQueryExecutor()->execute($insert);

            return;
        }

        if (!$entity->isAttributeChanged('parentId')) {
            return;
        }

        $delete = $em->getQueryBuilder()
            ->delete()
            ->from($pathEntityType, 'a')
            ->join(
                $pathEntityType,
                'd',
                [
                    'd.descendorId:' => 'a.descendorId',
                ]
            )
            ->leftJoin(
                $pathEntityType,
                'x',
                [
                    'x.ascendorId:' => 'd.descendorId',
                    'x.descendorId:' => 'a.ascendorId',
                ]
            )
            ->where([
                'd.descendorId' => $entity->getId(),
                'x.ascendorId' => null,
            ])
            ->build();

        $em->getQueryExecutor()->execute($delete);

        if (!empty($parentId)) {
            $select = $em->getQueryBuilder()
                ->select()
                ->from($pathEntityType)
                ->select(['ascendorId', 's.descendorId'])
                ->join($pathEntityType, 's')
                ->where([
                    's.ascendorId' => $entity->getId(),
                    'descendorId' => $parentId,
                ])
                ->build();

            $insert = $em->getQueryBuilder()
                ->insert()
                ->into($pathEntityType)
                ->columns(['ascendorId', 'descendorId'])
                ->valuesQuery($select)
                ->build();

            $em->getQueryExecutor()->execute($insert);
        }
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        $pathEntityType = $entity->getEntityType() . 'Path';

        $em = $this->entityManager;

        $delete = $em->getQueryBuilder()
            ->delete()
            ->from($pathEntityType)
            ->where([
                'descendorId' => $entity->getId(),
            ])
            ->build();

        $em->getQueryExecutor()->execute($delete);

        $mapper = $em->getMapper();

        if (!$mapper instanceof BaseMapper) {
            return;
        }

        $mapper->deleteFromDb($entity->getEntityType(), $entity->getId());
    }
}
