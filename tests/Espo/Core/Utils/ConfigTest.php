<?php

namespace tests\Espo\Core\Utils;

use tests\ReflectionHelper;


class ConfigTest extends \PHPUnit_Framework_TestCase
{
	protected $object;
	
	protected $objects;

	protected $defaultConfigPath = 'tests/testData/Utils/Config/defaultConfig.php';

    protected function setUp()
    {   
		$this->objects['fileManager'] = new \Espo\Core\Utils\File\Manager();						

        $this->object = new \Espo\Core\Utils\Config($this->objects['fileManager']);

        $this->reflection = new ReflectionHelper($this->object); 
        $this->reflection->setProperty('defaultConfigPath', $this->defaultConfigPath);
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
			'driver' => 'mysqli',
		    'host' => 'localhost',
		    'dbname' => 'espocrm',
		    'user' => 'root',
		    'password' => '',
		);
		$this->assertEquals($result, $this->object->get('database'));

		$result = 'mysqli';
		$this->assertEquals($result, $this->object->get('database.driver'));


		$result = 'MM/DD/YYYY';
		$this->assertEquals($result, $this->object->get('dateFormat'));
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
		$this->reflection->setProperty('defaultConfigPath', 'tests/testData/Utils/Config/defaultConfigArray.php');

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

        $this->reflection->setProperty('defaultConfigPath', $this->defaultConfigPath);
	}

}