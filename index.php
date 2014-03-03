<?php

include "bootstrap.php";
	
$app = new \Espo\Core\Application();

$app->isInstalled();

if (empty($_GET['entryPoint'])) {
	include "main.html";
} else {
	$app->runEntryPoint($_GET['entryPoint']);
}

