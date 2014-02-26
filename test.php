<?php
error_reporting(-1);

require_once('bootstrap.php');

$app = new \Espo\Core\Application();


echo '/very/lllooooonnnnnggggg/file/path.txt' | wc -c;


//phpinfo();
exit;



$fm = $app->getContainer()->get('fileManager');
//$GLOBALS['log']->error('My Custom Error');


echo '<pre>';
print_r($fm->putContents('tests/taras/test/me/ggg.json', '{"data":"dddd"}'));



exit;


$user = $app->getContainer()->get('entityManager')->getRepository('User')->findOne(array(
				'whereClause' => array(
					'userName' => 'admin',					
				),
			));
$app->getContainer()->setUser($user);


$i18n = $app->getContainer()->get('i18n');

echo '<pre>';
//print_r( $i18n->get() );
var_dump( $i18n->get() );


exit;


echo '<pre>';
//var_dump( $app->getContainer()->get('metadata')->get('app.adminPanel.system') );
var_dump( $app->getContainer()->get('metadata')->get('app.adminPanel.system.label') );
//print_r( $app->getContainer()->get('metadata')->get() );


exit;


/*$cron = Cron\CronExpression::factory('53 * * * *');
var_dump($cron->isDue()).'<br>';

echo date('Y-m-d H:i:s').']]<br>';

echo 'Next= '.$cron->getNextRunDate()->format('Y-m-d H:i:s').'<br>';
echo 'Prev= '.$cron->getPreviousRunDate()->format('Y-m-d H:i:s');

exit;*/



/*$entryPoint = new \Espo\Core\EntryPointManager($app->getContainer());
$entryPoint->run('Download');

exit;*/



$user = $app->getContainer()->get('entityManager')->getRepository('User')->findOne(array(
				'whereClause' => array(
					'userName' => 'admin',					
				),
			));
$app->getContainer()->setUser($user);


//$cronManager = new \Espo\Core\CronManager( $app->getContainer()->get('serviceFactory'), $app->getContainer()->get('config'), $app->getContainer()->get('fileManager') );
$cronManager = new \Espo\Core\CronManager( $app->getContainer() );
echo $cronManager->run();


exit;

//dddhhh
echo time().'<br>';

echo '<pre>';
print_r();
exit;


/*$cron = Cron\CronExpression::factory('@daily');
echo $cron->isDue();
echo $cron->getNextRunDate()->format('Y-m-d H:i:s').'<br>';
echo $cron->getPreviousRunDate()->format('Y-m-d H:i:s');

exit;*/

//$cron = Cron\CronExpression::factory('3-59/15 2,6-12 */15 1 2-5');
$cron = Cron\CronExpression::factory('24 * * * *');
var_dump($cron->isDue()).'<br>';
//var_dump(isInRange($cron, '2014-01-28 12:00:00', '2014-01-28 13:00:00')).'<br>';
var_dump(isInRange($cron, '1 hour')).'<br>';

echo date('Y-m-d H:i:s').']]<br>';

echo $cron->getNextRunDate()->format('Y-m-d H:i:s').'<br>';
echo $cron->getPreviousRunDate()->format('Y-m-d H:i:s');



function isInRange($cron, $period = '30 minutes')
{	
	$startDateTimestamp = strtotime($period);
	$endDateTimestamp = strtotime('-'.$period);
	$cronDateTimestamp = $cron->getNextRunDate($currentDate, 0, true)->getTimestamp();

	echo '<br> ********* <br>';
	echo date('Y-m-d H:i:s', $startDateTimestamp).'<br>';
	echo date('Y-m-d H:i:s', $endDateTimestamp).'<br>';
	echo date('Y-m-d H:i:s', $cronDateTimestamp).'<br>';
	echo '********* <br>';

	if ($cronDateTimestamp >= $startDateTimestamp && $cronDateTimestamp <= $endDateTimestamp) {
		return true;
	}

	return false;
}

