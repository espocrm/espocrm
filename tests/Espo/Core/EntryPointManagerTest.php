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
        $this->reflection->setProperty('customPaths', array(
            'corePath' => '',
            'modulePath' => '',                                             
        ));     
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }


    function testGetData()
    {
        $result = array(
            'Download' => '\tests\testData\EntryPoints\Espo\EntryPoints\Download',
            'Test' => '\tests\testData\EntryPoints\Espo\EntryPoints\Test',
            'InModule' => '\tests\testData\EntryPoints\Espo\Modules\Crm\EntryPoints\InModule'          
        );        
        $this->assertEquals( $result, $this->reflection->invokeMethod('getData', array($this->reflection->getProperty('paths'))) ); 
    }	

    function testGet()
    {
        $this->reflection->setProperty('data', array(
            'Test' => '\tests\testData\EntryPoints\Espo\EntryPoints\Test',            
        ));

        $this->assertEquals('\tests\testData\EntryPoints\Espo\EntryPoints\Test', $this->reflection->invokeMethod('get', array('test')) );
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
