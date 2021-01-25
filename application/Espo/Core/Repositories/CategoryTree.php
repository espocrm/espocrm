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

namespace Espo\Core\Repositories;

use Espo\ORM\Entity;

class CategoryTree extends Database
{
	protected function afterSave(Entity $entity, array $options = [])
	{
		parent::afterSave($entity, $options);

		$parentId = $entity->get('parentId');

        $em = $this->getEntityManager();

        $pathEntityType = $entity->getEntityType() . 'Path';

		if ($entity->isNew()) {
			if ($parentId) {
                $subSelect1 = $em->getQueryBuilder()
                    ->select()
                    ->from($pathEntityType)
                    ->select(['ascendorId', "'" . $entity->id . "'"])
                    ->where([
                        'descendorId' => $parentId,
                    ])
                    ->build();

                $subSelect2 = $em->getQueryBuilder()
                    ->select()
                    ->select(["'" . $entity->id . "'", "'" . $entity->id . "'"])
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
                    'ascendorId' => $entity->id,
                    'descendorId' => $entity->id,
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
                'd.descendorId' => $entity->id,
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
                    's.ascendorId' => $entity->id,
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

        $em = $this->getEntityManager();

        $delete = $em->getQueryBuilder()
            ->delete()
            ->from($pathEntityType)
            ->where([
                'descendorId' => $entity->id,
            ])
            ->build();

        $em->getQueryExecutor()->execute($delete);

        $em->getMapper()->deleteFromDb($entity->getEntityType(), $entity->id);
	}
}
