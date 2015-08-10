<?php

$sapiName = php_sapi_name();

if (substr($sapiName, 0, 3) != 'cli') {
    die("Extension installer script can be run only via CLI.\n");
}

include "bootstrap.php";

$arg = isset($_SERVER['argv'][1]) ? trim($_SERVER['argv'][1]) : '';

if (empty($arg)) {
    die("Specify extension package file.\n");
}

if (!file_exists($arg)) {
    die("Package file does not exist.\n");
}

$pathInfo = pathinfo($arg);
if (!isset($pathInfo['extension']) || $pathInfo['extension'] !== 'zip' || !is_file($arg)) {
    die("Unsupported package.\n");
}

$app = new \Espo\Core\Application();

$config = $app->getContainer()->get('config');
$entityManager = $app->getContainer()->get('entityManager');

$user = $entityManager->getEntity('User', 'system');
$app->getContainer()->setUser($user);

$upgradeManager = new \Espo\Core\ExtensionManager($app->getContainer());

echo "Start install process...\n";

try {
    $fileData = file_get_contents($arg);
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

echo "Install is completed.\n";