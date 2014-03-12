<?php

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