<?php

require_once('../bootstrap.php');

use \Espo\Utils\Api as Api,
	\Espo\Utils as Utils,
	\Slim;

/* START: remove for composer */
require 'vendor/Slim/Slim.php';
\Slim\Slim::registerAutoloader();
/* END: remove for composer */

//$app = new \Slim\Slim();

$app = new \Slim\Slim(array(
    'mode' => 'development'
));
$app->add(new Api\Auth());

//convert all url params to camel case format
$app->hook('slim.before.dispatch', function () use ($app) {
	$routeParams= $app->router()->getCurrentRoute()->getParams();

	if (!empty($routeParams)) {
		$baseUtils= new Utils\BaseUtils();
		foreach($routeParams as &$param) {
	       $param= $baseUtils->toCamelCase($param);
		}

	    $app->router()->getCurrentRoute()->setParams($routeParams);
	}
});
//END: convert all url params to camel case format


$app->hook('slim.before.dispatch', function () use ($app) {

	$currentRoute = $app->router()->getCurrentRoute();
    $conditions = $currentRoute->getConditions();

	if (isset($conditions['useController']) && !$conditions['useController']) {
		return;
	}

	$espoController = call_user_func( $app->router()->getCurrentRoute()->getCallable() );
	$espoKeys = array_keys($espoController);

	if (!in_array('controller', $espoKeys) || !in_array('action', $espoKeys) || !in_array('scope', $espoKeys)) {
		return;
	}

	$ControllerManager = new Utils\Controllers\Manager();

	$params = $currentRoute->getParams();
	$data = $app->request()->getBody();

	//prepare controller Params
	$controllerParams = array();
    $controllerParams['HttpMethod'] = strtolower($app->request()->getMethod());

	foreach($espoController as $key => $val) {
    	if (strstr($val, ':')) {
    		$paramName = str_replace(':', '', $val);
        	$val = $params[$paramName];
    	}
		$controllerParams[$key] = $val;
	}
	//END: prepare controller Params

	$result = $ControllerManager->call($controllerParams, $params, $data);

	return Api\Helper::output($result->data, $result->errMessage, $result->errCode);
});


//return json response
$app->hook('slim.after.router', function () use (&$app) {
    $app->contentType('application/json');
    //$app->contentType('text/javascript');
});
//END: return json response


//Setup routes
$app->get('/', '\Espo\Utils\Api\Rest::main');

$app->get('/metadata/', '\Espo\Utils\Api\Rest::getMetadata');
$app->put('/metadata/:type/:scope/', '\Espo\Utils\Api\Rest::putMetadata');

$app->get('/settings/', '\Espo\Utils\Api\Rest::getSettings')->conditions( array('auth' => false) );
$app->map('/settings/', '\Espo\Utils\Api\Rest::patchSettings')->via('PATCH');
//$app->get('/settings/', '\Espo\Utils\Api\Rest::getSettings')->conditions( array('auth' => false) );

//$app->get('/:controller/layout/:name/', '\Espo\Utils\Api\Rest::getLayout');
//$app->put('/:controller/layout/:name/', '\Espo\Utils\Api\Rest::putLayout');
//$app->map('/:controller/layout/:name/', '\Espo\Utils\Api\Rest::patchLayout')->via('PATCH');

$app->get('/app/user/', '\Espo\Utils\Api\Rest::getAppUser');


/*$app->get('/:controller/:id', function() {
	return array(
		'controller' => ':controller',
		'action' => 'read',
        'id' => ':id'
	);
})->conditions( array('useController' => true) );

$app->post('/:controller', function() {
	return array(
		'controller' => ':controller',
		'action' => 'create',
	);
})->conditions( array('useController' => true) );

$app->put('/:controller/:id', function() {
	return array(
		'controller' => ':controller',
		'action' => 'update',
		 'id' => ':id'
	);
})->conditions( array('useController' => true) );

$app->patch('/:controller/:id', function() {
	return array(
		'controller' => ':controller',
		'action' => 'patch',
		 'id' => ':id'
	);
})->conditions( array('useController' => true) );


$app->get('/:controller/:id/:link/:foreignId', function() {
	return array(
		'controller' => ':controller',
		'action' => 'readRelated',
		'id' => ':id',
		'link' => ':link',
		'foreignId' => ':foreignId'
	);
})->conditions( array('useController' => true) );    */

//Layout
$app->get('/:controller/layout/:name/', function() {
	return array(
		'controller' => 'Layout',
		'scope' => ':controller',
		'action' => ':name',
	);
})->conditions( array('useController' => true) );

$app->put('/:controller/layout/:name/', function() {
	return array(
		'controller' => 'Layout',
		'scope' => ':controller',
		'action' => ':name',
	);
})->conditions( array('useController' => true) );

$app->map('/:controller/layout/:name/', function() {
	return array(
		'controller' => 'Layout',
		'scope' => ':controller',
		'action' => ':name',
	);
})->via('PATCH')->conditions( array('useController' => true) );
//END: Layout


$app->run();

?>