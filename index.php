<?php

if (empty($_GET['entryPoint'])) {
	include "main.html";
} else {
	include "bootstrap.php";
	
	$app = new \Espo\Core\Application();
	$app->runEntryPoint($_GET['entryPoint']);
}

