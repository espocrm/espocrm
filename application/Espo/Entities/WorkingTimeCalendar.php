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

namespace Espo\Entities;

use Espo\Core\ORM\Entity;
use Espo\Tools\WorkingTime\Calendar\WorkingWeekday;
use Espo\Tools\WorkingTime\Calendar\TimeRange;
use Espo\Tools\WorkingTime\Calendar\Time;

use DateTimeZone;

class WorkingTimeCalendar extends Entity
{
    public const ENTITY_TYPE = 'WorkingTimeCalendar';

    public function getTimeZone(): ?DateTimeZone
    {
        $string = $this->get('timeZone');

        if (!$string) {
            return null;
        }

        return new DateTimeZone($string);
    }

    /**
     * @return TimeRange[]
     */
    public function getTimeRanges(): array
    {
        return self::convertRanges($this->get('timeRanges'));
    }

    /**
     * @param int<0,6> $weekday
     */
    private function hasCustomWeekdayRanges(int $weekday): bool
    {
        $attribute = 'weekday' . $weekday . 'TimeRanges';

        return $this->get($attribute) !== null && $this->get($attribute) !== [];
    }

    /**
     * @param int<0,6> $weekday
     * @return TimeRange[]
     */
    private function getWeekdayTimeRanges(int $weekday): array
    {
        $attribute = 'weekday' . $weekday . 'TimeRanges';

        $raw = $this->hasCustomWeekdayRanges($weekday) ?
            $this->get($attribute) :
            $this->get('timeRanges');

        return self::convertRanges($raw);
    }

    /**
     * @return WorkingWeekday[]
     */
    public function getWorkingWeekdays(): array
    {
        $list = [];

        for ($i = 0; $i <= 6; $i++) {
            if (!$this->get('weekday' . $i)) {
                continue;
            }

            $list[] = new WorkingWeekday($i, $this->getWeekdayTimeRanges($i));
        }

        return $list;
    }

    /**
     * @param array{string, string}[] $ranges
     * @return TimeRange[]
     */
    private static function convertRanges(array $ranges): array
    {
        $list = [];

        foreach ($ranges as $range) {
            $list[] = new TimeRange(
                self::convertTime($range[0]),
                self::convertTime($range[1])
            );
        }

        return $list;
    }

    private static function convertTime(string $time): Time
    {
        /** @var int<0, 23> $h */
        $h = (int) explode(':', $time)[0];
        /** @var int<0, 59> $m */
        $m = (int) explode(':', $time)[1];

        return new Time($h, $m);
    }
}
