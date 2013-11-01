<?php

require_once('../bootstrap.php');

use \Espo\Utils\Api as Api,
	\Slim;

require 'vendor/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

//$app = new \Slim\Slim();

$app = new \Slim\Slim(array(
    'mode' => 'development'
));
$app->add(new Api\Auth());

$app->hook('slim.after.router', function () use (&$app) {
    $app->contentType('application/json');
    //$app->contentType('text/javascript');
});


//Setup routes
$app->get('/', '\Espo\Utils\Api\Rest::main');

$app->get('/metadata/', '\Espo\Utils\Api\Rest::getMetadata');
$app->put('/metadata/:type/:scope/', '\Espo\Utils\Api\Rest::putMetadata');

$app->get('/settings/', '\Espo\Utils\Api\Rest::getSettings')->conditions( array('auth' => false) );
$app->map('/settings/', '\Espo\Utils\Api\Rest::patchSettings')->via('PATCH');
//$app->get('/settings/', '\Espo\Utils\Api\Rest::getSettings')->conditions( array('auth' => false) );

$app->get('/:controller/layout/:name/', '\Espo\Utils\Api\Rest::getLayout');
$app->map('/:controller/layout/:name/', '\Espo\Utils\Api\Rest::patchLayout')->via('PATCH');
$app->put('/:controller/layout/:name/', '\Espo\Utils\Api\Rest::putLayout');

$app->get('/app/user/', '\Espo\Utils\Api\Rest::getAppUser');


/*$app->put('/settings/', 'Rest::putSettings');

//$app->map('/hello/', 'Rest::putSettings')->via('PATCH');

$app->get('/app/user/', 'Rest::getUserPreferences');
$app->map('/app/:action', 'Rest::appAction')->via('GET', 'POST');

$app->get('/metadata/:type/:scope/', 'Rest::getMetadata');
$app->put('/metadata/:type/:scope/', 'Rest::putMetadata');
$app->get('/metadata/:type/', 'Rest::getMetadataByType');

$app->get('/:controller/', 'Rest::getControllerList');
$app->get('/:controller/:id/', 'Rest::getController');

$app->get('/:controller/layout/:type/', 'Rest::getLayout');

*/


//$app->put('/:controller/layout/:type/', 'Rest::putLayout');
//$app->map('/:controller/layout/:type/', 'Rest::patchLayout')->via('PATCH');

//$app->put('/:controller/layout/:type/', 'Rest::putLayout');


/*$app->put( '/:controller/layout/:type/', function ($controller, $type) use ( $app ) {



	$mysqli = new mysqli('localhost', 'root', '', 'projects_jet');

	$query= "SELECT * FROM layouts
				WHERE controller='".$controller."' AND layout_type='".$type."' LIMIT 1";
	$result = $mysqli->query($query);
	$selectRow = $result->fetch_assoc();

   	$data = $app->request()->getBody();
   	//$data = $app->request()->params('payload');
   	//$dataFull = array_keys($dataPut);
   	//$data= $dataFull[0];


	if (empty($selectRow)) {
		//insert
		$query= "INSERT INTO layouts (
						controller,
						layout_type,
						data
					)
					VALUES (
						'".$controller."',
						'".$type."',
						'".$data."'
					);";
	}
	else {
		$query= "UPDATE layouts SET data='".$data."'
					WHERE id='".$selectRow['id']."' ";
		//update
	}

	$result = $mysqli->query($query);

	echo $data;
});  */

/*$app->map('/app/:action', 'appAction')->via('GET', 'POST');
$app->get('/:controller/', 'getControllerList');
$app->get('/:controller/:id/', 'getController');*/

/*
// POST route
$app->post('/post', function () {
    echo 'This is a POST route';
});

// PUT route
$app->put('/put', function () {
    echo 'This is a PUT route';
});

// DELETE route
$app->delete('/delete', function () {
    echo 'This is a DELETE route';
});
*/

$app->run();

?>