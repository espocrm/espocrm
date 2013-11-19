<?php

error_reporting(E_ALL ^ E_NOTICE);

require_once('bootstrap.php');


$app = new \Espo\Core\Application();

//$app->run();

$GLOBALS['app'] = $app;


?>