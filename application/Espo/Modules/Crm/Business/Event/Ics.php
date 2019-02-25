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

namespace Espo\Modules\Crm\Business\Event;

class Ics
{
    private $dEnd;

    private $dStart;

    private $sAddress;

    private $sDescription;

    private $sHtml;

    private $sWho;

    private $sEmail;

    private $sUri;

    private $sUid;

    private $sSummary;

    private $sOutput;

    private $sProdid;

    public function __construct($prodid, array $attributes = array())
    {
        if (!is_string($prodid) || $prodid === '') {
            throw new \Exception('PRODID is required');
        }

        $this->sProdid = $prodid;

        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'startDate':
                $this->dStart = $value;
                break;

            case 'endDate':
                $this->dEnd = $value;
                break;

            case 'address':
                $this->sAddress = $value;
                break;

            case 'summary':
                $this->sSummary = $value;
                break;

            case 'who':
                $this->sWho = $value;
                break;

            case 'email':
                $this->sEmail = $value;
                break;

            case 'uri':
                $this->sUri = $value;
                break;

            case 'uid':
                $this->sUid = $value;
                break;

            case 'description':
                $this->sDescription = $value;
                break;

            case 'html':
                $this->sHtml = $value;
                break;
        }

        return $this;
    }

    public function __get($name) {
        switch ($name)
        {
            case 'startDate':
                return $this->dStart;
                break;

            case 'endDate':
                return $this->dEnd;
                break;

            case 'address':
                return $this->sAddress;
                break;

            case 'summary':
                return $this->sSummary;
                break;

            case 'uri':
                return $this->sUri;
                break;

            case 'who':
                return $this->sWho;
                break;

            case 'email':
                return $this->sEmail;
                break;

            case 'uid':
                return $this->sUid;
                break;

            case 'description':
                return $this->sDescription;
                break;

            case 'html':
                return $this->sHtml;
                break;
        }
    }

    public function get()
    {
        ($this->sOutput) ? $this->sOutput : $this->generate();

        return $this->sOutput;
    }

    private function generate()
    {
        $this->sOutput = "BEGIN:VCALENDAR\n".
             "VERSION:2.0\n".
             "PRODID:-".$this->sProdid."\n".
             "METHOD:REQUEST\n".
             "BEGIN:VEVENT\n".
             "DTSTART:".$this->dateToCal($this->startDate)."\n".
             "DTEND:".$this->dateToCal($this->endDate)."\n".
             "SUMMARY:".$this->escapeString($this->summary)."\n".
             "LOCATION:".$this->escapeString($this->address)."\n".
             "ORGANIZER;CN=".$this->escapeString($this->who).":MAILTO:" . $this->escapeString($this->email)."\n".
             "DESCRIPTION:".$this->escapeString($this->formatMultiline($this->description))."\n".
             "UID:".$this->uid."\n".
             "SEQUENCE:0\n".
             "DTSTAMP:".$this->dateToCal(time())."\n".
             "END:VEVENT\n".
             "END:VCALENDAR";
    }

    private function dateToCal($timestamp)
    {
        return date('Ymd\THis\Z', ($timestamp) ? $timestamp : time());
    }

    private function escapeString($string)
    {
        return preg_replace('/([\,;])/','\\\$1', ($string) ? $string : '');
    }

    private function formatMultiline($string)
    {
        return str_replace(["\r\n", "\n"], "\\n", $string);
    }
}
