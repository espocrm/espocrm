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
use Exception;
use DateTime as DateTimeStd;

/**
 * Util for a date-time formatting and conversion.
 * Available as 'dateTime' service.
 */
class DateTime
{
    public const SYSTEM_DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    public const SYSTEM_DATE_FORMAT = 'Y-m-d';

    protected $dateFormat;

    protected $timeFormat;

    protected $timezone;

    protected $langauge;

    /** @deprecated */
    public static $systemDateTimeFormat = self::SYSTEM_DATE_TIME_FORMAT;

    /** @deprecated */
    public static $systemDateFormat = self::SYSTEM_DATE_FORMAT;

    protected $internalDateTimeFormat = self::SYSTEM_DATE_TIME_FORMAT;

    protected $internalDateFormat = self::SYSTEM_DATE_FORMAT;

    protected $dateFormats = [
        'MM/DD/YYYY' => 'm/d/Y',
        'YYYY-MM-DD' => 'Y-m-d',
        'DD.MM.YYYY' => 'd.m.Y',
        'DD/MM/YYYY' => 'd/m/Y',
    ];

    protected $timeFormats = [
        'HH:mm' => 'H:i',
        'hh:mm A' => 'h:i A',
        'hh:mm a' => 'h:ia',
        'hh:mmA' => 'h:iA',
    ];

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

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function getDateTimeFormat(): string
    {
        return $this->dateFormat . ' ' . $this->timeFormat;
    }

    public function getInternalDateTimeFormat(): string
    {
        return $this->internalDateTimeFormat;
    }

    public function getInternalDateFormat(): string
    {
        return $this->internalDateFormat;
    }

    protected function getPhpDateFormat(): string
    {
        return $this->dateFormats[$this->dateFormat];
    }

    protected function getPhpDateTimeFormat(): string
    {
        return $this->dateFormats[$this->dateFormat] . ' ' . $this->timeFormats[$this->timeFormat];
    }

    public function convertSystemDateToGlobal($string): ?string
    {
        return $this->convertSystemDate($string);
    }

    public function convertSystemDateTimeToGlobal(string $string): ?string
    {
        return $this->convertSystemDateTime($string);
    }

    public function convertSystemDate(string $string, ?string $format = null, ?string $language = null): ?string
    {
        $dateTime = DateTimeStd::createFromFormat('Y-m-d', $string);

        if ($dateTime) {
            $carbon = Carbon::instance($dateTime);

            $carbon->locale($language ?? $this->language);

            return $carbon->isoFormat($format ?? $this->getDateFormat());
        }

        return null;
    }

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

        if (empty($timezone)) {
            $tz = $this->timezone;
        }
        else {
            $tz = new DateTimeZone($timezone);
        }

        if ($dateTime) {
            $dateTime->setTimezone($tz);

            $carbon = Carbon::instance($dateTime);

            $carbon->locale($language ?? $this->language);

            return $carbon->isoFormat($format ?? $this->getDateTimeFormat());
        }

        return null;
    }

    public function setTimezone(string $timezone): void
    {
        $this->timezone = new DateTimeZone($timezone);
    }

    public function getInternalNowString(): string
    {
        return date($this->getInternalDateTimeFormat());
    }

    public function getInternalTodayString(): string
    {
        return date($this->getInternalDateFormat());
    }

    public function getTodayString(?string $timezone = null, ?string $format = null): string
    {
        if ($timezone) {
            $tz = new DateTimeZone($timezone);
        } else {
            $tz = $this->timezone;
        }

        $dateTime = new DateTimeStd();

        $dateTime->setTimezone($tz);

        $format = $format ?? $this->getDateFormat();

        $carbon = Carbon::instance($dateTime);

        $carbon->locale($this->language);

        return $carbon->isoFormat($format);
    }

    public function getNowString(?string $timezone = null, ?string $format = null): string
    {
        if ($timezone) {
            $tz = new DateTimeZone($timezone);
        } else {
            $tz = $this->timezone;
        }

        $dateTime = new DateTimeStd();

        $dateTime->setTimezone($tz);

        $format = $format ?? $this->getDateTimeFormat();

        $carbon = Carbon::instance($dateTime);

        $carbon->locale($this->language);

        return $carbon->isoFormat($format);
    }

    public static function isAfterThreshold($value, string $period): bool
    {
        if (is_string($value)) {
            try {
                $dt = new DateTimeStd($value);
            }
            catch (Exception $e) {
                return false;
            }
        }
        else if ($value instanceof DateTimeStd) {
            $dt = clone $value;
        }
        else {
            return false;
        }

        $dt->modify($period);

        $dtNow = new DateTimeStd();

        if ($dtNow->format('U') > $dt->format('U')) {
            return true;
        }

        return false;
    }

    public static function getSystemNowString(): string
    {
        return date(self::SYSTEM_DATE_TIME_FORMAT);
    }
}
