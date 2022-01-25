<?php
return [
  'database' => [
    'driver' => 'pdo_mysql',
    'charset' => 'utf8mb4',
    'host' => getenv('TEST_DATABASE_HOST'),
    'port' => getenv('TEST_DATABASE_PORT'),
    'dbname' => getenv('TEST_DATABASE_NAME'),
    'user' => getenv('TEST_DATABASE_USER'),
    'password' => getenv('TEST_DATABASE_PASSWORD'),
  ],
];
