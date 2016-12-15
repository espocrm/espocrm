<?php

namespace tests\unit\testData\Entities;

use Espo\ORM\Entity;

class User extends Entity
{
    public $fields = array(
        'id' => array(
            'type' => Entity::ID,
        ),
        'name' => array(
            'type' => Entity::VARCHAR,
            'len' => 255
        ),
        'deleted' => array(
            'type' => Entity::BOOL,
            'default' => 0
        )
    );
}

