<?php

namespace tests\Espo\Core\Utils;

use Espo\Core\Utils\Layout;
use Espo\Core\Utils\Util;
use PHPUnit_Framework_MockObject_MockObject;
use tests\ReflectionHelper;


class LayoutTest extends
    \PHPUnit_Framework_TestCase
{

    /**
     * @var Layout
     */
    protected $object;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $objects;

    /**
     * @var ReflectionHelper
     */
    protected $reflection;

    protected $filesPath = 'tests/testData/FileManager';

    function testGetLayoutPathCore()
    {
        $this->objects['metadata']
            ->expects($this->exactly(1))
            ->method('getScopeModuleName')
            ->will($this->returnValue(false));
        $customResults = Util::fixPath('custom/Espo/Custom/Resources/layouts/User');
        $coreResults = Util::fixPath('application/Espo/Resources/layouts/User');
        $this->assertEquals($coreResults, $this->reflection->invokeMethod('getLayoutPath', array('User')));
        $this->assertEquals($customResults, $this->reflection->invokeMethod('getLayoutPath', array('User', true)));
    }

    function testGetLayoutPathModule()
    {
        $this->objects['metadata']
            ->expects($this->exactly(1))
            ->method('getScopeModuleName')
            ->will($this->returnValue('Crm'));
        $corePath = Util::fixPath('application/Espo/Modules/Crm/Resources/layouts/Call');
        $customPath = Util::fixPath('custom/Espo/Custom/Resources/layouts/Call');
        $this->assertEquals($corePath, $this->reflection->invokeMethod('getLayoutPath', array('Call')));
        $this->assertEquals($customPath, $this->reflection->invokeMethod('getLayoutPath', array('Call', true)));
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

    protected function setUp()
    {
        $this->objects['fileManager'] = $this->getMockBuilder('\\Espo\\Core\\Utils\\File\\Manager')->disableOriginalConstructor()->getMock();
        $this->objects['metadata'] = $this->getMockBuilder('\\Espo\\Core\\Utils\\Metadata')->disableOriginalConstructor()->getMock();
        $this->object = new Layout($this->objects['fileManager'], $this->objects['metadata']);
        $this->reflection = new ReflectionHelper($this->object);
        $this->reflection->setProperty('params', array(
            'application/Espo/Core/defaults',
        ));
    }

    protected function tearDown()
    {
        $this->object = null;
    }
}

?>
