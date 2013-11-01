<?php
chdir(dirname(__FILE__));
set_include_path( dirname(__FILE__) );

require_once "vendor/autoload.php";


use Espo\Core;
$base= \Espo\Core\Base::start();

//error_reporting(-1);
set_error_handler(array($base->log, 'catchError'), E_ALL);
set_exception_handler(array($base->log, 'catchException'));

/*
use Doctrine\ORM\Tools\Setup;



$isDevMode = true;
//JSON Driver for Doctrine
use Doctrine\ORM\Mapping\Driver\JsonDriver;

$config = Setup::createConfiguration($isDevMode, null, null);
$config->setMetadataDriverImpl(new JsonDriver(array(__DIR__."/data/cache/doctrine/metadata")));
//END: JSON Driver for Doctrine
*/

// database configuration parameters
/*$conn = array(
    'driver' => 'pdo_sqlite',
    'path' => __DIR__ . '/db.sqlite',
); */

/*$conn = array(
    'driver'   => 'pdo_mysql',
    'user'     => 'root',
    'password' => '',
    'dbname'   => 'jet_doctrine_last',
); */


/*$conn = array(
    'driver'   => 'pdo_mysql',
    //'driver'   => 'mysqli',
    'host'     => 'localhost',
    'dbname'   => 'jetcrm',
    'user'     => 'root',
    'password' => ''
);

// obtaining the entity manager
//$entityManager = \Doctrine\ORM\EntityManager::create($conn, $config);
$em = \Doctrine\ORM\EntityManager::create($conn, $config);
*/


?>