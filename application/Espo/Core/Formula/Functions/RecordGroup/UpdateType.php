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

class UpdateType extends BaseFunction implements
    Di\EntityManagerAware
{
    use Di\EntityManagerSetter;

    public function process(ArgumentList $args)
    {
        if (count($args) < 2) {
            $this->throwTooFewArguments(2);
        }

        $args = $this->evaluate($args);

        $entityType = $args[0];
        $id = $args[1];

        if (!is_string($entityType)) {
            $this->throwBadArgumentType(1, 'string');
        }
        if (!is_string($id)) {
            $this->throwBadArgumentType(2, 'string');
        }

        $data = [];

        $i = 2;
        while ($i < count($args) - 1) {
            $attribute = $args[$i];
            if (!is_string($entityType)) {
                $this->throwBadArgumentType($i + 1, 'string');
            }
            $value = $args[$i + 1];
            $data[$attribute] = $value;
            $i = $i + 2;
        }

        $em = $this->entityManager;

        $entity = $em->getEntity($entityType, $id);

        if (!$entity) {
            return false;
        }

        $entity->set($data);
        $em->saveEntity($entity);

        return true;
    }
}
