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

namespace Espo\Core\Formula\Functions\RecordGroup;

use Espo\Core\Formula\{
    Functions\BaseFunction,
    ArgumentList,
};

use Espo\Core\Di;

class RelationColumnType extends BaseFunction implements
    Di\EntityManagerAware
{
    use Di\EntityManagerSetter;

    public function process(ArgumentList $args)
    {
        $args = $this->evaluate($args);

        if (count($args) < 5) {
            $this->throwTooFewArguments(5);
        }

        $entityType = $args[0];
        $id = $args[1];
        $link = $args[2];
        $foreignId = $args[3];
        $column = $args[4];

        if (!$entityType) {
            $this->throwError("Empty entityType.");
        }

        if (!$id) {
            return null;
        }

        if (!$link) {
            $this->throwError("Empty link.");
        }

        if (!$foreignId) {
            return null;
        }

        if (!$column) {
            $this->throwError("Empty column.");
        }

        $em = $this->entityManager;

        if (!$em->hasRepository($entityType)) {
            $this->throwError("Repository '$entityType' does not exist.");
        }

        $entity = $em->getEntityById($entityType, $id);

        if (!$entity) {
            return null;
        }

        return $em->getRelation($entity, $link)->getColumnById($foreignId, $column);
    }
}
