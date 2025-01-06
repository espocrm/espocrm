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

namespace Espo\Tools\WorkingTime;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\InjectableFactory;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Entities\WorkingTimeCalendar;

class CalendarFactory
{
    public function __construct(private InjectableFactory $injectableFactory)
    {}

    public function createGlobal(): GlobalCalendar
    {
        return $this->injectableFactory->create(GlobalCalendar::class);
    }

    public function createForUser(User $user): UserCalendar
    {
        $binding = BindingContainerBuilder::create()
            ->bindInstance(User::class, $user)
            ->build();

        return $this->injectableFactory->createWithBinding(UserCalendar::class, $binding);
    }

    public function createForTeam(Team $team): TeamCalendar
    {
        $binding = BindingContainerBuilder::create()
            ->bindInstance(Team::class, $team)
            ->build();

        return $this->injectableFactory->createWithBinding(TeamCalendar::class, $binding);
    }

    /**
     * @since 8.4.0
     */
    public function create(WorkingTimeCalendar $calendar): Calendar
    {
        $binding = BindingContainerBuilder::create()
            ->bindInstance(WorkingTimeCalendar::class, $calendar)
            ->build();

        return $this->injectableFactory->createWithBinding(SpecificCalendar::class, $binding);
    }
}
