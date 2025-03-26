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

namespace Espo\Core\Field;

use Espo\Core\Field\DateTime\DateTimeable;

use DateTimeImmutable;
use DateTimeInterface;
use DateInterval;
use DateTimeZone;
use RuntimeException;

/**
 * A date-time value object. Immutable.
 */
class DateTime implements DateTimeable
{
    private string $value;
    private DateTimeImmutable $dateTime;

    private const SYSTEM_FORMAT = 'Y-m-d H:i:s';

    public function __construct(string $value)
    {
        if (!$value) {
            throw new RuntimeException("Empty value.");
        }

        $normValue = strlen($value) === 16 ? $value . ':00' : $value;

        $this->value = $normValue;

        $parsedValue = DateTimeImmutable::createFromFormat(
            self::SYSTEM_FORMAT,
            $normValue,
            new DateTimeZone('UTC')
        );

        if ($parsedValue === false) {
            throw new RuntimeException("Bad value.");
        }

        $this->dateTime = $parsedValue;

        if ($this->value !== $this->dateTime->format(self::SYSTEM_FORMAT)) {
            throw new RuntimeException("Bad value.");
        }
    }

    /**
     * Get a string value in `Y-m-d H:i:s` format.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Get DateTimeImmutable.
     */
    public function toDateTime(): DateTimeImmutable
    {
        return $this->dateTime;
    }

    /**
     * Get a timestamp.
     */
    public function toTimestamp(): int
    {
        return $this->dateTime->getTimestamp();
    }

    /**
     * Get a year.
     */
    public function getYear(): int
    {
        return (int) $this->dateTime->format('Y');
    }

    /**
     * Get a month.
     */
    public function getMonth(): int
    {
        return (int) $this->dateTime->format('n');
    }

    /**
     * Get a day (of month).
     */
    public function getDay(): int
    {
        return (int) $this->dateTime->format('j');
    }

    /**
     * Get a day of week. 0 (for Sunday) through 6 (for Saturday).
     */
    public function getDayOfWeek(): int
    {
        return (int) $this->dateTime->format('w');
    }

    /**
     * Get a hour.
     */
    public function getHour(): int
    {
        return (int) $this->dateTime->format('G');
    }

    /**
     * Get a minute.
     */
    public function getMinute(): int
    {
        return (int) $this->dateTime->format('i');
    }

    /**
     * Get a second.
     */
    public function getSecond(): int
    {
        return (int) $this->dateTime->format('s');
    }

    /**
     * Get a timezone.
     */
    public function getTimezone(): DateTimeZone
    {
        return $this->dateTime->getTimezone();
    }

    /**
     * Clones and modifies.
     */
    public function modify(string $modifier): self
    {
        /**
         * @var DateTimeImmutable|false $dateTime
         */
        $dateTime = $this->dateTime->modify($modifier);

        if (!$dateTime) {
            throw new RuntimeException("Modify failure.");
        }

        return self::fromDateTime($dateTime);
    }

    /**
     * Clones and adds an interval.
     */
    public function add(DateInterval $interval): self
    {
        $dateTime = $this->dateTime->add($interval);

        return self::fromDateTime($dateTime);
    }

    /**
     * Clones and subtracts an interval.
     */
    public function subtract(DateInterval $interval): self
    {
        $dateTime = $this->dateTime->sub($interval);

        return self::fromDateTime($dateTime);
    }

    /**
     * Add days.
     */
    public function addDays(int $days): self
    {
        $modifier = ($days >= 0 ? '+' : '-') . abs($days) . ' days';

        return $this->modify($modifier);
    }

    /**
     * Add months.
     */
    public function addMonths(int $months): self
    {
        $modifier = ($months >= 0 ? '+' : '-') . abs($months) . ' months';

        return $this->modify($modifier);
    }

    /**
     * Add years.
     */
    public function addYears(int $years): self
    {
        $modifier = ($years >= 0 ? '+' : '-') . abs($years) . ' years';

        return $this->modify($modifier);
    }

    /**
     * Add hours.
     */
    public function addHours(int $hours): self
    {
        $modifier = ($hours >= 0 ? '+' : '-') . abs($hours) . ' hours';

        return $this->modify($modifier);
    }

