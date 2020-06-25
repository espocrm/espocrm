<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Exceptions\Error;

use Espo\Core\Di;

class RelateType extends \Espo\Core\Formula\Functions\FunctionBase implements
    Di\EntityManagerAware
{
    use Di\EntityManagerSetter;

    public function process(\StdClass $item)
    {
        $args = $item->value ?? [];

        if (count($args) < 4) throw new Error("record\\relate: Not enough arguments.");

        $entityType = $this->evaluate($args[0]);
        $id = $this->evaluate($args[1]);
        $link = $this->evaluate($item->value[2]);
        $foreignId = $this->evaluate($item->value[3]);

        if (!$entityType) throw new Error("record\\relate: Empty entityType.");
        if (!$id) return null;
        if (!$link) throw new Error("record\\relate: Empty link.");
        if (!$foreignId) return null;

        $em = $this->entityManager;

        if (!$em->hasRepository($entityType)) throw new Error("record\\relate: Repository does not exist.");

        $entity = $em->getEntity($entityType, $id);
        if (!$entity) return null;

        if (is_array($foreignId)) {
            foreach ($foreignId as $itemId) {
                $em->getRepository($entityType)->relate($entity, $link, $itemId);
            }
            return true;

        } else if (is_string($foreignId)) {
            if ($em->getRepository($entityType)->isRelated($entity, $link, $foreignId)) {
                return true;
            }
            return $em->getRepository($entityType)->relate($entity, $link, $foreignId);
        } else {
            throw new Error("record\\relate: foreignId type is wrong.");
        }
    }
}
