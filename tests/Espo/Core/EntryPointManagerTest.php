<?php

namespace tests\Espo\Core;

use tests\ReflectionHelper;


class EntryPointManagerTest extends \PHPUnit_Framework_TestCase
{
	protected $object;
	
	protected $objects;

	protected $filesPath= 'tests/testData/EntryPoints';

    protected function setUp()
    {                          
		$this->objects['container'] = $this->getMockBuilder('\\Espo\\Core\\Container')->disableOriginalConstructor()->getMock();

        $this->object = new \Espo\Core\EntryPointManager($this->objects['container']);

        $this->reflection = new ReflectionHelper($this->object);

        $fileManager = new \Espo\Core\Utils\File\Manager( (object) array());
        $this->reflection->setProperty('fileManager', $fileManager); 

        $this->reflection->setProperty('cacheFile', 'tests/testData/EntryPoints/cache/entryPoints.php');        
        $this->reflection->setProperty('paths', array(
            'corePath' => 'tests/testData/EntryPoints/Espo/EntryPoints',
            'modulePath' => 'tests/testData/EntryPoints/Espo/Modules/Crm/EntryPoints',                                             
        ));             
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }

    function testGet()
    {
        $this->reflection->setProperty('data', array(
            'Test' => '\tests\testData\EntryPoints\Espo\EntryPoints\Test',            
        ));

        $this->assertEquals('\tests\testData\EntryPoints\Espo\EntryPoints\Test', $this->reflection->invokeMethod('getClassName', array('test')) );
    }


    function testRun()
    {
        $this->reflection->setProperty('data', array(
            'Test' => '\tests\testData\EntryPoints\Espo\EntryPoints\Test',            
        ));

        $this->assertNull( $this->reflection->invokeMethod('run', array('test')) );
    }

}

?>
