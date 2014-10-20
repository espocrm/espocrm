<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/ 

namespace Espo\Core\Utils;

class DateTime 
{
    protected $dataFormat;
    
    protected $timeFormat;
    
    protected $timezone;
    
    protected $dateFormats = array(
        'MM/DD/YYYY' => 'm/d/Y',
        'YYYY-MM-DD' => 'Y-m-d',
        'DD.MM.YYYY' => 'd.m.Y',
    );
    
    protected $timeFormats = array(
        'HH:mm' => 'H:i',
        'hh:mm A' => 'h:i A',
        'hh:mm a' => 'h:ia',
        'hh:mmA' => 'h:iA',
    );
    
    public function __construct($dateFormat = 'YYYY-MM-DD', $timeFormat = 'HH:mm', $timeZone = 'UTC')
    {
        $this->dateFormat = $dateFormat;
        $this->timeFormat = $timeFormat;
        
        $this->timezone = new \DateTimeZone($timeZone);        
    }    
    
    protected function getPhpDateFormat()
    {
        return $this->dateFormats[$this->dateFormat];
    }
    
    protected function getPhpDateTimeFormat()
    {
        return $this->dateFormats[$this->dateFormat] . ' ' . $this->timeFormats[$this->timeFormat];
    }
    
    public function convertSystemDateToGlobal($string)
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d', $string);        
        if ($dateTime) {
            return $dateTime->format($this->getPhpDateFormat());
        }
        return null;
    }
    
    public function convertSystemDateTimeToGlobal($string)
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $string);        
        if ($dateTime) {
            return $dateTime->setTimezone($this->timezone)->format($this->getPhpDateTimeFormat());
        }
        return null;
    }
}


