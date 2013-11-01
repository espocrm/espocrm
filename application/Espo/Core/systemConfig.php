<?php

return array (
  'configPath' => 'application/config.php',
  'customPath' => 'application/Custom',
  'cachePath' => 'data/cache',
  'defaultsPath' => 'application/Espo/Core/defaults',
  'unsetFileName' => 'unset.json',

  'metadataConfig' =>
  array (
    'name' => 'metadata',
    'cachePath' => 'data/cache/application',
    'corePath' => 'application/Espo/Metadata',
    'customPath' => 'application/Modules/{*}/Metadata',
    'doctrineCache' => 'data/cache/doctrine/metadata',
  ),

  'layoutConfig' =>
  array (
    'name' => 'layouts',
    'corePath' => 'application/Espo/Layouts',
    'customPath' => 'application/Modules/{*}/Layouts',
  ),

  'languageConfig' =>
  array (
    'name' => '{lang}',
    'cachePath' => 'data/cache/application/Language',
    'corePath' => 'application/Espo/Language',
    'customPath' => 'application/Modules/{*}/Language',
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
  ),
  'adminItems' =>
  array (
    'defaultPermissions',
    'logger',
    'devMode',
  ),
);

?>