/*function isInRange($cron, $startDate, $endDate)
{	
	$startDateTimestamp = strtotime($startDate);
	$endDateTimestamp = strtotime($endDate);
	$cronDateTimestamp = $cron->getNextRunDate($currentDate, 0, true)->getTimestamp();

	if ($cronDateTimestamp >= $startDateTimestamp && $cronDateTimestamp <= $endDateTimestamp) {
		return true;
	}

	return false;
}*/
//********************************************************************************************/
exit;


$app->getSlim()->get('/', function() {});
$app->getSlim()->run();

echo 'ooooo';

exit;


$entryPoint = $app->getContainer()->get('entryPointManager')->getAll();


echo '<pre>';
print_r($entryPoint);


exit;

/*$route = new \Espo\Core\Utils\Route($app->getContainer()->get('config'), $app->getContainer()->get('fileManager'));

echo '<pre>';
print_r($route->get('0.route'));

exit;  */

//$entity = $app->getContainer()->get('entityManager')->getRepository('User')->findOne(array('userName' => 'admin'));
$entity = $app->getContainer()
			->get('entityManager')
			->getRepository('User')
			->findOne(array(
				'whereClause' => array(
					'userName' => 'admin',
				),
			)
			);

//$currentUser = $app->getContainer()->get('user');



echo '<pre>';
print_r($entity);
//print_r($currentUser);



//******************************************************************************
exit;
$espoSchema = new  \Espo\Core\Utils\Database\Schema($app->getContainer()->get('config'), $app->getMetadata(), $app->getContainer()->get('fileManager'));

$db= $espoSchema->getConnection();

$sm = $db->getSchemaManager();

$schema = new \Doctrine\DBAL\Schema\Schema();
//$schema = $sm->createSchema();
//$schema = new \Espo\Core\Database\Schema();
$myTable = $schema->createTable("my_table");
$myTable->addColumn("id", "integer", array("unsigned" => true));
$myTable->addColumn("username", "varchar", array("length" => 32, "default" => "admin",));
$myTable->addColumn("test", "varchar", array("length" => 32));
$myTable->setPrimaryKey(array("id"));



$myPlatform = $db->getDatabasePlatform();

$queries = $schema->toSql($myPlatform); // get queries to create this schema.
//$dropSchema = $schema->toDropSql($myPlatform); // get queries to safely delete this schema.


//print_r(\Doctrine\DBAL\Types\Type::getTypesMap());
//print_r($queries);

//exit;

echo '<pre>';
//print_r($queries);

//print_r($dropSchema);
//exit;


//exit;

$fromSchema = $sm->createSchema();


//print_r($schema);
//exit;

$comparator = new \Doctrine\DBAL\Schema\Comparator();
$schemaDiff = $comparator->compare($fromSchema, $schema);

$queries = $schemaDiff->toSql($myPlatform); // queries to get from one to another schema.
$saveQueries = $schemaDiff->toSaveSql($myPlatform);


//$espoSchema->getConnection()->executeQuery($saveQueries);


print_r($queries);
print_r($saveQueries);

exit;

echo '<pre>';
//var_dump( $app->getContainer()->get('metadata')->get('app.adminPanel.system') );
var_dump( $app->getContainer()->get('metadata')->get('app.adminPanel.system.label') );
//print_r( $app->getContainer()->get('metadata')->get() );


exit;


//echo $app->getContainer()->get('entityManager')->getRepository('Espo\Entities\User')->isAdmin();

echo '<pre>';
print_r( $app->getContainer()->get('entityManager')->getRepository('Espo\Entities\User')->findOneBy(array('username' => 'admin')) );
exit;

//$user = $app->getContainer()->get('entityManager')->getRepository('\Espo\Entities\User')->findOneBy(array('username' => 'admin'));
$user = $app->getContainer()->get('entityManager')->getRepository('\Espo\Entities\User');

//$user = new \Espo\Entities\User();


echo '[[[<pre>';
print_r($user);

echo '<br /><br />[';
echo $user->isAdmin();


//$user = $this->entityManager->getRepository('\Espo\Entities\User')->findOneBy(array('username' => $username));



$app->getContainer()->get('entityManager')->getRepository('Espo\Entities\User')->isAdmin();








?>
