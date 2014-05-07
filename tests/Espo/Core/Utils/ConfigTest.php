<?php

namespace tests\Espo\Core\Utils;

use tests\ReflectionHelper;


class ConfigTest extends \PHPUnit_Framework_TestCase
{
	protected $object;

	protected $objects;

	protected $configPath = 'tests/testData/Utils/Config/config.php';

    protected function setUp()
    {
		$this->objects['fileManager'] = new \Espo\Core\Utils\File\Manager();

        $this->object = new \Espo\Core\Utils\Config($this->objects['fileManager']);

        $this->reflection = new ReflectionHelper($this->object);
        $this->reflection->setProperty('configPath', $this->configPath);
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }


    function testLoadConfig()
	{
		$this->assertArrayHasKey('database', $this->reflection->invokeMethod('loadConfig', array()));

		$this->assertArrayHasKey('dateFormat', $this->reflection->invokeMethod('loadConfig', array()));
	}

    function testGet()
	{
		$result = array(
			'driver' => 'pdo_mysql',
		    'host' => 'localhost',
		    'dbname' => 'espocrm',
		    'user' => 'root',
		    'password' => '',
		);
		$this->assertEquals($result, $this->object->get('database'));

		$result = 'pdo_mysql';
		$this->assertEquals($result, $this->object->get('database.driver'));


		$result = 'YYYY-MM-DD';
		$this->assertEquals($result, $this->object->get('dateFormat'));

		$this->assertTrue($this->object->get('isInstalled'));
	}


    function testSet()
	{
        $setKey= 'testOption';
		$setValue= 'Test';

        $this->assertTrue($this->object->set($setKey, $setValue));
        $this->assertEquals($setValue, $this->object->get($setKey));

        $this->assertTrue($this->object->set($setKey, 'Another Wrong Value'));
	}

	function testSetArray()
	{
		$values = array(
			'testOption' => 'Test',
			'testOption2' => 'Test2',
		);

        $this->assertTrue($this->object->set($values));
        $this->assertEquals('Test', $this->object->get('testOption'));
        $this->assertEquals('Test2', $this->object->get('testOption2'));

        $wrongArray = array(
        	'testOption' => 'Another Wrong Value',
        );
        $this->assertTrue($this->object->set($wrongArray));
	}

}