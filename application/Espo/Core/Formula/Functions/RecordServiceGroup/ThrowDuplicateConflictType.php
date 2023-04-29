<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Formula\Functions\RecordServiceGroup;

use Espo\Core\Di\EntityManagerAware;
use Espo\Core\Di\EntityManagerSetter;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\ConflictSilent;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Utils\Json;

class ThrowDuplicateConflictType extends BaseFunction implements EntityManagerAware
{
    use EntityManagerSetter;

    /**
     * @inheritDoc
     * @throws Conflict
     */
    public function process(ArgumentList $args)
    {
        if (empty($this->getVariables()->__isRecordService)) {
            $this->throwError("Can be called only from API script.");
        }

        if (count($args) < 1) {
            $this->throwTooFewArguments(1);
        }

        $ids = $this->evaluate($args[0]);

        if (is_string($ids)) {
            $ids = [$ids];
        }

        if (!is_array($ids)) {
            $this->throwBadArgumentType(1);
        }

        $entityType = $this->getEntity()->getEntityType();

        $list = [];

        foreach ($ids as $id) {
            $entity = $this->entityManager->getEntityById($entityType, $id);

            $name = $entity ? $entity->get('name') : $id;


            $list[] = (object) ['id' => $id, 'name' => $name];
        }

        throw ConflictSilent::createWithBody('duplicate', Json::encode($list));
    }
}
