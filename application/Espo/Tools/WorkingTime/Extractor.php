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

use DateTimeZone;
use Espo\Tools\WorkingTime\Calendar\WorkingWeekday;
use Espo\Tools\WorkingTime\Calendar\WorkingDate;
use Espo\Tools\WorkingTime\Calendar\HavingRanges;

use Espo\Core\Field\DateTime;
use Espo\Core\Field\Date;

class Extractor
{
    /**
     * @return array{DateTime,DateTime}[]
     */
    public function extract(Calendar $calendar, DateTime $from, DateTime $to): array
    {
        $pointer = $from->withTimezone($calendar->getTimezone());

        $fromDate = Date::fromDateTime($from->modify('-1 day')->toDateTime());
        $toDate = Date::fromDateTime($from->modify('+1 day')->toDateTime());

        $workingDates = $calendar->getWorkingDates($fromDate, $toDate);
        $nonWorkingDates = $calendar->getNonWorkingDates($fromDate, $toDate);
        $workingWeekdays = $calendar->getWorkingWeekdays();

        $list = [];

        $end = $to->withTimezone($calendar->getTimezone())->withTime(23, 59, 59);

        while ($pointer->toTimestamp() < $end->toTimestamp()) {
            $list = array_merge(
                $list,
                $this->extractIteration(
                    $pointer,
                    $workingDates,
                    $nonWorkingDates,
                    $workingWeekdays,
                ),
            );

            $pointer = $pointer->modify('+1 day');
        }

        return $this->trim($list, $from, $to, $calendar);
    }

    /**
     * @param array{DateTime,DateTime}[] $list
     * @return array{DateTime,DateTime}[]
     */
    private function trim(array $list, DateTime $from, DateTime $to, Calendar $calendar): array
    {
        $wasUnset = false;

        foreach ($list as $i => $pair) {
            if ($from->isLessThan($pair[0]) || $from->isEqualTo($pair[0])) {
                break;
            }

            if ($from->isGreaterThan($pair[1]) || $from->isEqualTo($pair[1])) {
                unset($list[$i]);
                $wasUnset = true;

                continue;
            }

            if ($from->isLessThan($pair[1])) {
                $list[$i][0] = $from->withTimezone($calendar->getTimezone());
            }

            break;
        }

        if ($wasUnset) {
            $list = array_values($list);

            $wasUnset = false;
        }

        for ($i = count($list) - 1; $i >= 0; $i--) {
            $pair = $list[$i];

            if ($to->isGreaterThan($pair[1]) || $to->isEqualTo($pair[1])) {
                break;
            }

            if ($to->isLessThan($pair[0]) || $to->isEqualTo($pair[0])) {
                unset($list[$i]);
                $wasUnset = true;

                continue;
            }

            if ($to->isGreaterThan($pair[0])) {
                $list[$i][1] = $to->withTimezone($calendar->getTimezone());
            }

            break;
        }

        if ($wasUnset) {
            $list = array_values($list);
        }

        return $list;
    }

    /**
     * @return array{DateTime,DateTime}[]
     */
    public function extractAllDay(Calendar $calendar,
        DateTime $from,
        DateTime $to,
        ?DateTimeZone $timezone = null
    ): array {

        $timezone ??= $calendar->getTimezone();

        $pointer = $from
            ->withTimezone($timezone)
            ->withTime(0, 0, 0);

        $fromDate = Date::fromDateTime($from->modify('-1 day')->toDateTime());
        $toDate = Date::fromDateTime($from->modify('+1 day')->toDateTime());

        $workingDates = $calendar->getWorkingDates($fromDate, $toDate);
        $nonWorkingDates = $calendar->getNonWorkingDates($fromDate, $toDate);
        $workingWeekdays = $calendar->getWorkingWeekdays();

        $list = [];

        $end = $to->withTimezone($timezone)->withTime(23, 59, 59);

        while ($pointer->toTimestamp() < $end->toTimestamp()) {
            $isWorkingDay = $this->isWorkingDay(
                $pointer,
                $workingDates,
                $nonWorkingDates,
                $workingWeekdays,
            );

            $nextPointer = $pointer->modify('+1 day');

            if ($isWorkingDay) {
                $list[] = [
                    $pointer,
                    $nextPointer
                ];
            }

            $pointer = $nextPointer;
        }

        return $list;
    }

