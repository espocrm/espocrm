<?php

return array (
  'configPath' => 'tests/testData/Utils/Config/testArray.php',

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
    'customPath',
    'defaultsPath', 
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
