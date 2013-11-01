<?php

return array (
  'Espo\\Entities\\Bug' => 
  array (
    'type' => 'entity',
    'manyToOne' => 
    array (
      'reporter' => 
      array (
        'inversedBy' => 'reportedBugs',
        'targetEntity' => 'Espo\\Entities\\User',
      ),
      'engineer' => 
      array (
        'inversedBy' => 'assignedBugs',
        'targetEntity' => 'Espo\\Entities\\User',
      ),
    ),
    'fields' => 
    array (
      'status' => 
      array (
        'type' => 'string',
      ),
      'description' => 
      array (
        'type' => 'text',
      ),
      'created' => 
      array (
        'type' => 'datetime',
      ),
    ),
    'repositoryClass' => 'BugRepository',
    'table' => 'bugs',
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
  ),
);

?>