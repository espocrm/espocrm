<?php

return array(

    'Subscription' => array(
        'fields' => array(
            'id' => array(
                'type' => 'id',
                'dbType' => 'int',
                'len' => '11',
                'autoincrement' => true,
                'unique' => true,
            ),
            'entityId' => array(
                'type' => 'varchar',
                'len' => '24',
                'index' => 'entity',
            ),
            'entityType' => array(
                'type' => 'varchar',
                'len' => '100',
                'index' => 'entity',
            ),
            'userId' => array(
                'type' => 'varchar',
                'len' => '24',
                'index' => true,
            ),
        ),
    ),

);

