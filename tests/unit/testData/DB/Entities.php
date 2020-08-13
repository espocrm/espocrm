<?php

namespace Espo\Entities;

use Espo\ORM\Entity;
use Espo\ORM\BaseEntity;

class TEntity extends BaseEntity
{

}

class Account extends TEntity
{
    public $fields = [
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
    ];
    public $relations = [
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
    ];
}

class Team extends TEntity
{
    public $fields = [
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
    ];
    public $relations = [];
}

class EntityTeam extends TEntity
{
    public $fields = [
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
    ];
}

class Contact extends TEntity
{
    public $fields = [
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
            'orderBy' => "contact.first_name {direction}, contact.last_name {direction}",
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
    ];
    public $relations = [];
}

class Post extends TEntity
{
    public $fields = [
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
    ];
    public $relations = [
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
    ];
}

class Comment extends TEntity
{
    public $fields = [
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
    ];

    public $relations = [
        'post' => [
            'type' => Entity::BELONGS_TO,
            'entity' => 'Post',
            'key' => 'postId',
            'foreignKey' => 'id',
        ],
    ];
}

class PostData extends TEntity
{
    public $fields = [
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
    ];

    public $relations = [
        'post' => [
            'type' => Entity::BELONGS_TO,
            'entity' => 'Post',
            'key' => 'postId',
            'foreignKey' => 'id',
            'foreign' => 'postData',
        ],
    ];
}

class Tag extends TEntity
{
    public $fields = [
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
    ];

    public $relations = [
        'posts' => [
            'type' => Entity::MANY_MANY,
            'entity' => 'Post',
            'foreign' => 'tags',
            'columnAttributeMap' => [
                'role' => 'postRole',
            ],
        ],
    ];
}

class PostTag extends TEntity
{
    public $fields = [
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
    ];
}

class Note extends TEntity
{
    public $fields = [
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
    ];

    public $relations = [
        'parent' => [
            'type' => Entity::BELONGS_TO_PARENT,
            'entities' => ['Post'],
            'foreign' => 'notes',
        ],
    ];
}


class Article extends TEntity
{
    public $fields = [
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
        ]
    ];
}

class Job extends TEntity
{
    public $fields = [
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
    ];
}

class Test extends TEntity
{
    public $fields = [
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
    ];
}

class Dependee extends TEntity
{
    public $fields = [
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
    ];
    public $relations = [

    ];
}
