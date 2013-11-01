<?php

return array (
  'Modules\\Crm\\Entities\\Product' => 
  array (
    'table' => 'products',
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
      'name' => 
      array (
        'type' => 'string',
      ),
    ),
  ),
);

?>