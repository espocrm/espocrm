<?php

namespace tests\Espo\Core\Utils;

require_once('tests/testBootstrap.php');

use Espo\Core\Utils\Util;


class UtilTest extends \PHPUnit_Framework_TestCase
{

	function testGetSeparator()
	{
		$this->assertEquals(DIRECTORY_SEPARATOR, Util::getSeparator());
	}

	function testToCamelCase()
	{
       $this->assertEquals('detail', Util::toCamelCase('detail'));
       $this->assertEquals('detailView', Util::toCamelCase('detail-view'));
	   $this->assertEquals('myDetailView', Util::toCamelCase('my-detail-view'));
	}

	function testFromCamelCase()
	{
       $this->assertEquals('detail', Util::fromCamelCase('detail'));
       $this->assertEquals('detail-view', Util::fromCamelCase('detailView'));
	   $this->assertEquals('my-detail-view', Util::fromCamelCase('myDetailView'));
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
		$this->assertEquals($result, Util::merge($array1, $array2Main));



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
		$this->assertEquals($result, Util::merge($array1, $array2Main));


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
		$this->assertEquals($result, Util::merge($array1, $array2Main));
	}

	function testToFormat()
	{
       $this->assertEquals('/Espo/Core/Utils', Util::toFormat('/Espo/Core/Utils', '/'));
       $this->assertEquals('\Espo\Core\Utils', Util::toFormat('/Espo/Core/Utils', '\\'));

	   $this->assertEquals('/Espo/Core/Utils', Util::toFormat('\Espo\Core\Utils', '/'));
       $this->assertEquals('\Espo\Core\Utils', Util::toFormat('\Espo\Core\Utils', '\\'));
	}

	function testConcatPath()
	{
		$result= 'dir1/dir2/file1.json';
    	$this->assertEquals($result, Util::concatPath('dir1/dir2', 'file1.json'));

		$result= 'dir1/dir2/file1.json';
    	$this->assertEquals($result, Util::concatPath('dir1/dir2/', 'file1.json'));

		$result= 'dir1/dir2/file1.json';
    	$this->assertEquals($result, Util::concatPath('dir1/dir2/file1.json'));
	}


	function testArrayToObject()
	{
		$testArr= array(
			'useCache' => true,
			'sub' =>  array (
				'subV' => '125',
				'subO' => array(
                	'subOV' => '125',
				),
			),
		);

		$testResult= (object) array(
			'useCache' => true,
		);
		$testResult->sub = (object) array (
				'subV' => '125',
		);
		$testResult->sub->subO = (object) array (
				'subOV' => '125',
		);

        $this->assertEquals($testResult, Util::arrayToObject($testArr));
	}


	function testObjectToArray()
	{
		$testObj= (object) array(
			'useCache' => true,
		);
		$testObj->sub = (object) array (
				'subV' => '125',
		);
		$testObj->sub->subO = (object) array (
				'subOV' => '125',
		);

		$testResult= array(
			'useCache' => true,
			'sub' =>  array (
				'subV' => '125',
				'subO' => array(
                	'subOV' => '125',
				),
			),
		);

        $this->assertEquals($testResult, Util::objectToArray($testObj));
	}


	/*function testGetScopeModuleName()
	{
       $this->assertEquals('Crm', $this->fixture->getScopeModuleName('Account'));
       $this->assertEquals('Crm', $this->fixture->getScopeModuleName('account'));
	   $this->assertNotEquals('crm', $this->fixture->getScopeModuleName('account'));

       $this->assertEquals('', $this->fixture->getScopeModuleName('User'));
       $this->assertEquals('', $this->fixture->getScopeModuleName('user'));
       $this->assertNotEquals('Crm', $this->fixture->getScopeModuleName('User'));
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
	} */




}

?>