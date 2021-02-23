<?php

use Espo\ORM\Entity;

return [
    'Account' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
            ],
            'name' => [
                'type' => Entity::VARCHAR,
                'len' => 255,
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => 0,
            ],
            'stub' => [
                'type' => 'varchar',
                'notStorable' => true,
            ],
        ],
        'relations' => [
            'teams' => [
                'type' => Entity::MANY_MANY,
                'entity' => 'Team',
                'relationName' => 'EntityTeam',
                'midKeys' => [
                    'entityId',
                    'teamId',
                ],
                'conditions' => ['entityType' => 'Account'],
            ],
        ],
        'indexes' => [
            'name' => [
                'key' => 'IDX_NAME',
            ],
        ],
    ],

    'Team' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
            ],
            'name' => [
                'type' => Entity::VARCHAR,
                'len' => 255,
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => 0,
            ],
        ],
        'relations' => [

        ],
    ],

    'EntityTeam' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
                'autoincrement' => true,
                'dbType' => 'int',
            ],
            'entityId' => [
                'type' => Entity::FOREIGN_ID,
                'len' => 50,
            ],
            'entityType' => [
                'type' => Entity::VARCHAR,
                'len' => 50,
            ],
            'teamId' => [
                'type' => Entity::FOREIGN_ID,
                'len' => 50,
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => 0,
            ],
        ],
        'relations' => [

        ],
    ],

    'Contact' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
            ],
            'name' => [
                'type' => Entity::VARCHAR,
                'notStorable' => true,
                'select' => "TRIM(CONCAT(contact.first_name, ' ', contact.last_name))",
                'where' => [
                    'LIKE' => "(contact.first_name LIKE {value} OR contact.last_name LIKE {value} OR "
                        ."CONCAT(contact.first_name, ' ', contact.last_name) LIKE {value})",
                ],
                'order' => "contact.first_name {direction}, contact.last_name {direction}",
            ],
            'firstName' => [
                'type' => Entity::VARCHAR,
            ],
            'lastName' => [
                'type' => Entity::VARCHAR,
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => 0,
            ],
        ],
        'relations' => [

        ],
    ],

    'Post' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
            ],
            'name' => [
                'type' => Entity::VARCHAR,
                'len' => 255,
            ],
            'privateField' => [
                'notStorable' => true,
            ],
            'tagRole' => [
                'notStorable' => true,
                'type' => Entity::VARCHAR,
            ],
            'createdByName' => [
                'type' => Entity::FOREIGN,
                'relation' => 'createdBy',
                'foreign' => ['salutationName', 'firstName', ' ', 'lastName'],
            ],
            'createdById' => [
                'type' => Entity::FOREIGN_ID,
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => 0,
            ],
        ],
        'relations' => [
            'tags' => [
                'type' => Entity::MANY_MANY,
                'entity' => 'Tag',
                'relationName' => 'PostTag',
                'foreign' => 'posts',
                'key' => 'id',
                'foreignKey' => 'id',
                'midKeys' => [
                    'postId',
                    'tagId',
                ],
                'additionalColumns' => [
                    'role' => [
                        'type' => Entity::VARCHAR,
                    ],
                ],
                'columnAttributeMap' => [
                    'role' => 'tagRole',
                ],
            ],
            'comments' => [
                'type' => Entity::HAS_MANY,
                'entity' => 'Comment',
                'foreignKey' => 'postId',
            ],
            'createdBy' => [
                'type' => Entity::BELONGS_TO,
                'entity' => 'User',
                'key' => 'createdById',
            ],
            'notes' => [
                'type' => Entity::HAS_CHILDREN,
                'entity' => 'Note',
                'foreignKey' => 'parentId',
                'foreignType' => 'parentType',
            ],
            'postData' => [
                'type' => Entity::HAS_ONE,
                'entity' => 'PostData',
                'foreign' => 'post',
                'noJoin' => true,
            ],
        ],
    ],

    'Comment' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
            ],
            'postId' => [
                'type' => Entity::FOREIGN_ID,
            ],
            'postName' => [
                'type' => Entity::FOREIGN,
                'relation' => 'post',
                'foreign' => 'name',
            ],
            'name' => [
                'type' => Entity::VARCHAR,
                'len' => 255,
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => 0,
            ],
        ],
        'relations' => [
            'post' => [
                'type' => Entity::BELONGS_TO,
                'entity' => 'Post',
                'key' => 'postId',
                'foreignKey' => 'id',
            ],
        ],
    ],

    'PostData' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
            ],
            'postId' => [
                'type' => Entity::FOREIGN_ID,
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => 0,
            ],
        ],
        'relations' => [
            'post' => [
                'type' => Entity::BELONGS_TO,
                'entity' => 'Post',
                'key' => 'postId',
                'foreignKey' => 'id',
                'foreign' => 'postData',
            ],
        ],
    ],

    'Tag' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
            ],
            'name' => [
                'type' => Entity::VARCHAR,
                'len' => 50,
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => 0,
            ],
            'postRole' => [
                'type' => Entity::VARCHAR,
                'notStorable' => true,
            ],
        ],
        'relations' => [
            'posts' => [
                'type' => Entity::MANY_MANY,
                'entity' => 'Post',
                'foreign' => 'tags',
                'columnAttributeMap' => [
                    'role' => 'postRole',
                ],
            ],
        ],
    ],

    'PostTag' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
                'autoincrement' => true,
                'dbType' => 'int',
            ],
            'postId' => [
                'type' => Entity::FOREIGN_ID,
                'len' => 50,
            ],
            'tagId' => [
                'type' => Entity::FOREIGN_ID,
                'len' => 50,
            ],
            'role' => [
                'type' => Entity::VARCHAR,
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => 0,
            ],
        ],
        'relations' => [

        ],
    ],

    'Note' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
            ],
            'name' => [
                'type' => Entity::VARCHAR,
                'len' => 50,
            ],
            'parentId' => [
                'type' => Entity::FOREIGN_ID,
            ],
            'parentType' => [
                'type' => Entity::FOREIGN_TYPE,
            ],
            'parentName' => [
                'type' => Entity::VARCHAR,
                'notStorable' => true,
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => 0,
            ],
        ],
        'relations' => [
            'parent' => [
                'type' => Entity::BELONGS_TO_PARENT,
                'entities' => ['Post'],
                'foreign' => 'notes',
            ],
        ],
    ],

    'Article' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID
            ],
            'name' => [
                'type' => Entity::VARCHAR,
                'len' => 50,
            ],
            'description' => [
                'type' => Entity::TEXT
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => 0
            ],
        ],
        'relations' => [

        ],
    ],

    'Job' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID
            ],
            'string' => [
                'type' => Entity::VARCHAR,
                'len' => 50
            ],
            'array' => [
                'type' => Entity::JSON_ARRAY
            ],
            'arrayUnordered' => [
                'type' => Entity::JSON_ARRAY,
                'isUnordered' => true
            ],
            'object' => [
                'type' => Entity::JSON_OBJECT
            ],
            'deleted' => [
                'type' => Entity::BOOL,
                'default' => 0
            ],
        ],
        'relations' => [

        ],
    ],

    'Test' => [
        'fields' => [
            'id' => [
                 'type' => Entity::ID,
             ],
             'name' => [
                 'type' => Entity::VARCHAR,
                 'len' => 255,
             ],
             'date' => [
                 'type' => Entity::DATE
             ],
             'dateTime' => [
                 'type' => Entity::DATETIME
             ],
             'int' => [
                 'type' => Entity::INT
             ],
             'float' => [
                 'type' => Entity::FLOAT
             ],
             'list' => [
                 'type' => Entity::JSON_ARRAY
             ],
             'object' => [
                 'type' => Entity::JSON_OBJECT
             ],
             'deleted' => [
                 'type' => Entity::BOOL,
                 'default' => 0,
             ]
        ],
        'relations' => [

        ],
    ],

    'Dependee' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
            ],
            'name' => [
                'type' => Entity::VARCHAR,
                'len' => 255,
                'dependeeAttributeList' => [
                    'test'
                ],
            ],
            'test' => [
                'type' => Entity::BOOL,
                'default' => 0,
            ],
        ],
        'relations' => [

        ],
    ],

    'TestWhere' => [
        'fields' => [
            'id' => [
                 'type' => Entity::ID,
            ],
            'test' => [
                 'type' => Entity::VARCHAR,
            ],
            'test1' => [
                'type' => Entity::VARCHAR,
                'notStorable' => true,
                'where' => [
                     '=' => [
                         'whereClause' => [
                             'OR' => [
                                 ['test' => '{value}'],
                                 ['test' => '1'],
                             ],
                         ],
                         'joins' => [
                             ['Test', 't', ['t.id:' => 'id']],
                         ],
                     ],
                     "IN" => [
                         'whereClause' => [
                             'test' => '{value}'
                         ],
                     ]
                ],
                'order' => [
                     'order' => [
                         ['test', '{direction}'],
                         ['t.id', '{direction}'],
                     ],
                     'joins' => [
                         ['Test', 't', ['t.id:' => 'id']],
                     ],
                ],
                'select' => [
                     'select' => 'MUL:(t.id, test)',
                     'joins' => [
                         ['Test', 't', ['t.id:' => 'id']],
                     ],
                ],
             ],
            'test2' => [
                'type' => Entity::INT,
                'notStorable' => true,
                'where' => [
                     '=' => [
                         'whereClause' => [
                             'test' => '{value}',
                             'id!=' => null,
                         ],
                     ],
                ],
            ],
        ],
        'relations' => [
        ],
    ],

    'TestSelect' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
            ],
            'test' => [
                'type' => Entity::VARCHAR,
                'notStorable' => true,
                'select' => [
                    'select' => 'MUL:({id, 1)',
                ],
                'selectForeign' => [
                    'select' => 'MUL:({alias}.id, 1)',
                ],
                'order' => [
                    'order' => [
                        ['MUL:({alias}.id, 1)', '{direction}'],
                    ],
                ],
            ],
            'testAnother' => [
                'type' => Entity::VARCHAR,
                'notStorable' => true,
                'select' => [
                    'select' => 'MUL:({alias}.id, 1)',
                ],
            ],
        ],
        'relations' => [
            'right' => [
                'type' => Entity::HAS_MANY,
                'foreign' => 'left',
                'entity' => 'TestSelectRight',
            ],
        ],
    ],

    'TestSelectRight' => [
        'fields' => [
            'id' => [
                'type' => Entity::ID,
            ],
            'leftId' => [
                'type' => Entity::FOREIGN_ID,
            ],
        ],
        'relations' => [
            'left' => [
                'type' => Entity::BELONGS_TO,
                'foreign' => 'right',
                'entity' => 'TestSelect',
            ],
        ],
    ],
];