    /**
     * Add minutes.
     */
    public function addMinutes(int $minutes): self
    {
        $modifier = ($minutes >= 0 ? '+' : '-') . abs($minutes) . ' minutes';

        return $this->modify($modifier);
    }

    /**
     * Add seconds.
     */
    public function addSeconds(int $seconds): self
    {
        $modifier = ($seconds >= 0 ? '+' : '-') . abs($seconds) . ' seconds';

        return $this->modify($modifier);
    }

    /**
     * A difference between another object (date or date-time) and self.
     */
    public function diff(DateTimeable $other): DateInterval
    {
        return $this->toDateTime()->diff($other->toDateTime());
    }

    /**
     * Clones and apply a timezone.
     */
    public function withTimezone(DateTimeZone $timezone): self
    {
        $dateTime = $this->dateTime->setTimezone($timezone);

        return self::fromDateTime($dateTime);
    }

    /**
     * Clones and sets time. Null preserves a current value.
     */
    public function withTime(?int $hour, ?int $minute, ?int $second = 0): self
    {
        $dateTime = $this->dateTime->setTime(
            $hour ?? $this->getHour(),
            $minute ?? $this->getMinute(),
            $second ?? $this->getSecond()
        );

        return self::fromDateTime($dateTime);
    }

    /**
     * Whether greater than a given value.
     */
    public function isGreaterThan(DateTimeable $other): bool
    {
        return $this->toDateTime() > $other->toDateTime();
    }

    /**
     * Whether less than a given value.
     */
    public function isLessThan(DateTimeable $other): bool
    {
        return $this->toDateTime() < $other->toDateTime();
    }

    /**
     * Whether equals to a given value.
     */
    public function isEqualTo(DateTimeable $other): bool
    {
        return $this->toDateTime() == $other->toDateTime();
    }

    /**
     * Whether less than or equals to a given value.
     * @since 9.0.0
     */
    public function isLessThanOrEqualTo(DateTimeable $other): bool
    {
        return $this->isLessThan($other) || $this->isEqualTo($other);
    }

    /**
     * Whether greater than or equals to a given value.
     * @since 9.0.0
     */
    public function isGreaterThanOrEqualTo(DateTimeable $other): bool
    {
        return $this->isGreaterThan($other) || $this->isEqualTo($other);
    }

    /**
     * Create a current time.
     */
    public static function createNow(): self
    {
        return self::fromDateTime(new DateTimeImmutable());
    }

    /**
     * Create from a string with a date-time in `Y-m-d H:i:s` format.
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Create from a timestamp.
     */
    public static function fromTimestamp(int $timestamp): self
    {
        $dateTime = (new DateTimeImmutable)->setTimestamp($timestamp);

        return self::fromDateTime($dateTime);
    }

    /**
     * Create from a DateTimeInterface.
     */
    public static function fromDateTime(DateTimeInterface $dateTime): self
    {
        /** @var DateTimeImmutable $value */
        $value = DateTimeImmutable::createFromFormat(
            self::SYSTEM_FORMAT,
            $dateTime->format(self::SYSTEM_FORMAT),
            $dateTime->getTimezone()
        );

        $utcValue = $value
             ->setTimezone(new DateTimeZone('UTC'))
             ->format(self::SYSTEM_FORMAT);

        $obj = new self($utcValue);

        $obj->dateTime = $obj->dateTime->setTimezone($dateTime->getTimezone());

        return $obj;
    }

    /**
     * @deprecated As of v8.1. Use `toString` instead.
     * @todo Remove in v10.0.
     */
    public function getString(): string
    {
        return $this->toString();
    }

    /**
     * @deprecated As of v8.1. Use `toDateTime` instead.
     * @todo Remove in v10.0.
     */
    public function getDateTime(): DateTimeImmutable
    {
        return $this->toDateTime();
    }

    /**
     * @deprecated As of v8.1. Use `toTimestamp` instead.
     * @todo Remove in v10.0.
     */
    public function getTimestamp(): int
    {
        return $this->toTimestamp();
    }
}
