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

use Espo\Core\Field\DateTime;

use InvalidArgumentException;

class CalendarUtility
{
    private const MAX_FIND_DAYS_PERIOD = 200;
    private const MAX_YEARS_PERIOD = 2;

    private Calendar $calendar;
    private Extractor $extractor;

    public function __construct(Calendar $calendar, Extractor $extractor)
    {
        $this->calendar = $calendar;
        $this->extractor = $extractor;
    }

    public function isWorkingDay(DateTime $time): bool
    {
        $point = $time
            ->withTimezone($this->calendar->getTimezone())
            ->withTime(0, 0, 0);

        return $this->extractor->extractAllDay($this->calendar, $point, $point->modify('+0 seconds')) !== [];
    }

    public function hasWorkingTime(DateTime $from, DateTime $to): bool
    {
        return $this->extractor->extract($this->calendar, $from, $to) !== [];
    }

    public function getSummedWorkingHours(DateTime $from, DateTime $to): float
    {
        $ranges = $this->extractor->extract($this->calendar, $from, $to);

        $sum = 0.0;

        foreach ($ranges as $range) {
            $sum += ($range[1]->getTimestamp() - $range[0]->getTimestamp()) / 3600;
        }

        return $sum;
    }

    public function getWorkingDays(DateTime $from, DateTime $to): int
    {
        $ranges = $this->extractor->extractAllDay($this->calendar, $from, $to);

        return count($ranges);
    }

    public function findClosestWorkingTime(DateTime $time): ?DateTime
    {
        $step = 10;
        $max = $time->modify('+' . self::MAX_FIND_DAYS_PERIOD . ' days');

        $point = $time;

        while ($point->isLessThan($max)) {
            $from = $point;
            $to = $point->modify('+' . $step . ' days');

            $ranges = $this->extractor->extract($this->calendar, $from, $to);

            if (count($ranges)) {
                return $time->isGreaterThan($ranges[0][0]) ?
                    $time :
                    $ranges[0][0];
            }

            $point = $to;
        }

        return null;
    }

    /**
     * @param int<1, max> $days
     */
    public function addWorkingDays(DateTime $time, int $days): ?DateTime
    {
        /** @var int $days */

        if ($days <= 0) {
            throw new InvalidArgumentException("Can't add non-positive days number.");
        }

        $step = max(30, $days);
        $max = $time->modify('+' . self::MAX_YEARS_PERIOD . ' years');

        $point = $time
            ->withTimezone($this->calendar->getTimezone())
            ->modify('+1 day')
            ->withTime(0, 0, 0);

        $counter = 0;

        while ($point->isLessThan($max)) {
            $from = $point;
            $to = $point->modify('+' . $step . ' days');

            $ranges = $this->extractor->extractAllDay($this->calendar, $from, $to);

            foreach ($ranges as $range) {
                $counter++;

                if ($counter === $days) {
                    return $range[0];
                }
            }

            $point = $to;
        }

        return null;
    }
}
