<?php

namespace tests\Espo\Core\Utils\File;

use tests\ReflectionHelper;


class ManagerTest extends \PHPUnit_Framework_TestCase
{
	protected $object;

	protected $objects;

	protected $filesPath= 'tests/testData/FileManager';

	protected $reflection;

    protected function setUp()
    {
    	$this->objects['config'] = $this->getMockBuilder('\Espo\Core\Utils\Config')->disableOriginalConstructor()->getMock();

    	$this->object = new \Espo\Core\Utils\File\Manager();

		$this->reflection = new ReflectionHelper($this->object);
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }


	public function testGetFileName()
	{
		$this->assertEquals('Donwload', $this->object->getFileName('Donwload.php'));

		$this->assertEquals('Donwload', $this->object->getFileName('/Donwload.php'));

		$this->assertEquals('Donwload', $this->object->getFileName('\Donwload.php'));

		$this->assertEquals('Donwload', $this->object->getFileName('application/Espo/EntryPoints/Donwload.php'));
	}

	public function testGetContents()
	{
		$result = file_get_contents($this->filesPath.'/getContent/test.json');
		$this->assertEquals($result, $this->object->getContents( array($this->filesPath, 'getContent/test.json') ));
	}


	public function testPutContents()
	{
		$testPath= $this->filesPath.'/setContent';

        $result= 'next value';
		$this->assertTrue($this->object->putContents(array($testPath, 'test.json'), $result));

    	$this->assertEquals($result, $this->object->getContents( array($testPath, 'test.json')) );

    	$this->assertTrue($this->object->putContents(array($testPath, 'test.json'), 'initial value'));
	}


	public function testConcatPaths()
	{
		$input = 'application/Espo/Resources/metadata/app/panel.json';
		$result = 'application/Espo/Resources/metadata/app/panel.json';

		$this->assertEquals($result, $this->reflection->invokeMethod('concatPaths', array($input)) );


		$input = array(
			'application',
			'Espo/Resources/metadata/',
			'app',
			'panel.json',
		);
		$result = 'application/Espo/Resources/metadata/app/panel.json';

		$this->assertEquals($result, $this->reflection->invokeMethod('concatPaths', array($input)) );


		$input = array(
			'application/Espo/Resources/metadata/app',
			'panel.json',
		);
		$result = 'application/Espo/Resources/metadata/app/panel.json';

		$this->assertEquals($result, $this->reflection->invokeMethod('concatPaths', array($input)) );


		$input = array(
			'application/Espo/Resources/metadata/app/',
			'panel.json',
		);
		$result = 'application/Espo/Resources/metadata/app/panel.json';

		$this->assertEquals($result, $this->reflection->invokeMethod('concatPaths', array($input)) );
	}

	public function testGetDirName()
	{
		$input = 'data/logs/espo.log';
		$result = 'logs';
		$this->assertEquals($result, $this->object->getDirName($input, false));

		$input = 'data/logs/espo.log/';
		$result = 'logs';
		$this->assertEquals($result, $this->object->getDirName($input, false));

		$input = 'application/Espo/Resources/metadata/entityDefs';
		$result = 'metadata';
		$this->assertEquals($result, $this->object->getDirName($input, false));

		$input = 'application/Espo/Resources/metadata/entityDefs/';
		$result = 'metadata';
		$this->assertEquals($result, $this->object->getDirName($input, false));

		$input = '/application/Espo/Resources/metadata/entityDefs';
		$result = 'metadata';
		$this->assertEquals($result, $this->object->getDirName($input, false));

		$input = 'notRealPath/logs/espo.log';
		$result = 'logs';
		$this->assertEquals($result, $this->object->getDirName($input, false));
	}


	public function testGetDirNameFullPath()
	{
		$input = 'data/logs/espo.log';
		$result = 'data/logs';
		$this->assertEquals($result, $this->object->getDirName($input));

		$input = 'data/logs/espo.log/';
		$result = 'data/logs';
		$this->assertEquals($result, $this->object->getDirName($input));

		$input = 'application/Espo/Resources/metadata/entityDefs';
		$result = 'application/Espo/Resources/metadata';
		$this->assertEquals($result, $this->object->getDirName($input));

		$input = 'application/Espo/Resources/metadata/entityDefs/';
		$result = 'application/Espo/Resources/metadata';
		$this->assertEquals($result, $this->object->getDirName($input));

		$input = '/application/Espo/Resources/metadata/entityDefs';
		$result = '/application/Espo/Resources/metadata';
		$this->assertEquals($result, $this->object->getDirName($input));

		$input = 'notRealPath/logs/espo.log';
		$result = 'notRealPath/logs';
		$this->assertEquals($result, $this->object->getDirName($input));
	}

	public function testUnsetContents()
	{
		$testPath = $this->filesPath.'/unsets/test.json';

		$initData = '{"fields":{"someName":{"type":"varchar","maxLength":40},"someName2":{"type":"varchar","maxLength":36}}}';
		$this->object->putContents($testPath, $initData);

		$unsets = 'fields.someName2';
		$this->assertTrue($this->object->unsetContents($testPath, $unsets));

		$result = '{"fields":{"someName":{"type":"varchar","maxLength":40}}}';
		$this->assertJsonStringEqualsJsonFile($testPath, $result);
	}


}

?>
