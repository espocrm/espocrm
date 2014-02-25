<?php

namespace tests\Espo\Core\Utils;

use tests\ReflectionHelper;


class MetadataTest extends \PHPUnit_Framework_TestCase
{
	protected $object;
	
	protected $objects;

	protected $reflection;


    protected function setUp()
    {     
    	$this->objects['config'] = $this->getMockBuilder('\Espo\Core\Utils\Config')->disableOriginalConstructor()->getMock();
		$this->objects['fileManager'] = new \Espo\Core\Utils\File\Manager(); 	

		//set to use cache
		$this->objects['config']
			->expects($this->any())
            ->method('get')
            ->will($this->returnValue(true));		
		 			

        $this->object = new \Espo\Core\Utils\Metadata($this->objects['config'], $this->objects['fileManager']);

        $this->reflection = new ReflectionHelper($this->object);  
        $this->reflection->setProperty('cacheFile', 'tests/testData/Utils/Metadata/metadata.php');       
        $this->reflection->setProperty('ormCacheFile', 'tests/testData/Utils/Metadata/ormMetadata.php');     
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }
	
	
	function testGet()
	{          
		$result = 'System';
		$this->assertEquals($result, $this->object->get('app.adminPanel.system.label'));  		

		$result = 'fields';
		$this->assertArrayHasKey($result, $this->object->get('entityDefs.User')); 
	}  	



}

?>
