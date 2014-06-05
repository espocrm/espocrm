<?php

namespace tests\Espo\Core\Utils;

use tests\ReflectionHelper;


class LayoutTest extends \PHPUnit_Framework_TestCase
{
	protected $object;

	protected $objects;

	protected $reflection;

	protected $filesPath= 'tests/testData/FileManager';

    protected function setUp()
    {
		$this->objects['fileManager'] = $this->getMockBuilder('\\Espo\\Core\\Utils\\File\\Manager')->disableOriginalConstructor()->getMock();
		$this->objects['metadata'] = $this->getMockBuilder('\\Espo\\Core\\Utils\\Metadata')->disableOriginalConstructor()->getMock();

        $this->object = new \Espo\Core\Utils\Layout($this->objects['fileManager'], $this->objects['metadata']);

        $this->reflection = new ReflectionHelper($this->object);
        $this->reflection->setProperty('params', array(
        	'application/Espo/Core/defaults',
        ) );
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }


	function testGetLayoutPathCore()
	{
		$this->objects['metadata']
			->expects($this->exactly(1))
            ->method('getScopeModuleName')
            ->will($this->returnValue(false));

		$this->assertEquals('application/Espo/Resources/layouts/User', $this->reflection->invokeMethod('getLayoutPath', array('User')) );
		$this->assertEquals('custom/Espo/Custom/Resources/layouts/User', $this->reflection->invokeMethod('getLayoutPath', array('User', true)) );
	}


	function testGetLayoutPathModule()
	{
		$this->objects['metadata']
			->expects($this->exactly(1))
            ->method('getScopeModuleName')
            ->will($this->returnValue('Crm'));

		$this->assertEquals('application/Espo/Modules/Crm/Resources/layouts/Call', $this->reflection->invokeMethod('getLayoutPath', array('Call')) );
		$this->assertEquals('custom/Espo/Custom/Resources/layouts/Call', $this->reflection->invokeMethod('getLayoutPath', array('Call', true)) );
	}

	function testGet()
	{
		$result = '[{"label":"Overview","rows":[[{"name":"userName"},{"name":"isAdmin"}],[{"name":"name"},{"name":"title"}],[{"name":"defaultTeam"}],[{"name":"emailAddress"},{"name":"phone"}]]}]';

		$this->objects['metadata']
			->expects($this->exactly(1))
            ->method('getScopeModuleName')
            ->will($this->returnValue(false));

		$this->objects['fileManager']
			->expects($this->exactly(1))
            ->method('getContents')
            ->will($this->returnValue($result));

		$this->assertEquals($result, $this->object->get('Note', 'detail'));
	}



}

?>
