<?php

return array (
  'Espo\\Entities\\User' =>
  array (
    'table' => 'users',
    'type' => 'entity',
    'id' =>
    array (
      'id' =>
      array (
        'type' => 'string',
        'generator' =>
        array (
          'strategy' => 'UUID',
        ),
      ),
    ),
    'fields' =>
    array (
      'username' =>
      array (
        'type' => 'string(30)',
      ),
      'password' =>
      array (
        'type' => 'string(255)',
      ),
    ),
  ),
);

?>