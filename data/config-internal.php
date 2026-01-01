<?php
return [
  'database' => [
    'host' => 'localhost',
    'port' => '',
    'charset' => NULL,
    'dbname' => 'xibalbacrm',
    'user' => 'xibalbacrm',
    'password' => 'passwod',
    'platform' => 'Mysql'
  ],
  'smtpPassword' => NULL,
  'logger' => [
    'path' => 'data/logs/espo.log',
    'level' => 'WARNING',
    'rotation' => true,
    'maxFileNumber' => 30,
    'printTrace' => false,
    'databaseHandler' => false,
    'sql' => false,
    'sqlFailed' => false
  ],
  'restrictedMode' => false,
  'cleanupAppLog' => true,
  'cleanupAppLogPeriod' => '30 days',
  'webSocketMessager' => 'ZeroMQ',
  'clientSecurityHeadersDisabled' => false,
  'clientCspDisabled' => false,
  'clientCspScriptSourceList' => [
    0 => 'https://maps.googleapis.com'
  ],
  'adminUpgradeDisabled' => false,
  'isInstalled' => true,
  'microtimeInternal' => 1767241447.0557,
  'cryptKey' => '3d7d564d795eb751d3d07ffa631d1cbd',
  'hashSecretKey' => '3bd3a32d65007cf1e31e3b04d9058778',
  'defaultPermissions' => [
    'user' => 1000,
    'group' => 1000
  ],
  'actualDatabaseType' => 'mysql',
  'actualDatabaseVersion' => '8.0.44',
  'instanceId' => '41810675-a8d0-4a4c-9fa8-a1afe797c500'
];
