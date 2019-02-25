<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\ORM\Entity;

class CategoryTree extends \Espo\Core\ORM\Repositories\RDB
{
	protected function afterSave(Entity $entity, array $options = array())
	{
		parent::afterSave($entity, $options);

		$pdo = $this->getEntityManager()->getPDO();
		$query = $this->getEntityManager()->getQuery();

		$parentId = $entity->get('parentId');
		$pathsTableName = $query->toDb($query->sanitize($entity->getEntityType()) . 'Path');

		if ($entity->isNew()) {
			if ($parentId) {
				$sql = "
					INSERT INTO `".$pathsTableName."` (ascendor_id, descendor_id)
						SELECT ascendor_id, ".$pdo->quote($entity->id)."
						FROM `".$pathsTableName."`
						WHERE descendor_id = ".$pdo->quote($parentId)."
						UNION ALL
						SELECT ".$pdo->quote($entity->id).", ".$pdo->quote($entity->id)."
				";
			} else {
				$sql = "
					INSERT INTO `".$pathsTableName."` (ascendor_id, descendor_id)
					VALUES
					(".$pdo->quote($entity->id).", ".$pdo->quote($entity->id).")
				";
			}
			$pdo->query($sql);
		} else {
			if ($entity->isAttributeChanged('parentId')) {
				$sql = "
					DELETE a FROM `".$pathsTableName."` AS a
					JOIN `".$pathsTableName."` AS d ON a.descendor_id = d.descendor_id
					LEFT JOIN `".$pathsTableName."` AS x ON x.ascendor_id = d.ascendor_id AND x.descendor_id = a.ascendor_id
					WHERE d.ascendor_id = ".$pdo->quote($entity->id)." AND x.ascendor_id IS NULL
				";
				$pdo->query($sql);
				if (!empty($parentId)) {
					$sql = "
						INSERT INTO `".$pathsTableName."` (ascendor_id, descendor_id)
							SELECT supertree.ascendor_id, subtree.descendor_id
							FROM `".$pathsTableName."` AS supertree
							JOIN `".$pathsTableName."` AS subtree
							WHERE
								subtree.ascendor_id = ".$pdo->quote($entity->id)." AND
								supertree.descendor_id = ".$pdo->quote($parentId)."
					";
					$pdo->query($sql);
				}
			}
		}
	}

	protected function afterRemove(Entity $entity, array $options = array())
	{
		parent::afterRemove($entity, $options);

		$pdo = $this->getEntityManager()->getPDO();
		$query = $this->getEntityManager()->getQuery();

		$pathsTableName = $query->toDb($query->sanitize($entity->getEntityType()) . 'Path');

		$sql = "DELETE FROM `".$pathsTableName."` WHERE descendor_id = ".$pdo->quote($entity->id)."";
		$pdo->query($sql);
	}
}

