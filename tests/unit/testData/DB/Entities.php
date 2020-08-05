<?php

namespace Espo\Entities;

use Espo\ORM\Entity;
use Espo\ORM\BaseEntity;

class TEntity extends BaseEntity
{

}

class Account extends TEntity
{
    public $fields = array(
            'id' => array(
                'type' => Entity::ID,
            ),
            'name' => array(
                'type' => Entity::VARCHAR,
                'len' => 255,
            ),
            'deleted' => array(
                'type' => Entity::BOOL,
                'default' => 0,
            ),
            'stub' => [
                'type' => 'varchar',
                'notStorable' => true,
            ],
    );
    public $relations = [
        'teams' => [
            'type' => Entity::MANY_MANY,
            'entity' => 'Team',
            'relationName' => 'EntityTeam',
            'midKeys' => [
                'entityId',
                'teamId',
            ],
            'conditions' => ['entityType' => 'Account']
        ],
    ];
}

class Team extends TEntity
{
    public $fields = array(
        'id' => array(
            'type' => Entity::ID,
        ),
        'name' => array(
            'type' => Entity::VARCHAR,
            'len' => 255,
        ),
        'deleted' => array(
            'type' => Entity::BOOL,
            'default' => 0,
        ),
    );
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
    public $fields = array(
            'id' => array(
                'type' => Entity::ID,
            ),
            'name' => array(
                'type' => Entity::VARCHAR,
                'notStorable' => true,
                'select' => "TRIM(CONCAT(contact.first_name, ' ', contact.last_name))",
                'where' => array(
                    'LIKE' => "(contact.first_name LIKE {value} OR contact.last_name LIKE {value} OR CONCAT(contact.first_name, ' ', contact.last_name) LIKE {value})",
                ),
                'orderBy' => "contact.first_name {direction}, contact.last_name {direction}",
            ),
            'firstName' => array(
                'type' => Entity::VARCHAR,
            ),
            'lastName' => array(
                'type' => Entity::VARCHAR,
            ),
            'deleted' => array(
                'type' => Entity::BOOL,
                'default' => 0,
            ),
    );
    public $relations = array();
}

class Post extends TEntity
{
    public $fields = array(
        'id' => array(
            'type' => Entity::ID,
        ),
        'name' => array(
            'type' => Entity::VARCHAR,
            'len' => 255,
        ),
        'privateField' => array(
            'notStorable' => true,
        ),
        'createdByName' => array(
            'type' => Entity::FOREIGN,
            'relation' => 'createdBy',
            'foreign' => array('salutationName', 'firstName', ' ', 'lastName'),
        ),
        'createdById' => array(
            'type' => Entity::FOREIGN_ID,
        ),
        'deleted' => array(
            'type' => Entity::BOOL,
            'default' => 0,
        ),
    );
    public $relations = array(
        'tags' => array(
            'type' => Entity::MANY_MANY,
            'entity' => 'Tag',
            'relationName' => 'PostTag',
            'key' => 'id',
            'foreignKey' => 'id',
            'midKeys' => array(
                'postId',
                'tagId',
            ),
        ),
        'comments' => array(
            'type' => Entity::HAS_MANY,
            'entity' => 'Comment',
            'foreignKey' => 'postId',
        ),
        'createdBy' => array(
            'type' => Entity::BELONGS_TO,
            'entity' => 'User',
            'key' => 'createdById',
        ),
        'notes' => array(
            'type' => Entity::HAS_CHILDREN,
            'entity' => 'Note',
            'foreignKey' => 'parentId',
            'foreignType' => 'parentType',
        ),
        'postData' => [
            'type' => Entity::HAS_ONE,
            'entity' => 'PostData',
            'foreign' => 'post',
            'noJoin' => true,
        ],
    );
}

class Comment extends TEntity
{
    public $fields = array(
            'id' => array(
                'type' => Entity::ID,
            ),
            'postId' => array(
                'type' => Entity::FOREIGN_ID,
            ),
            'postName' => array(
                'type' => Entity::FOREIGN,
                'relation' => 'post',
                'foreign' => 'name',
            ),
            'name' => array(
                'type' => Entity::VARCHAR,
                'len' => 255,
            ),
            'deleted' => array(
                'type' => Entity::BOOL,
                'default' => 0,
            ),
    );

    public $relations = array(
            'post' => array(
                'type' => Entity::BELONGS_TO,
                'entity' => 'Post',
                'key' => 'postId',
                'foreignKey' => 'id',
            ),
    );
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
    public $fields = array(
        'id' => array(
            'type' => Entity::ID,
        ),
        'name' => array(
            'type' => Entity::VARCHAR,
            'len' => 50,
        ),
        'deleted' => array(
            'type' => Entity::BOOL,
            'default' => 0,
        ),
    );
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
    public $fields = array(
        'id' => array(
            'type' => Entity::ID,
        ),
        'name' => array(
            'type' => Entity::VARCHAR,
            'len' => 50,
        ),
        'parentId' => array(
            'type' => Entity::FOREIGN_ID,
        ),
        'parentType' => array(
            'type' => Entity::FOREIGN_TYPE,
        ),
        'parentName' => array(
            'type' => Entity::VARCHAR,
            'notStorable' => true,
        ),
        'deleted' => array(
            'type' => Entity::BOOL,
            'default' => 0,
        ),
    );

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
    public $fields = array(
        'id' => array(
            'type' => Entity::ID
        ),
        'name' => array(
            'type' => Entity::VARCHAR,
            'len' => 50,
        ),
        'description' => array(
            'type' => Entity::TEXT
        ),
        'deleted' => array(
            'type' => Entity::BOOL,
            'default' => 0
        )
    );
}


class Job extends TEntity
{
    public $fields = array(
        'id' => array(
            'type' => Entity::ID
        ),
        'string' => array(
            'type' => Entity::VARCHAR,
            'len' => 50
        ),
        'array' => array(
            'type' => Entity::JSON_ARRAY
        ),
        'arrayUnordered' => array(
            'type' => Entity::JSON_ARRAY,
            'isUnordered' => true
        ),
        'object' => array(
            'type' => Entity::JSON_OBJECT
        ),
        'deleted' => array(
            'type' => Entity::BOOL,
            'default' => 0
        )
    );
}

