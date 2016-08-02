<?php

namespace tests\testData\Entities;

use Espo\Core\ORM\Entity;

class Test2 extends Entity
{
    public $fields = array(
        'id' => array(
            'type' => Entity::ID,
        ),
        'name' => array(
            'type' => Entity::VARCHAR,
            'len' => 255,
        ),
        'date' => array(
            'type' => Entity::DATE
        ),
        'dateTime' => array(
            'type' => Entity::DATETIME
        ),
        'int' => array(
            'type' => Entity::INT
        ),
        'float' => array(
            'type' => Entity::FLOAT
        ),
        'list' => array(
            'type' => Entity::JSON_ARRAY
        ),
        'text' => array(
            'type' => Entity::TEXT,
            'len' => 255,
        ),
        'object' => array(
            'type' => Entity::JSON_OBJECT
        ),
        'deleted' => array(
            'type' => Entity::BOOL,
            'default' => 0,
        ),
        'assignedUserId' => array (
            'len' => 24,
            'type' => 'foreignId'
        ),
        'assignedUserName' => array (
            'type' => 'foreign',
            'notStorable' => false,
            'relation' => 'assignedUser',
            'foreign' => array (
                'firstName',
                ' ',
                'lastName'
            )
        ),
        'teamsIds' => array (
            'type' => 'varchar',
            'notStorable' => true
        ),
        'teamsNames' => array (
            'type' => 'varchar',
            'notStorable' => true
        )
    );

    public $relations = array(
        'assignedUser' => array(
            'type' => 'belongsTo',
            'entity' => 'User',
            'key' => 'assignedUserId',
            'foreignKey' => 'id',
        ),
        'teams' => array (
            'type' => 'manyMany',
            'entity' => 'Team',
            'relationName' => 'entityTeam'
        )
    );
}

