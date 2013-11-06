<?php

namespace Espo\Tests\Utils;

require_once('bootstrap.php');

use Espo\Utils as Utils;

class BaseUtilsTest extends \PHPUnit_Framework_TestCase
{
	protected $fixture;

	protected function setUp()
    {
        $this->fixture = new Utils\BaseUtils();
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }

	function testMerge()
	{
		$array1= array(
			'defaultPermissions',
			'logger',
			'devMode'
		);
		$array2Main= array(
			45 => '125',
			'sub' =>  array (
				'subV' => '125',
			),
		);
		$result= array(
        	'defaultPermissions',
			'logger',
			'devMode',
			45 => '125',
			'sub' =>  array (
				'subV' => '125',
			),
		);
		$this->assertEquals($result, $this->fixture->merge($array1, $array2Main));



        $array1= array(
			'datetime' =>
			  array (
			    'dateFormat' => 'Y-m-d',
			    'timeFormat' => 'H:i:s',
			  ),
		);
		$array2Main= array(
		   	'datetime' =>
			  array (
			    'dateFormat' => 'MyDateFormat',
			  ),
		);
		$result= array(
        	'datetime' =>
			  array (
			    'dateFormat' => 'MyDateFormat',
			    'timeFormat' => 'H:i:s',
			  ),
		);
		$this->assertEquals($result, $this->fixture->merge($array1, $array2Main));


		$array1= array(
			'database' =>
			  array (
			    'driver' => 'pdo_mysql',
			    'host' => 'localhost',
			    'dbname' => 'espocrm',
			    'user' => 'root',
			    'password' => '',
			  ),
		);
		$array2Main= array(
		   	'database' =>
			  array (
			    'password' => 'MyPass',
			  ),
		);
		$result= array(
        	'database' =>
			  array (
			    'driver' => 'pdo_mysql',
			    'host' => 'localhost',
			    'dbname' => 'espocrm',
			    'user' => 'root',
			    'password' => 'MyPass',
			  ),
		);
		$this->assertEquals($result, $this->fixture->merge($array1, $array2Main));
	}


	function testGetScopeModuleName()
	{
       $this->assertEquals('Crm', $this->fixture->getScopeModuleName('Account'));
       $this->assertEquals('Crm', $this->fixture->getScopeModuleName('account'));
	   $this->assertNotEquals('crm', $this->fixture->getScopeModuleName('account'));

       $this->assertEquals('', $this->fixture->getScopeModuleName('User'));
       $this->assertEquals('', $this->fixture->getScopeModuleName('user'));
       $this->assertNotEquals('Crm', $this->fixture->getScopeModuleName('User'));
	}


	function testToCamelCase()
	{
       $this->assertEquals('detail', $this->fixture->toCamelCase('detail'));
       $this->assertEquals('detailView', $this->fixture->toCamelCase('detail-view'));
	   $this->assertEquals('myDetailView', $this->fixture->toCamelCase('my-detail-view'));
	}

	function testFromCamelCase()
	{
       $this->assertEquals('detail', $this->fixture->fromCamelCase('detail'));
       $this->assertEquals('detail-view', $this->fixture->fromCamelCase('detailView'));
	   $this->assertEquals('my-detail-view', $this->fixture->fromCamelCase('myDetailView'));
	}


	function testGetScopePath()
	{
       $this->assertEquals('Modules/Crm', $this->fixture->getScopePath('Account', '/'));
       $this->assertEquals('Modules\Crm', $this->fixture->getScopePath('Account', '\\'));
       $this->assertEquals('Modules\Crm', $this->fixture->getScopePath('account', '\\'));

       $this->assertEquals('Espo', $this->fixture->getScopePath('User', '/'));
       $this->assertEquals('Espo', $this->fixture->getScopePath('User', '\\'));
       $this->assertEquals('Espo', $this->fixture->getScopePath('user', '\\'));
	}


	function testGetScopes()
	{
       $this->assertArrayHasKey('User', $this->fixture->getScopes() );    
	}




}

?>