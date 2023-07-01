<?php

return [
    'attributes' => [
      'id' =>
      [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'id',
      ],
      'name' =>
      [
        'type' => 'varchar',
        'len' => 255,
      ],
      'deleted' =>
      [
        'type' => 'bool',
        'default' => false,
      ],
      'type' =>
      [
        'type' => 'varchar',
        'len' => 100,
      ],
      'size' =>
      [
        'type' => 'int',
        'len' => 11,
      ],
      'sourceId' =>
      [
        'type' => 'varchar',
        'len' => 36,
      ],
      'createdAt' =>
      [
        'type' => 'datetime',
        'notNull' => false,
      ],
      'contents' =>
      [
        'type' => 'text',
        'notStorable' => true,
      ],
      'role' =>
      [
        'type' => 'varchar',
        'len' => 36,
      ],
      'global' =>
      [
        'type' => 'bool',
        'default' => false,
      ],
      'parentId' =>
      [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => 'parent',
        'notNull' => false,
      ],
      'parentType' =>
      [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'dbType' => 'varchar',
      ],
      'parentName' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
      'relatedId' =>
      [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => 'related',
        'notNull' => false,
      ],
      'relatedType' =>
      [
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'related',
        'len' => 100,
        'dbType' => 'varchar',
      ],
      'relatedName' =>
      [
        'type' => 'varchar',
        'notStorable' => true,
      ],
      'createdById' =>
      [
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
      ],
      'createdByName' =>
      [
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'createdBy',
        'foreign' =>
        [
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ],
      ],
    ],
    'relations' => [
     'related' =>
      [
        'type' => 'belongsToParent',
        'key' => 'relatedId',
      ],
      'parent' =>
      [
        'type' => 'belongsToParent',
        'key' => 'parentId',
      ],
      'createdBy' =>
      [
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
      ],
    ]
];
