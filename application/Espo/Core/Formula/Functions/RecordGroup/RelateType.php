<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Functions\BaseFunction;

use Espo\Core\Di;

class RelateType extends BaseFunction implements
    Di\EntityManagerAware
{
    use Di\EntityManagerSetter;

    public function process(ArgumentList $args)
    {
        if (count($args) < 4) {
            $this->throwTooFewArguments(4);
        }

        $entityType = $this->evaluate($args[0]);
        $id = $this->evaluate($args[1]);
        $link = $this->evaluate($args[2]);
        $foreignId = $this->evaluate($args[3]);

        if (!$entityType || !is_string($entityType)) {
            $this->throwBadArgumentType(1, 'string');
        }

        if (!$id) {
            return null;
        }

        if (!$link || !is_string($link)) {
            $this->throwBadArgumentType(3, 'string');
        }

        if (!$foreignId) {
            return null;
        }

        $em = $this->entityManager;

        if (!$em->hasRepository($entityType)) {
            $this->throwError("Repository does not exist.");
        }

        $entity = $em->getEntityById($entityType, $id);

        if (!$entity) {
            return null;
        }

        $relation = $em->getRDBRepository($entityType)->getRelation($entity, $link);

        if (is_array($foreignId)) {
            foreach ($foreignId as $itemId) {
                $relation->relateById($itemId);
            }

            return true;
        }

        if (!is_string($foreignId)) {
            $this->throwError("foreignId type is wrong.");
        }

        $relation->relateById($foreignId);

        return true;
    }
}
