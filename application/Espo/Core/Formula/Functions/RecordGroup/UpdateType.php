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

class UpdateType extends \Espo\Core\Formula\Functions\Base
{
    protected function init()
    {
        $this->addDependency('entityManager');
    }

    public function process(\StdClass $item)
    {
        $args = $this->fetchArguments($item);

        if (count($args) < 2) throw new Error("Formula record\update: Too few arguments.");
        $entityType = $args[0];
        $id = $args[1];

        if (!is_string($entityType)) throw new Error("Formula record\update: First argument should be a string.");
        if (!is_string($id)) throw new Error("Formula record\update: Second argument should be a string.");

        $data = [];

        $i = 2;
        while ($i < count($args) - 1) {
            $attribute = $args[$i];
            if (!is_string($entityType)) throw new Error("Formula record\update: Attribute should be a string.");
            $value = $args[$i + 1];
            $data[$attribute] = $value;
            $i = $i + 2;
        }

        $em = $this->getInjection('entityManager');

        $entity = $em->getEntity($entityType, $id);

        if ($entity) {
            $entity->set($data);
            if ($em->saveEntity($entity)) {
                return true;
            }
        }

        return false;
    }
}
