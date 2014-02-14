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
		$this->objects['fileManager'] = new \Espo\Core\Utils\File\Manager(
			array(
				'defaultPermissions' => array (
				    'dir' => '0775',
				    'file' => '0664',
				    'user' => '',
				    'group' => '',
			  ),
			)
		);						

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

}