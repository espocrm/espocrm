<?php

$sapiName = php_sapi_name();

if (substr($sapiName, 0, 3) != 'cli') {
    die("Upgrade script can be run only via CLI.\n");
}

include "bootstrap.php";

$packageName = isset($_SERVER['argv'][1]) ? trim($_SERVER['argv'][1]) : '';

if ($packageName == 'version' || $packageName == '-v') {
    $app = new \Espo\Core\Application();
    die("Current version is " . $app->getContainer()->get('config')->get('version') . ".\n");
}

if (empty($packageName)) {
    die("Please specify an upgraded package.\n");
}

if (!file_exists($packageName)) {
    die("Package does not exist. Please check the file path.\n");
}

$pathInfo = pathinfo($packageName);
if (!isset($pathInfo['extension']) || $pathInfo['extension'] != 'zip' || !is_file($packageName)) {
    die("Unsupported package.\n");
}

$app = new \Espo\Core\Application();

$config = $app->getContainer()->get('config');
$entityManager = $app->getContainer()->get('entityManager');

$user = $entityManager->getEntity('User', 'system');
$app->getContainer()->setUser($user);

$upgradeManager = new \Espo\Core\UpgradeManager($app->getContainer());

echo "Start upgrade process. Current version is " . $config->get('version') . "\n";

try {
    $fileData = file_get_contents($packageName);
    $fileData = 'data:application/zip;base64,' . base64_encode($fileData);

    $upgradeId = $upgradeManager->upload($fileData);
    $upgradeManager->install(array('id' => $upgradeId));
} catch (\Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

try {
    $app = new \Espo\Core\Application();
    $app->runRebuild();
} catch (\Exception $e) {}

echo "Upgrade is complete. New version is " . $config->get('version') . ". \n";