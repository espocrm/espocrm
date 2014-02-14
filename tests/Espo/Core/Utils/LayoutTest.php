<?php

namespace tests\Espo\Core\Utils;


class LayoutTest extends \PHPUnit_Framework_TestCase
{
	protected $object;
	
	protected $objects;

	protected $filesPath= 'tests/testData/FileManager';

    protected function setUp()
    {                          
		$this->objects['config'] = $this->getMockBuilder('\\Espo\\Core\\Utils\\Config')->disableOriginalConstructor()->getMock(); 	
		$this->objects['fileManager'] = $this->getMockBuilder('\\Espo\\Core\\Utils\\File\\Manager')->disableOriginalConstructor()->getMock(); 			
		$this->objects['metadata'] = $this->getMockBuilder('\\Espo\\Core\\Utils\\Metadata')->disableOriginalConstructor()->getMock(); 			

        $this->object = new \Espo\Core\Utils\Layout($this->objects['config'], $this->objects['fileManager'], $this->objects['metadata']);
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }

	
	function testGetLayoutPathCore()
	{                                                                                  
		$this->objects['metadata']
			->expects($this->exactly(2))
            ->method('getScopeModuleName')
            ->will($this->returnValue(false));
			
		$this->assertEquals('application/Espo/Resources/layouts/User', $this->object->getLayoutPath('User'));  		
		$this->assertEquals('application/Espo/Custom/Resources/layouts/User', $this->object->getLayoutPath('User', true));				   
	}
	
	
	function testGetLayoutPathModule()
	{
		$this->objects['metadata']
			->expects($this->exactly(2))
            ->method('getScopeModuleName')
            ->will($this->returnValue('Crm'));
			
		$this->assertEquals('application/Espo/Modules/Crm/Resources/layouts/Call', $this->object->getLayoutPath('Call'));  		
		$this->assertEquals('application/Espo/Custom/Modules/Crm/Resources/layouts/Call', $this->object->getLayoutPath('Call', true));
	}
	
	function testGet()
	{          
		$result = '[{"label":"Overview","rows":[[{"name":"userName"},{"name":"isAdmin"}],[{"name":"name"},{"name":"title"}],[{"name":"defaultTeam"}],[{"name":"emailAddress"},{"name":"phone"}]]}]';	
	
		$this->objects['metadata']
			->expects($this->exactly(2))
            ->method('getScopeModuleName')
            ->will($this->returnValue(false));
		
		$this->objects['config']
			->expects($this->never())
            ->method('get')
            ->will($this->returnValue('application/Espo/Core/defaults'));
			
		$this->objects['fileManager']
			->expects($this->exactly(1))
            ->method('getContents')
            ->will($this->returnValue($result));
			
		$this->assertEquals($result, $this->object->get('User', 'detail'));  		
	}  	



}

?>
