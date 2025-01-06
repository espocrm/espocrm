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

namespace Espo\Core\Formula\Functions\RecordServiceGroup;

use Espo\Core\Di\EntityManagerAware;
use Espo\Core\Di\EntityManagerSetter;
use Espo\Core\Di\RecordServiceContainerAware;
use Espo\Core\Di\RecordServiceContainerSetter;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\ConflictSilent;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Json;

class ThrowDuplicateConflictType extends BaseFunction implements
    EntityManagerAware,
    RecordServiceContainerAware
{
    use EntityManagerSetter;
    use RecordServiceContainerSetter;

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

            if ($entity) {
                $this->recordServiceContainer->get($entityType)->prepareEntityForOutput($entity);
            }

            if (!$entity) {
                $entity = $this->entityManager->getNewEntity($entityType);
                $entity->set(Field::NAME, $id);
            }

            $list[] = $entity->getValueMap();
        }

        throw ConflictSilent::createWithBody('duplicate', Json::encode($list));
    }
}
