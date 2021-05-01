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

namespace Espo\Core\Utils;

use Carbon\Carbon;

use DateTimeZone;
use DateTime as DateTimeStd;

/**
 * Util for a date-time formatting and conversion.
 * Available as 'dateTime' service.
 */
class DateTime
{
    public const SYSTEM_DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public const SYSTEM_DATE_FORMAT = 'Y-m-d';

    private $dateFormat;

    private $timeFormat;

    private $timezone;

    private $language;

    public function __construct(
        ?string $dateFormat = 'YYYY-MM-DD',
        ?string $timeFormat = 'HH:mm',
        ?string $timeZone = 'UTC',
        ?string $language = 'en_US'
    ) {
        $this->dateFormat = $dateFormat ?? 'YYYY-MM-DD';
        $this->timeFormat = $timeFormat ?? 'HH:mm';
        $this->timezone = new DateTimeZone($timeZone ?? 'UTC');
        $this->language = $language ?? 'en_US';
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
     */
    public function convertSystemDate(
        string $string,
        ?string $format = null,
        ?string $language = null
    ): ?string {

        $dateTime = DateTimeStd::createFromFormat('Y-m-d', $string);

        if (!$dateTime) {
            return null;
        }

        $carbon = Carbon::instance($dateTime);

        $carbon->locale($language ?? $this->language);

        return $carbon->isoFormat($format ?? $this->getDateFormat());
    }

    /**
     * Convert a system date-time.
     *
     * @param string $string A system date-time.
     * @param string $timezone A target timezone. If not specified then the default timezone will be used.
     * @param string|null $format A target format. If not specified then the default format will be used.
     * @param string|null $language A language. If not specified then the default language will be used.
     */
    public function convertSystemDateTime(
        string $string,
        ?string $timezone = null,
        ?string $format = null,
        ?string $language = null
    ): ?string {

        if (is_string($string) && strlen($string) === 16) {
            $string .= ':00';
        }

        $dateTime = DateTimeStd::createFromFormat('Y-m-d H:i:s', $string);

        if (!$dateTime) {
            return null;
        }

        $tz = $timezone ? new DateTimeZone($timezone) : $this->timezone;

        $dateTime->setTimezone($tz);

        $carbon = Carbon::instance($dateTime);

        $carbon->locale($language ?? $this->language);

        return $carbon->isoFormat($format ?? $this->getDateTimeFormat());
    }

    /**
     * Get a current date.
     *
     * @param string|null $timezone If not specified then the default will be used.
     * @param string|null $format If not specified then the default will be used.
     */
    public function getTodayString(?string $timezone = null, ?string $format = null): string
    {
        $tz = $timezone ? new DateTimeZone($timezone) : $this->timezone;

        $dateTime = new DateTimeStd();

        $dateTime->setTimezone($tz);

        $carbon = Carbon::instance($dateTime);

        $carbon->locale($this->language);

        return $carbon->isoFormat($format ?? $this->getDateFormat());
    }

    /**
     * Get a current date-time.
     *
     * @param string|null $timezone If not specified then the default will be used.
     * @param string|null $format If not specified then the default will be used.
     */
    public function getNowString(?string $timezone = null, ?string $format = null): string
    {
        $tz = $timezone ? new DateTimeZone($timezone) : $this->timezone;

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
     */
    public function convertSystemDateToGlobal($string): ?string
    {
        return $this->convertSystemDate($string);
    }

    /**
     * @deprecated Use `convertSystemDateTime`.
     */
    public function convertSystemDateTimeToGlobal(string $string): ?string
    {
        return $this->convertSystemDateTime($string);
    }
}
