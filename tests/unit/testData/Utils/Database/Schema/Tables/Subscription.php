<?php

return [

    'Subscription' => [
        'fields' => [
            'id' => [
                'type' => 'id',
                'dbType' => 'int',
                'len' => '11',
                'autoincrement' => true,
                'unique' => true,
            ],
            'entityId' => [
                'type' => 'varchar',
                'len' => '24',
                'index' => 'entity',
            ],
            'entityType' => [
                'type' => 'varchar',
                'len' => '100',
                'index' => 'entity',
            ],
            'userId' => [
                'type' => 'varchar',
                'len' => '24',
                'index' => true,
            ],
        ],
    ],

];

