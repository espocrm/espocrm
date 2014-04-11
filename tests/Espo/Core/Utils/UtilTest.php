<?php

namespace tests\Espo\Core\Utils;

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
	   $this->assertEquals('my-f-f', Util::fromCamelCase('myFF'));
	}

	public function testToUnderScoree()
	{
       $this->assertEquals('detail', Util::toUnderScore('detail'));
       $this->assertEquals('detail_view', Util::toUnderScore('detailView'));
	   $this->assertEquals('my_detail_view', Util::toUnderScore('myDetailView'));
	   $this->assertEquals('my_f_f', Util::toUnderScore('myFF'));
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

		$input = array('dir1/dir2', 'file1.json');
		$result= 'dir1/dir2/file1.json';
		$this->assertEquals($result, Util::concatPath($input));

		$input = array('dir1/', 'dir2', 'file1.json');
		$result = 'dir1/dir2/file1.json';
		$this->assertEquals($result, Util::concatPath($input));
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

	function testGetNaming()
	{
		$this->assertEquals('myPrefixMyName', Util::getNaming('myName', 'myPrefix', 'prefix'));

		$this->assertEquals('myNameMyPostfix', Util::getNaming('myName', 'myPostfix', 'postfix'));
		$this->assertEquals('myNameMyPostfix', Util::getNaming('my_name', 'myPostfix', 'postfix', '_'));
		$this->assertEquals('myNameMyPostfix', Util::getNaming('my_name', 'my_postfix', 'postfix', '_'));
	}

	function testReplaceInArray()
	{
		$testArray = array(
			'option' => array(
				'default' => '{0}',
				 'testKey' => array(
					'{0}' => 'testVal',
				 ),
			),
		);

		$testResult = array(
			'option' => array(
				'default' => 'DONE',
				 'testKey' => array(
					'DONE' => 'testVal',
				 ),
			),
		);

		$this->assertEquals($testResult, Util::replaceInArray('{0}', 'DONE', $testArray, true));
	}


	function testGetClassName()
	{
		$this->assertEquals('\Espo\EntryPoints\Donwload', Util::getClassName('application/Espo/EntryPoints/Donwload.php'));
		$this->assertEquals('\Espo\EntryPoints\Donwload', Util::getClassName('custom/Espo/EntryPoints/Donwload.php'));
		$this->assertEquals('\Espo\EntryPoints\Donwload', Util::getClassName('Espo/EntryPoints/Donwload.php'));
		$this->assertEquals('\Espo\EntryPoints\Donwload', Util::getClassName('application/Espo/EntryPoints/Donwload'));
	}

	function testUnsetInArrayNotSingle()
	{
		$input = array(
			'Account' => array(
				'useCache' => true,
				'sub' =>  array (
					'subV' => '125',
					'subO' => array(
						'subOV' => '125',
						'subOV2' => '125',
					),
				),
			),
		);

		$unsets = array(
			'Account' => array(
				'sub.subO.subOV', 'sub.subV',
			),
		);

		$result = array(
			'Account' => array(
				'useCache' => true,
				'sub' =>  array (
					'subO' => array(
						'subOV2' => '125',
					),
				),
			),
		);

		$this->assertEquals($result, Util::unsetInArray($input, $unsets));
	}

	function testUnsetInArraySingle()
	{
		$input = array(
			'Account' => array(
				'useCache' => true,
				'sub' =>  array (
					'subV' => '125',
					'subO' => array(
						'subOV' => '125',
						'subOV2' => '125',
					),
				),
			),
		);

		$unsets = array(
			'Account.sub.subO.subOV', 'Account.sub.subV',
		);

		$result = array(
			'Account' => array(
				'useCache' => true,
				'sub' =>  array (
					'subO' => array(
						'subOV2' => '125',
					),
				),
			),
		);

		$this->assertEquals($result, Util::unsetInArray($input, $unsets));
	}


	function testUnsetInArrayTogether()
	{
		$input = array(
			'Account' => array(
				'useCache' => true,
				'sub' =>  array (
					'subV' => '125',
					'subO' => array(
						'subOV' => '125',
						'subOV2' => '125',
					),
				),
			),
		);

		$unsets = array(
			'Account' => array(
				'sub.subO.subOV',
			),
			'Account.sub.subV',
		);

		$result = array(
			'Account' => array(
				'useCache' => true,
				'sub' =>  array (
					'subO' => array(
						'subOV2' => '125',
					),
				),
			),
		);

		$this->assertEquals($result, Util::unsetInArray($input, $unsets));
	}


	function testUnsetInArray()
	{
		$input = array(
			'Account' => array(
				'useCache' => true,
				'sub' =>  array (
					'subV' => '125',
					'subO' => array(
						'subOV' => '125',
						'subOV2' => '125',
					),
				),
			),
			'Contact' => array(
				'useCache' => true,
			),
		);

		$unsets = array(
			'Account',
		);

		$result = array(
			'Contact' => array(
				'useCache' => true,
			),
		);

		$this->assertEquals($result, Util::unsetInArray($input, $unsets));
	}

	public function testUnsetInArrayByString()
	{
		$input = array(
			'Account' => array(
				'useCache' => true,
			),
			'Contact' => array(
				'useCache' => true,
			),
		);

		$unsets = 'Account.useCache';

		$result = array(
			'Account' => array(
			),
			'Contact' => array(
				'useCache' => true,
			),
		);

		$this->assertEquals($result, Util::unsetInArray($input, $unsets));
	}

	function testGetValueByKey()
	{
		$inputArray = array(
			'Account' => array(
				'useCache' => true,
				'sub' =>  array (
					'subV' => '125',
					'subO' => array(
						'subOV' => '125',
						'subOV2' => '125',
					),
				),
			),
			'Contact' => array(
				'useCache' => true,
			),
		);


		$this->assertEquals($inputArray, Util::getValueByKey($inputArray));
		$this->assertEquals($inputArray, Util::getValueByKey($inputArray, ''));

		$this->assertEquals('125', Util::getValueByKey($inputArray, 'Account.sub.subV'));

		$result = array('useCache' => true,	);
		$this->assertEquals($result, Util::getValueByKey($inputArray, 'Contact'));

		$this->assertNull(Util::getValueByKey($inputArray, 'Contact.notExists'));

		$this->assertEquals('customReturns', Util::getValueByKey($inputArray, 'Contact.notExists', 'customReturns'));
		$this->assertNotEquals('customReturns', Util::getValueByKey($inputArray, 'Contact.useCache', 'customReturns'));
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