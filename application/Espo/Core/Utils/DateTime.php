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

namespace Espo\Core\Utils;

use Carbon\Carbon;

use Espo\Core\Field\Date;
use Espo\Core\Field\DateTime as DateTimeField;

use DateTime as DateTimeStd;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use RuntimeException;

/**
 * Util for a date-time formatting and conversion.
 * Available as 'dateTime' service.
 */
class DateTime
{
    public const SYSTEM_DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    public const SYSTEM_DATE_FORMAT = 'Y-m-d';

    private string $dateFormat;
    private string $timeFormat;
    private DateTimeZone $timezone;
    private string $language;

    public function __construct(
        ?string $dateFormat = 'YYYY-MM-DD',
        ?string $timeFormat = 'HH:mm',
        ?string $timeZone = 'UTC',
        ?string $language = 'en_US'
    ) {
        $this->dateFormat = $dateFormat ?? 'YYYY-MM-DD';
        $this->timeFormat = $timeFormat ?? 'HH:mm';
        $this->language = $language ?? 'en_US';

        try {
            $this->timezone = new DateTimeZone($timeZone ?? 'UTC');
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * Get a default date format.
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    /**
     * Get a default date-time format.
     */
    public function getDateTimeFormat(): string
    {
        return $this->dateFormat . ' ' . $this->timeFormat;
    }

    /**
     * Convert a system date.
     *
     * @param string $string A system date.
     * @param string|null $format A target format. If not specified then the default format will be used.
     * @param string|null $language A language. If not specified then the default language will be used.
     * @throws RuntimeException If it could not parse.
     */
    public function convertSystemDate(
        string $string,
        ?string $format = null,
        ?string $language = null
    ): string {

        $dateTime = DateTimeStd::createFromFormat('Y-m-d', $string);

        if ($dateTime === false) {
            throw new RuntimeException("Could not parse date `$string`.");
        }

        $carbon = Carbon::instance($dateTime);

        $carbon->locale($language ?? $this->language);

        return $carbon->isoFormat($format ?? $this->getDateFormat());
    }

    /**
     * Convert a system date-time.
     *
     * @param string $string A system date-time.
     * @param ?string $timezone A target timezone. If not specified then the default timezone will be used.
     * @param ?string $format A target format. If not specified then the default format will be used.
     * @param ?string $language A language. If not specified then the default language will be used.
     * @throws RuntimeException If it could not parse.
     */
    public function convertSystemDateTime(
        string $string,
        ?string $timezone = null,
        ?string $format = null,
        ?string $language = null
    ): string {

        if (strlen($string) === 16) {
            $string .= ':00';
        }

        $dateTime = DateTimeStd::createFromFormat('Y-m-d H:i:s', $string);

        if ($dateTime === false) {
            throw new RuntimeException("Could not parse date-time `$string`.");
        }

        try {
            $tz = $timezone ? new DateTimeZone($timezone) : $this->timezone;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $dateTime->setTimezone($tz);

        $carbon = Carbon::instance($dateTime);
        $carbon->locale($language ?? $this->language);

        return $carbon->isoFormat($format ?? $this->getDateTimeFormat());
    }

    /**
     * Get a current date.
     *
     * @param ?string $timezone If not specified then the default will be used.
     * @param ?string $format If not specified then the default will be used.
     */
    public function getTodayString(?string $timezone = null, ?string $format = null): string
    {
        try {
            $tz = $timezone ? new DateTimeZone($timezone) : $this->timezone;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $dateTime = new DateTimeStd();
        $dateTime->setTimezone($tz);

        $carbon = Carbon::instance($dateTime);
        $carbon->locale($this->language);

        return $carbon->isoFormat($format ?? $this->getDateFormat());
    }

    /**
     * Get a current date-time.
     *
     * @param ?string $timezone If not specified then the default will be used.
     * @param ?string $format If not specified then the default will be used.
     */
    public function getNowString(?string $timezone = null, ?string $format = null): string
    {
        try {
            $tz = $timezone ? new DateTimeZone($timezone) : $this->timezone;
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        $dateTime = new DateTimeStd();

        $dateTime->setTimezone($tz);

        $carbon = Carbon::instance($dateTime);

        $carbon->locale($this->language);

        return $carbon->isoFormat($format ?? $this->getDateTimeFormat());
    }

    /**
     * Get a current date-time in the system format in UTC timezone.
     */
    public static function getSystemNowString(): string
    {
        return date(self::SYSTEM_DATE_TIME_FORMAT);
    }

    public static function getSystemTodayString(): string
    {
        return date(self::SYSTEM_DATE_FORMAT);
    }

    /**
     * Convert a format to the system format.
     * Example: `YYYY-MM-DD` will be converted to `Y-m-d`.
     */
    public static function convertFormatToSystem(string $format): string
    {
        $map = [
            'MM' => 'm',
            'DD' => 'd',
            'YYYY' => 'Y',
            'HH' => 'H',
            'mm' => 'i',
            'hh' => 'h',
            'A' => 'A',
            'a' => 'a',
            'ss' => 's',
        ];

        return str_replace(
            array_keys($map),
            array_values($map),
            $format
        );
    }

    /**
     * Get the default time zone.
     *
     * @since 8.0.0
     */
    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }

    /**
     * Get a today's date according the default time zone.
     *
     * @since 8.0.0
     */
    public function getToday(): Date
    {
        $string = (new DateTimeImmutable)
            ->setTimezone($this->timezone)
            ->format(self::SYSTEM_DATE_FORMAT);

        return Date::fromString($string);
    }

    /**
     * Get a now date-time with the default time zone applied.
     *
     * @since 8.0.0
     */
    public function getNow(): DateTimeField
    {
        return DateTimeField::createNow()
            ->withTimezone($this->timezone);
    }

    /**
     * @deprecated Use `SYSTEM_DATE_TIME_FORMAT constant`.
     */
    public function getInternalDateTimeFormat(): string
    {
        return self::SYSTEM_DATE_TIME_FORMAT;
    }

    /**
     * @deprecated Use `SYSTEM_DATE_FORMAT constant`.
     */
    public function getInternalDateFormat(): string
    {
        return self::SYSTEM_DATE_FORMAT;
    }

    /**
     * @deprecated Use `convertSystemDate`.
     * @param string $string
     */
    public function convertSystemDateToGlobal($string): string
    {
        return $this->convertSystemDate($string);
    }

    /**
     * @deprecated Use `convertSystemDateTime`.
     */
    public function convertSystemDateTimeToGlobal(string $string): string
    {
        return $this->convertSystemDateTime($string);
    }
}