    /**
     * @return array{DateTime,DateTime}[]
     */
    public function extractInversion(Calendar $calendar, DateTime $from, DateTime $to): array
    {
        $list = $this->extract($calendar, $from, $to);

        $timezone = $calendar->getTimezone();

        if ($list === []) {
            return [
                [
                    $from->withTimezone($timezone),
                    $to->withTimezone($timezone),
                ]
            ];
        }

        $listInverted = [];

        $count = count($list);

        $listInverted[] = [
            $from->withTimezone($calendar->getTimezone()),
            $list[0][0]
        ];

        for ($i = 0; $i < $count - 1; $i++) {
            $item1 = $list[$i];
            $item2 = $list[$i + 1];

            $listInverted[] = [
                $item1[1],
                $item2[0]
            ];
        }

        $listInverted[] = [
            $list[$count - 1][1],
            $to->withTimezone($timezone)
        ];

        return $listInverted;
    }

    /**
     * @return array{DateTime,DateTime}[]
     */
    public function extractAllDayInversion(
        Calendar $calendar,
        DateTime $from,
        DateTime $to,
        ?DateTimeZone $timezone = null
    ): array {

        $list = $this->extractAllDay($calendar, $from, $to, $timezone);

        if ($list === []) {
            return [[$from, $to]];
        }

        $count = count($list);

        $listInverted = [];

        $listInverted[] = [
            $from,
            $list[0][0]
        ];

        for ($i = 0; $i < $count - 1; $i++) {
            $item1 = $list[$i];
            $item2 = $list[$i + 1];

            $listInverted[] = [
                $item1[1],
                $item2[0]
            ];
        }

        $listInverted[] = [
            $list[$count - 1][1],
            $to
        ];

        return $listInverted;
    }

    /**
     * @param WorkingDate[] $workingDates
     * @param WorkingDate[] $nonWorkingDates
     * @param WorkingWeekday[] $workingWeekdays
     */
    private function isWorkingDay(
        DateTime $pointer,
        array $workingDates,
        array $nonWorkingDates,
        array $workingWeekdays
    ): bool {

        if ($this->findInDateList($pointer, $nonWorkingDates)) {
            return false;
        }

        $day1 = $this->findInDateList($pointer, $workingDates);

        if ($day1) {
            return true;
        }

        $day2 = $this->findInWeekdayList($pointer, $workingWeekdays);

        if ($day2) {
            return true;
        }

        return false;
    }

    /**
     * @param WorkingDate[] $workingDates
     * @param WorkingDate[] $nonWorkingDates
     * @param WorkingWeekday[] $workingWeekdays
     * @return array{DateTime,DateTime}[]
     */
    private function extractIteration(
        DateTime $pointer,
        array $workingDates,
        array $nonWorkingDates,
        array $workingWeekdays
    ): array {

        if ($this->findInDateList($pointer, $nonWorkingDates)) {
            return [];
        }

        $day1 = $this->findInDateList($pointer, $workingDates);

        if ($day1) {
            return $this->extractFromDay($pointer, $day1);
        }

        $day2 = $this->findInWeekdayList($pointer, $workingWeekdays);

        if ($day2) {
            return $this->extractFromDay($pointer, $day2);
        }

        return [];
    }

    /**
     * @param DateTime $pointer
     * @param WorkingDate[] $dateList
     */
    private function findInDateList(DateTime $pointer, array $dateList): ?WorkingDate
    {
        $day = $pointer->getDay();
        $month = $pointer->getMonth();
        $year = $pointer->getYear();

        foreach ($dateList as $item) {
            $date = $item->getDate();

            if (
                $date->getDay() === $day &&
                $date->getMonth() === $month &&
                $date->getYear() === $year
            ) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param DateTime $pointer
     * @param WorkingWeekday[] $dayList
     */
    private function findInWeekdayList(DateTime $pointer, array $dayList): ?WorkingWeekday
    {
        $dow = $pointer->getDayOfWeek();

        foreach ($dayList as $item) {
            if ($item->getWeekday() === $dow) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return array{DateTime,DateTime}[]
     */
    private function extractFromDay(DateTime $dateTime, HavingRanges $day): array
    {
        $pointer = $dateTime->toDateTime();

        $list = [];

        foreach ($day->getRanges() as $range) {
            $start = $range->getStart();
            $end = $range->getEnd();

            $startDateTime = DateTime::fromDateTime(
                $pointer->setTime($start->getHour(), $start->getMinute())
            );

            $endDateTime = DateTime::fromDateTime(
                $pointer->setTime($end->getHour(), $end->getMinute())
            );

            if ($end->getHour() === 0 && $end->getMinute() === 0) {
                $endDateTime = $endDateTime->addDays(1);
            }

            $list[] = [$startDateTime, $endDateTime];
        }

        return $list;
    }
}
