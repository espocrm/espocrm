<?php

return array(
    'fields' => array(
      'id' => 
      array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'id',
      ),
      'name' => 
      array (
        'type' => 'varchar',
        'len' => 255,
      ),
      'deleted' => 
      array (
        'type' => 'bool',
        'default' => false,
      ),
      'type' => 
      array (
        'type' => 'varchar',
        'len' => 100,
      ),
      'size' => 
      array (
        'type' => 'int',
        'len' => 11,
      ),
      'sourceId' => 
      array (
        'type' => 'varchar',
        'len' => 36,
      ),
      'createdAt' => 
      array (
        'type' => 'datetime',
        'notNull' => false,
      ),
      'contents' => 
      array (
        'type' => 'text',
        'notStorable' => true,
      ),
      'role' => 
      array (
        'type' => 'varchar',
        'len' => 36,
      ),
      'global' => 
      array (
        'type' => 'bool',
        'default' => false,
      ),
      'parentId' => 
      array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => 'parent',
        'notNull' => false,
      ),
      'parentType' => 
      array (
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'parent',
        'len' => 100,
        'dbType' => 'varchar',
      ),
      'parentName' => 
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
      'relatedId' => 
      array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => 'related',
        'notNull' => false,
      ),
      'relatedType' => 
      array (
        'type' => 'foreignType',
        'notNull' => false,
        'index' => 'related',
        'len' => 100,
        'dbType' => 'varchar',
      ),
      'relatedName' => 
      array (
        'type' => 'varchar',
        'notStorable' => true,
      ),
      'createdById' => 
      array (
        'dbType' => 'varchar',
        'len' => 24,
        'type' => 'foreignId',
        'index' => true,
        'notNull' => false,
      ),
      'createdByName' => 
      array (
        'type' => 'foreign',
        'notStorable' => false,
        'relation' => 'createdBy',
        'foreign' => 
        array (
          0 => 'firstName',
          1 => ' ',
          2 => 'lastName',
        ),
      ),
    ),
    'relations' => array(
     'related' => 
      array (
        'type' => 'belongsToParent',
        'key' => 'relatedId',
      ),
      'parent' => 
      array (
        'type' => 'belongsToParent',
        'key' => 'parentId',
      ),
      'createdBy' => 
      array (
        'type' => 'belongsTo',
        'entity' => 'User',
        'key' => 'createdById',
        'foreignKey' => 'id',
      ),
    )
);
