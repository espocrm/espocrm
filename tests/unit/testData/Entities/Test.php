<?php

namespace tests\unit\testData\Entities;

use Espo\ORM\Entity;

class Test extends Entity
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
        'object' => array(
            'type' => Entity::JSON_OBJECT
        ),
        'deleted' => array(
            'type' => Entity::BOOL,
            'default' => 0,
        )
    );
}

