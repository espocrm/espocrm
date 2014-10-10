<?php

return array (
  'cacheTimestamp' => 1412600573,
  'removeOption' => 'Test',
  'testOption' => 'Another Wrong Value',
  'testOption2' => 'Test2',
  'database' => 
  array (
    'driver' => 'pdo_mysql',
    'host' => 'localhost',
    'dbname' => 'espocrm',
    'user' => 'root',
    'password' => '',
  ),
  'useCache' => false,
  'recordsPerPage' => 20,
  'recordsPerPageSmall' => 5,
  'applicationName' => 'EspoCRM',
  'version' => '1.0',
  'timeZone' => 'UTC',
  'dateFormat' => 'YYYY-MM-DD',
  'timeFormat' => 'HH:mm',
  'weekStart' => 1,
  'thousandSeparator' => ',',
  'decimalMark' => '.',
  'currencyList' => 
  array (
    0 => 'USD',
    1 => 'EUR',
  ),
  'defaultCurrency' => 'USD',
  'currency' => 
  array (
    'base' => 'USD',
    'rate' => 
    array (
      'EUR' => '1.37',
    ),
  ),
  'outboundEmailIsShared' => true,
  'outboundEmailFromName' => 'EspoCRM',
  'outboundEmailFromAddress' => '',
  'smtpServer' => '',
  'smtpPort' => 25,
  'smtpAuth' => true,
  'smtpSecurity' => '',
  'smtpUsername' => '',
  'smtpPassword' => '',
  'languageList' => 
  array (
    0 => 'en_US',
  ),
  'language' => 'en_US',
  'logger' => 
  array (
    'path' => 'data/logs/espo.log',
    'level' => 'INFO',
    'isRotate' => true,
    'maxRotateFiles' => 5,
  ),
  'defaultPermissions' => 
  array (
    'dir' => '0775',
    'file' => '0664',
    'user' => '',
    'group' => '',
  ),
  'cron' => 
  array (
    'maxJobNumber' => 15,
    'jobPeriod' => 7800,
    'minExecutionTime' => 50,
  ),
  'globalSearchEntityList' => 
  array (
    0 => 'Account',
    1 => 'Contact',
    2 => 'Lead',
    3 => 'Prospect',
    4 => 'Opportunity',
  ),
  'tabList' => 
  array (
    0 => 'Contact',
    1 => 'Account',
    2 => 'Lead',
    3 => 'Opportunity',
    4 => 'Calendar',
    5 => 'Meeting',
    6 => 'Call',
    7 => 'Task',
    8 => 'Case',
    9 => 'Prospect',
  ),
  'quickCreateList' => 
  array (
    0 => 'Account',
    1 => 'Contact',
    2 => 'Lead',
    3 => 'Opportunity',
    4 => 'Meeting',
    5 => 'Call',
    6 => 'Task',
    7 => 'Case',
    8 => 'Prospect',
  ),
  'isInstalled' => true,
);

?>