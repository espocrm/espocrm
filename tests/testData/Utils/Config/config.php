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

return array (
  'database' => 
  array (
    'driver' => 'mysqli',
    'host' => 'localhost',
    'dbname' => 'espocrm',
    'user' => 'root',
    'password' => '',
  ),
  'logger' => 
  array (
    'dir' => 'data/logs',
    'file' => 'espo.log',
    'level' => 'DEBUG',
  ),
  'useCache' => false,
  'recordsPerPage' => 20,
  'recordsPerPageSmall' => 5,
  'applicationName' => 'EspoCRM',
  'timeZone' => '',
  'dateFormat' => 'MM/DD/YYYY',
  'timeFormat' => 'HH:mm',
  'weekStart' => '1',
  'thousandSeparator' => ',',
  'decimalMark' => '.',
  'currencyList' => 
  array (
    0 => 'USD',
    1 => 'EUR',
  ),
  'testOption' => 'Another Wrong Value',
  'testOption2' => 'Test2',
);

?>