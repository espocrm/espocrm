<?php

return array (
  'database' => 
  array (
    'driver' => 'pdo_mysql',
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
  'scopeModuleMap' => 
  array (
    'Product' => 'Crm',
    'Account' => 'Crm',
    'Contact' => 'Crm',
    'Lead' => 'Crm',
    'Opportunity' => 'Crm',
    'Calendar' => 'Crm',
    'Meeting' => 'Crm',
    'Call' => 'Crm',
    'Task' => 'Crm',
    'Case' => 'Crm',
    'Prospect' => 'Crm',
    'Email' => 'Crm',
    'emailTemplate' => 'Crm',
    'inboundEmail' => 'Crm',
  ),
  'adminItems' => 
  array (
    0 => 'custom1',
    1 => 'custom2',
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
);

?>