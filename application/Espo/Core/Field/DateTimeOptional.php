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

namespace Espo\Core\Field;

use Espo\Core\Field\DateTime;
use Espo\Core\Field\Date;
use Espo\Core\Field\DateTime\DateTimeable;

use DateTimeImmutable;
use DateTimeInterface;
use DateInterval;
use DateTimeZone;
use RuntimeException;

/**
 * A date-time or date. Immutable.
 */
class DateTimeOptional implements DateTimeable
{
    private $dateTimeValue = null;

    private $dateValue = null;

    private const SYSTEM_FORMAT = 'Y-m-d H:i:s';

    private const SYSTEM_FORMAT_DATE = 'Y-m-d';

    public function __construct(string $value)
    {
        if (self::isStringDateTime($value)) {
            $this->dateTimeValue = new DateTime($value);
        }
        else {
            $this->dateValue = new Date($value);
        }
    }

    /**
     * Create from a string with a date-time in `Y-m-d H:i:s` format or date in `Y-m-d`.
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Create from a string with a date-time in `Y-m-d H:i:s` format.
     */
    public static function fromDateTimeString(string $value): self
    {
        if (!self::isStringDateTime($value)) {
            throw new RuntimeException("Bad value.");
        }

        return self::fromString($value);
    }

    /**
     * Get a string value in `Y-m-d H:i:s` format.
     */
    public function getString(): string
    {
        return $this->getActualValue()->getString();
    }

    /**
     * Get DateTimeImmutable.
     */
    public function getDateTime(): DateTimeImmutable
    {
        return $this->getActualValue()->getDateTime();
    }

    /**
     * Get a timestamp.
     */
    public function getTimestamp(): int
    {
        return $this->getActualValue()->getDateTime()->getTimestamp();
    }

    /**
     * Get a year.
     */
    public function getYear(): int
    {
        return $this->getActualValue()->getYear();
    }

    /**
     * Get a month.
     */
    public function getMonth(): int
    {
        return $this->getActualValue()->getMonth();
    }

    /**
     * Get a day (of month).
     */
    public function getDay(): int
    {
        return $this->getActualValue()->getDay();
    }

    /**
     * Get a day of week. 0 (for Sunday) through 6 (for Saturday).
     */
    public function getDayOfWeek(): int
    {
        return $this->getActualValue()->getDayOfWeek();
    }

    /**
     * Get a hour.
     */
    public function getHour(): int
    {
        if ($this->isAllDay()) {
            return 0;
        }

        return $this->getActualValue()->getHour();
    }

    /**
     * Get a minute.
     */
    public function getMinute(): int
    {
        if ($this->isAllDay()) {
            return 0;
        }

        return $this->getActualValue()->getMinute();
    }

    /**
     * Get a second.
     */
    public function getSecond(): int
    {
        if ($this->isAllDay()) {
            return 0;
        }

        return $this->getActualValue()->getSecond();
    }

    /**
     * Whether is all-day (no time part).
     */
    public function isAllDay(): bool
    {
        return $this->dateValue !== null;
    }

    /**
     * Get a timezone.
     */
    public function getTimezone(): DateTimeZone
    {
        return $this->getDateTime()->getTimezone();
    }

    /**
     * @return Date|DateTime
     */
    private function getActualValue(): object
    {
        return $this->dateValue ?? $this->dateTimeValue;
    }

    /**
     * Clones and apply a timezone. Non-all-day value is created.
     */
    public function withTimezone(DateTimeZone $timezone): self
    {
        if ($this->isAllDay()) {
            $dateTime = $this->getActualValue()->getDateTime()->setTimezone($timezone);

            return self::fromDateTime($dateTime);
        }

        $dateTime = $this->getActualValue()->withTimezone($timezone)->getDateTime();

        return self::fromDateTime($dateTime);
    }

    /**
     * Clones and modifies.
     */
    public function modify(string $modifier): self
    {
        if ($this->isAllDay()) {
            return self::fromDateTimeAllDay(
                $this->dateValue->modify($modifier)->getDateTime()
            );
        }

        return self::fromDateTime(
            $this->dateTimeValue->modify($modifier)->getDateTime()
        );
    }

    /**
     * Clones and adds an interval.
     */
    public function add(DateInterval $interval): self
    {
        if ($this->isAllDay()) {
            return self::fromDateTimeAllDay(
                $this->dateValue->add($interval)->getDateTime()
            );
        }

        return self::fromDateTime(
            $this->dateTimeValue->add($interval)->getDateTime()
        );
    }

    /**
     * Clones and subtracts an interval.
     */
    public function subtract(DateInterval $interval): self
    {
        if ($this->isAllDay()) {
            return self::fromDateTimeAllDay(
                $this->dateValue->subtract($interval)->getDateTime()
            );
        }

        return self::fromDateTime(
            $this->dateTimeValue->subtract($interval)->getDateTime()
        );
    }

    /**
     * A difference between another object (date or date-time) and self.
     */
    public function diff(DateTimeable $other): DateInterval
    {
        return $this->getDateTime()->diff($other->getDateTime());
    }

    /**
     * Create a current time.
     */
    public static function createNow(): self
    {
        return self::fromDateTime(new DateTimeImmutable());
    }

    /**
     * Create a today.
     */
    public static function createToday(?DateTimeZone $timezone = null): self
    {
        $now = new DateTimeImmutable();

        if ($timezone) {
            $now = $now->setTimezone($timezone);
        }

        return self::fromDateTimeAllDay($now);
    }

    /**
     * Create from a string with a date in `Y-m-d` format.
     */
    public static function fromDateString(string $value): self
    {
        if (self::isStringDateTime($value)) {
            throw new RuntimeException("Bad value.");
        }

        return self::fromString($value);
    }

    /**
     * Create from a DateTimeInterface.
     */
    public static function fromDateTime(DateTimeInterface $dateTime): self
    {
        $value = $dateTime->format(self::SYSTEM_FORMAT);

        $utcValue = DateTimeImmutable
             ::createFromFormat(self::SYSTEM_FORMAT, $value, $dateTime->getTimezone())
             ->setTimezone(new DateTimeZone('UTC'))
             ->format(self::SYSTEM_FORMAT);

        $obj = self::fromString($utcValue);

        $obj->dateTimeValue = $obj->dateTimeValue->withTimezone($dateTime->getTimezone());

        return $obj;
    }

    /**
     * Create all-day from a DateTimeInterface.
     */
    public static function fromDateTimeAllDay(DateTimeInterface $dateTime): self
    {
        $value = $dateTime->format(self::SYSTEM_FORMAT_DATE);

        return new self($value);
    }

    private static function isStringDateTime(string $value): bool
    {
        if (strlen($value) > 10) {
            return true;
        }

        return false;
    }
}
