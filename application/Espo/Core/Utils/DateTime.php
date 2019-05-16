<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class DateTime
{
    protected $dateFormat;

    protected $timeFormat;

    protected $timezone;

    public static $systemDateTimeFormat = 'Y-m-d H:i:s';

    public static $systemDateFormat  = 'Y-m-d';

    protected $internalDateTimeFormat = 'Y-m-d H:i:s';

    protected $internalDateFormat = 'Y-m-d';

    protected $dateFormats = array(
        'MM/DD/YYYY' => 'm/d/Y',
        'YYYY-MM-DD' => 'Y-m-d',
        'DD.MM.YYYY' => 'd.m.Y',
        'DD/MM/YYYY' => 'd/m/Y',
    );

    protected $timeFormats = array(
        'HH:mm' => 'H:i',
        'hh:mm A' => 'h:i A',
        'hh:mm a' => 'h:ia',
        'hh:mmA' => 'h:iA',
    );


    protected $formattingMap = array(
        'MMMM' => 'F',
        'MMM' => 'M',
        'MM' => 'm',
        'M' => 'n',
        'DDDD' => 'z',
        'DD' => 'd',
        'D' => 'j',
        'dddd' => 'l',
        'ddd' => 'D',
        'ww' => 'W',
        'w' => 'W',
        'e' => 'w',
        'YYYY' => 'Y',
        'YY' => 'y',
        'HH' => 'H',
        'H' => 'G',
        'hh' => 'h',
        'h' => 'g',
        'mm' => 'i',
        'm' => 'i',
        'A' => 'A',
        'a' => 'a',
        'ss' => 's',
        's' => 's',
        'Z' => 'O',
        'z' => 'O'
    );

    public function __construct($dateFormat = 'YYYY-MM-DD', $timeFormat = 'HH:mm', $timeZone = 'UTC')
    {
        $this->dateFormat = $dateFormat;
        $this->timeFormat = $timeFormat;

        $this->timezone = new \DateTimeZone($timeZone);
    }

    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    public function getDateTimeFormat()
    {
        return $this->dateFormat . ' ' . $this->timeFormat;
    }

    public function getInternalDateTimeFormat()
    {
        return $this->internalDateTimeFormat;
    }

    public function getInternalDateFormat()
    {
        return $this->internalDateFormat;
    }

    protected function getPhpDateFormat()
    {
        return $this->dateFormats[$this->dateFormat];
    }

    protected function getPhpDateTimeFormat()
    {
        return $this->dateFormats[$this->dateFormat] . ' ' . $this->timeFormats[$this->timeFormat];
    }

    protected function convertFormatToPhp($format)
    {
        return strtr($format, $this->formattingMap);
    }

    public function convertSystemDateToGlobal($string)
    {
        return $this->convertSystemDate($string);
    }

    public function convertSystemDateTimeToGlobal($string)
    {
        return $this->convertSystemDateTime($string);
    }

    public function convertSystemDate($string, $format = null)
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $string);
        if ($dateTime) {
            if ($format) {
                $phpFormat = $this->convertFormatToPhp($format);
            } else {
                $phpFormat = $this->getPhpDateFormat();
            }
            return $dateTime->format($phpFormat);
        }
        return null;
    }

    public function convertSystemDateTime($string, $timezone = null, $format = null)
    {
        if (is_string($string) && strlen($string) === 16) {
            $string .= ':00';
        }
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $string);
        if (empty($timezone)) {
            $timezone = $this->timezone;
        } else {
            $timezone = new \DateTimeZone($timezone);
        }

        if ($dateTime) {
            if ($format) {
                $phpFormat = $this->convertFormatToPhp($format);
            } else {
                $phpFormat = $this->getPhpDateTimeFormat();
            }
            return $dateTime->setTimezone($timezone)->format($phpFormat);
        }
        return null;
    }

    public function setTimezone($timezone)
    {
        $this->timezone = new \DateTimeZone($timezone);
    }

    public function getInternalNowString()
    {
        return date($this->getInternalDateTimeFormat());
    }

    public function getInternalTodayString()
    {
        return date($this->getInternalDateFormat());
    }

    public function getTodayString($timezone = null)
    {
        if ($timezone) {
            $timezoneObj = new \DateTimeZone($timezone);
        } else {
            $timezoneObj = $this->timezone;
        }

        $dateTime = new \DateTime();
        $dateTime->setTimezone($timezoneObj);

        return $dateTime->format($this->getPhpDateFormat());
    }

    public function getNowString($timezone = null, $format = null)
    {
        if ($timezone) {
            $timezoneObj = new \DateTimeZone($timezone);
        } else {
            $timezoneObj = $this->timezone;
        }

        $dateTime = new \DateTime();
        $dateTime->setTimezone($timezoneObj);

        if ($format) {
            $phpFormat = $this->convertFormatToPhp($format);
        } else {
            $phpFormat = $this->getPhpDateTimeFormat();
        }

        return $dateTime->format($phpFormat);
    }

    public static function isAfterThreshold($value, $period)
    {
        if (is_string($value)) {
            try {
                $dt = new \DateTime($value);
            } catch (\Exception $e) {
                return;
            }
        } else if ($value instanceof \DateTime) {
            $dt = clone $value;
        } else {
            return;
        }
        $dt->modify($period);

        $dtNow = new \DateTime();

        if ($dtNow->format('U') > $dt->format('U')) {
            return true;
        }

        return false;
    }

    public static function getSystemNowString()
    {
        return date(self::$systemDateTimeFormat);
    }
}
