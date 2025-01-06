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

namespace Espo\Core\Formula\Functions\ExtGroup\WorkingTimeGroup;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\BadArgumentValue;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Functions\BaseFunction;

use Espo\Entities\Team;
use Espo\Entities\User;

use Espo\Core\Di;
use Espo\ORM\Entity;
use Espo\Tools\WorkingTime\Calendar;
use Espo\Tools\WorkingTime\CalendarFactory;
use Espo\Tools\WorkingTime\CalendarUtility;

abstract class Base extends BaseFunction implements

    Di\EntityManagerAware,
    Di\InjectableFactoryAware
{
    use Di\EntityManagerSetter;
    use Di\InjectableFactorySetter;

    private function getCalendarFactory(): CalendarFactory
    {
        return $this->injectableFactory->create(CalendarFactory::class);
    }

    protected function createCalendarUtility(Calendar $calendar): CalendarUtility
    {
        return $this->injectableFactory->createWithBinding(
            CalendarUtility::class,
            BindingContainerBuilder::create()
                ->bindInstance(Calendar::class, $calendar)
                ->build()
        );
    }

    /**
     * @param mixed[] $evaluatedArgs
     * @throws BadArgumentType
     * @throws BadArgumentValue
     * @throws Error
     */
    protected function createCalendar(array $evaluatedArgs, int $argumentPosition = 1): Calendar
    {
        $target = $this->obtainTarget($evaluatedArgs, $argumentPosition);

        if ($target instanceof User) {
            return $this->getCalendarFactory()->createForUser($target);
        }

        if ($target instanceof Team) {
            return $this->getCalendarFactory()->createForTeam($target);
        }

        return $this->getCalendarFactory()->createGlobal();
    }

    /**
     * @param mixed[] $evaluatedArgs
     * @throws BadArgumentType
     * @throws BadArgumentValue
     * @throws Error
     */
    private function obtainTarget(array $evaluatedArgs, int $argumentPosition = 1): ?Entity
    {
        if (count($evaluatedArgs) < $argumentPosition + 2) {
            return null;
        }

        $entityType = $evaluatedArgs[$argumentPosition];
        $entityId = $evaluatedArgs[$argumentPosition + 1];

        if (!is_string($entityType)) {
            $this->throwBadArgumentType($argumentPosition + 1, 'string');
        }

        if (!is_string($entityId)) {
            $this->throwBadArgumentType($argumentPosition + 2, 'string');
        }

        if (!in_array($entityType, [User::ENTITY_TYPE, Team::ENTITY_TYPE])) {
            $this->throwBadArgumentValue($argumentPosition + 1);
        }

        $entity = $this->entityManager->getEntityById($entityType, $entityId);

        if (!$entity) {
            $this->throwError("Entity {$entityType} {$entityId} not found.");
        }

        return $entity;
    }
}
