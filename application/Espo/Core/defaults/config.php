<?php

return array (
  'configPath' => 'application/config.php',

  'cachePath' => 'data/cache',

  'logger' => 
  array (
    'path' => 'data/logs/espo.log',    
    'level' => 'DEBUG', /*DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY*/
    'isRotate' => true, /*rotate every day every logs files*/
    'maxRotateFiles' => 60, /*max number of rorate files*/
  ),

  'defaultPermissions' =>
  array (
    'dir' => '0775',
    'file' => '0664',
    'user' => '',
    'group' => '',
  ),
  'dateFormat' => 'MM/DD/YYYY',
  'timeFormat' => 'HH:mm',

  'cron' => array(
    'maxJobNumber' => 15, /*Max number of jobs per one execution*/    
    'jobPeriod' => 7800, /*Period for jobs, ex. if cron executed at 15:35, it will execute all pending jobs for times from 14:05 to 15:35*/
    'minExecutionTime' => 50, /*to avoid too frequency execution*/
  ),

  'systemUser' => array(
    'id' => 'system',
    'userName' => 'system',
    'firstName' => '',
    'lastName' => 'System',    
  ),

  'crud' => array(
  	'get' => 'read',
  	'post' => 'create',
  	'put' => 'update',
  	'patch' => 'patch',
  	'delete' => 'delete',
  ),
  'systemItems' =>
  array (
    'systemItems',
    'adminItems',
    'configPath',
    'cachePath',
    'database',
    'crud', 
  ),
  'adminItems' =>
  array (
    'defaultPermissions',
    'logger',
    'devMode',
  ),
  'currency' =>
  array(
    'base' => 'USD',
    'rate' => array(
      'EUR' => 1.37,
      'GBP' => 1.67,
    ),    
  ),
  
);

?>
