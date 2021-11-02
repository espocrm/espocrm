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

namespace Espo\Core\Formula\Functions\RecordGroup;

use Espo\Core\Formula\{
    Functions\BaseFunction,
    ArgumentList,
};

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

        $entity = $em->getEntity($entityType, $id);

        if (!$entity) {
            return null;
        }

        if (is_array($foreignId)) {
            foreach ($foreignId as $itemId) {
                $em->getRDBRepository($entityType)->relate($entity, $link, $itemId);
            }

            return true;
        }

        if (is_string($foreignId)) {
            if ($em->getRDBRepository($entityType)->isRelated($entity, $link, $foreignId)) {
                return true;
            }

            return $em->getRDBRepository($entityType)->relate($entity, $link, $foreignId);
        }

        $this->throwError("foreignId type is wrong.");
    }
}
