<?php

include "bootstrap.php";
	
$app = new \Espo\Core\Application();

$configFile = $app->getContainer()->get('config')->get('configPath');
if (!file_exists($configFile)) {
	header("Location: install/");
	exit;
}

if (empty($_GET['entryPoint'])) {
	include "main.html";
} else {
	$app->runEntryPoint($_GET['entryPoint']);
}

