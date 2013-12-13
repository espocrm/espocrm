<?php

return array (
  'configPath' => 'application/config.php',

  'customDir' => 'application/Espo/Custom',
  'cachePath' => 'data/cache',
  'defaultsPath' => 'application/Espo/Core/defaults',
  'unsetFileName' => 'unset.json',

  'espoPath' => 'Espo',
  'espoModulePath' => 'Espo/Modules/{*}',
  'espoCustomPath' => 'Espo/Custom',

  'controllerPath' => 'Controllers', //path for controllers in a module

  'metadataConfig' =>
  array (
    'name' => 'metadata',
    'cachePath' => 'data/cache/application',
    'corePath' => 'application/Espo/Resources/metadata',
    'customPath' => 'application/Espo/Modules/{*}/Resources/metadata',
  ),

  'layoutConfig' =>
  array (
    'name' => 'layouts',
    'corePath' => 'application/Espo/Resources/layouts',
    'customPath' => 'application/Espo/Modules/{*}/Resources/layouts',
  ),

  'languageConfig' =>
  array (
    'name' => '{lang}',
    'cachePath' => 'data/cache/application/Language',
    'corePath' => 'application/Espo/Language',
    'customPath' => 'application/Espo/Modules/{*}/Language',
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
    'metadataConfig',
    'layoutConfig',
    'languageConfig',
    'database',
    'customPath',
    'defaultsPath',
    'unsetFileName',
    'configPathFull',
    'configCustomPathFull',
    'crud',
    'customDir',
    'espoPath',
    'espoModulePath',
    'espoCustomPath',
    'controllerPath',
    'scopeModuleMap',
  ),
  'adminItems' =>
  array (
    'defaultPermissions',
    'logger',
    'devMode',
  ),
);

?>
