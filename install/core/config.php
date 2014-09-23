<?php

return array(

	'apiPath' => '/api/v1',

	'requirements' => array(
		'phpVersion' => '5.4',

		'phpRequires' => array(
			'JSON',
			'mcrypt',
			'pdo_mysql',
		),

		'phpRecommendations' => array(
			'GD',
			'IMAP',
			'max_execution_time' => 180,
			'max_input_time' => 180,
			'memory_limit' => '256M',
			'post_max_size' => '20M',
			'upload_max_filesize' => '20M',
		),

		'mysqlVersion' => '5.1',
		'mysqlRequires' => array(

		),

		'mysqlRecommendations' => array(

		),
	),

	'blog' => 'http://blog.espocrm.com',
	'twitter' => 'https://twitter.com/espocrm',
	'forum' => 'http://forum.espocrm.com',

);