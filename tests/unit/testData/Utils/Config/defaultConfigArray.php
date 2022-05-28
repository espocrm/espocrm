<?php

return  [
    'configPath' => 'tests/testData/Utils/Config/testArray.php',

    'dateFormat' => 'MM/DD/YYYY',
    'timeFormat' => 'HH:mm',

    'cron' => [
        'maxJobNumber' => 15, /*Max number of jobs per one execution*/
        'jobPeriod' => 7800, /*Period for jobs, ex. if cron executed at 15:35, it will execute all pending jobs for times from 14:05 to 15:35*/
        'minExecutionTime' => 50, /*to avoid too frequency execution*/
    ],

    'systemUser' => [
        'id' => 'system',
        'userName' => 'system',
        'firstName' => '',
        'lastName' => 'System',
    ],

    'crud' => [
        'get' => 'read',
        'post' => 'create',
        'put' => 'update',
        'patch' => 'patch',
        'delete' => 'delete',
    ],
    'systemItems' =>
     [
         'systemItems',
         'adminItems',
         'configPath',
         'cachePath',
         'database',
         'customPath',
         'defaultsPath',
         'crud',
     ],
    'adminItems' =>
     [
         'defaultPermissions',
         'logger',
         'devMode',
     ],
    'currency' =>
    [
        'base' => 'USD',
        'rate' => [
            'EUR' => 1.37,
            'GBP' => 1.67,
        ],
    ],
];

?>
