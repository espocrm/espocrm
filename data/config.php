<?php

return array (
  'database' => 
  array (
    'host' => 'localhost',
    'dbname' => 'espocrm_test',
    'user' => 'root',
    'password' => '',
  ),
  'logger' => 
  array (
    'path' => 'data/logs/espo.log',
    'level' => 'DEBUG',
    'isRotate' => true,
    'maxRotateFiles' => 3,
  ),
  'useCache' => false,
  'recordsPerPage' => 20,
  'recordsPerPageSmall' => 5,
  'applicationName' => 'EspoCRM',
  'timeZone' => 'UTC',
  'dateFormat' => 'MM/DD/YYYY',
  'timeFormat' => 'HH:mm',
  'weekStart' => 0,
  'thousandSeparator' => ',',
  'decimalMark' => '.',
  'currencyList' => 
  array (
    0 => 'USD',
    1 => 'EUR',
  ),
  'defaultCurrency' => 'USD',
  'outboundEmailIsShared' => true,
  'outboundEmailFromName' => 'EspoCRM',
  'outboundEmailFromAddress' => 'crm@letrium.com',
  'smtpServer' => 'mail.letrium.com',
  'smtpPort' => 25,
  'smtpAuth' => true,
  'smtpSecurity' => '',
  'smtpUsername' => 'test+letrium.com',
  'smtpPassword' => 'test123',
  'tabList' => 
  array (
    0 => 'Account',
    1 => 'Contact',
    2 => 'Lead',
    3 => 'Prospect',
    4 => 'Opportunity',
    5 => 'Calendar',
    6 => 'Meeting',
    7 => 'Call',
    8 => 'Task',
    9 => 'Case',
  ),
  'quickCreateList' => 
  array (
    0 => 'Contact',
    1 => 'Lead',
    2 => 'Meeting',
    3 => 'Call',
    4 => 'Task',
  ),
  'currency' => 
  array (
    'base' => 'USD',
    'rate' => 
    array (
      'EUR' => 1.3000000000000000444089209850062616169452667236328125,
      'GBP' => 1.6699999999999999289457264239899814128875732421875,
    ),
  ),
);

?>