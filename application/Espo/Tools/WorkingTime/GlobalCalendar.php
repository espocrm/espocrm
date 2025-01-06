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
use Espo\Core\Utils\Config;
use Espo\Entities\WorkingTimeCalendar;
use Espo\ORM\EntityManager;
use Espo\Tools\WorkingTime\Calendar\WorkingWeekday;
use Espo\Tools\WorkingTime\Calendar\WorkingDate;
use Espo\Core\Field\Date;

use DateTimeZone;
use Exception;
use RuntimeException;

class GlobalCalendar implements Calendar
{
    private ?WorkingTimeCalendar $workingTimeCalendar = null;
    private ?SpecificCalendar $specificCalendar = null;

    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private InjectableFactory $injectableFactory,
        private Config\ApplicationConfig $applicationConfig,
    ) {
        $this->initDefault();

        if ($this->workingTimeCalendar) {
            $this->specificCalendar = $this->injectableFactory->createWithBinding(
                SpecificCalendar::class,
                BindingContainerBuilder::create()
                    ->bindInstance(WorkingTimeCalendar::class, $this->workingTimeCalendar)
                    ->build()
            );
        }
    }

    private function initDefault(): void
    {
        $id = $this->config->get('workingTimeCalendarId');

        if (!$id) {
            return;
        }

        $this->workingTimeCalendar = $this->entityManager->getEntityById(WorkingTimeCalendar::ENTITY_TYPE, $id);
    }

    /** @noinspection PhpUnused */
    public function isAvailable(): bool
    {
        if ($this->specificCalendar) {
            return $this->specificCalendar->isAvailable();
        }

        return false;
    }

    public function getTimezone(): DateTimeZone
    {
        if ($this->specificCalendar) {
            return $this->specificCalendar->getTimezone();
        }

        try {
            return new DateTimeZone($this->applicationConfig->getTimeZone());
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * @return WorkingWeekday[]
     */
    public function getWorkingWeekdays(): array
    {
        if ($this->specificCalendar) {
            return $this->specificCalendar->getWorkingWeekdays();
        }

        return [];
    }

    /**
     * @return WorkingDate[]
     */
    public function getNonWorkingDates(Date $from, Date $to): array
    {
        if ($this->specificCalendar) {
            return $this->specificCalendar->getNonWorkingDates($from, $to);
        }

        return [];
    }

    /**
     * @return WorkingDate[]
     */
    public function getWorkingDates(Date $from, Date $to): array
    {
        if ($this->specificCalendar) {
            return $this->specificCalendar->getWorkingDates($from, $to);
        }

        return [];
    }
}
